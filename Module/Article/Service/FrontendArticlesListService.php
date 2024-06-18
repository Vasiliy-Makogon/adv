<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Article\Service;

use Krugozor\Cover\CoverArray;
use Krugozor\Database\Statement;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Mapper\AbstractMapper;
use Krugozor\Framework\Module\Article\Model\Article;
use Krugozor\Framework\Pagination\Manager as PaginationManager;
use Krugozor\Framework\Service\AbstractListService;
use Krugozor\Framework\Service\Trait\MemcacheTrait;

class FrontendArticlesListService extends AbstractListService
{
    use MemcacheTrait;

    /**
     * @param Request $request
     * @param AbstractMapper $mapper
     * @param PaginationManager $paginationManager
     */
    public function __construct(
        protected Request $request,
        protected AbstractMapper $mapper,
        protected PaginationManager $paginationManager
    )
    {
        $this->default_order_options = [
            'field_name' => 'article.id',
            'sort_order' => 'DESC'
        ];

        parent::__construct($request, $mapper, $paginationManager);
    }

    /**
     * @inheritDoc
     */
    public function findList(): static
    {
        $this->findListConditions();

        $this->list = $this->mapper->callableExecuteByParams(
            $this->createParams(true),
            function (Statement $statement) {
                $result = new CoverArray();

                while ($data = $statement->fetchAssoc()) {
                    $article = $this->findByIdThroughCache(
                        $data['id'],
                        Article::class,
                        60 * 60 * 24 * 30
                    );

                    if ($article->getId()) {
                        $article->setScore($data['article__score'] ?? 0);
                        $result->append($article);
                    }
                }

                return $result;
            }
        );

        $this->findListConditions(true);

        $totalRows = $this->mapper->callableExecuteByParams(
            $this->createParams(),
            function (Statement $statement) {
                return (int) $statement->getOne();
            }
        );

        $this->paginationManager->setCount($totalRows);

        return $this;
    }

    /**
     * Общие условия поиска для обоих запросов.
     *
     * @param bool $queryGetTotalRows
     */
    private function findListConditions(bool $queryGetTotalRows = false): void
    {
        // Используется поиск
        $searchKeyword = $this->request->getRequest('keyword', Request::SANITIZE_STRING_FULLTEXT);
        $searchKeyword = $searchKeyword && mb_strlen($searchKeyword) < 50 ? $searchKeyword : '';

        if ($queryGetTotalRows) {
            $this->sql_what_string_buffer[] = 'COUNT(*)';
        } else {
            $this->sql_what_string_buffer[] = '`article`.`id`';

            if ($searchKeyword) {
                $this->sql_what_string_buffer[] =
                    'ROUND(MATCH (
                        `article`.`article_header`, 
                        `article`.`article_url`, 
                        `article`.`article_text`) AGAINST ("?s"), 2) as `article__score`';
                $this->sql_what_args_buffer[] = $searchKeyword;
            }
        }

        $this->sql_from_string_buffer[] = '`article`';

        if ($searchKeyword) {
            $this->sql_where_string_buffer[] =
                'MATCH (
                    `article`.`article_header`, 
                    `article`.`article_url`, 
                    `article`.`article_text`
                ) AGAINST ("?s")';
            $this->sql_where_args_buffer[] = $searchKeyword;
        }

        if (!$queryGetTotalRows) {
            $this->sql_limit_args_buffer = [
                $this->paginationManager->getStartLimit(),
                $this->paginationManager->getStopLimit(),
            ];
        }

        if ($searchKeyword && !$queryGetTotalRows) {
            $this->sql_order_string_buffer['article__score'] = 'DESC';
        }

        $this->sql_order_string_buffer[$this->getFieldName()] = $this->getOrder();
    }
}