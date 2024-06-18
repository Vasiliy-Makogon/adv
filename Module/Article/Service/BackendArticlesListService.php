<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Article\Service;

use Krugozor\Database\Statement;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Mapper\AbstractMapper;
use Krugozor\Framework\Mapper\MapperParamsCreator;
use Krugozor\Framework\Pagination\Manager as PaginationManager;
use Krugozor\Framework\Service\AbstractListService;

class BackendArticlesListService extends AbstractListService
{
    /**
     * @param Request $request
     * @param AbstractMapper $mapper
     * @param PaginationManager $paginationManager
     */
    public function __construct(
        protected Request $request,
        protected AbstractMapper $mapper,
        protected PaginationManager $paginationManager
    ) {
        $this->order_options['id'] = 'article.id';
        $this->order_options['header'] = 'article.article_header';
        $this->order_options['active'] = 'article.article_active';
        $this->order_options['is_html'] = 'article.article_is_html';
        $this->order_options['article_create_date'] = 'article.article_edit_date';
        $this->order_options['article_create_date'] = 'article.article_create_date';

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
                return $this->mapper->result2objects($statement);
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
        $this->sql_order_string_buffer[$this->getFieldName()] = $this->getOrder();

        // Используется поиск
        $searchKeyword = $this->request->getRequest('keyword', Request::SANITIZE_STRING_FULLTEXT);
        $searchKeyword = $searchKeyword && mb_strlen($searchKeyword) < 50 ? $searchKeyword : '';

        // Запрос на общее кол-во данных
        if ($queryGetTotalRows) {
            $this->sql_what_string_buffer[] = 'COUNT(*)';
        } else {
            $this->sql_what_string_buffer[] = '*';

            if ($searchKeyword) {
                $this->sql_what_string_buffer[] =
                    'ROUND(MATCH (
                        `article`.`article_header`, 
                        `article`.`article_url`, 
                        `article`.`article_text`) AGAINST ("?s"), 2) as `article__score`';
                $this->sql_what_args_buffer[] = $searchKeyword;
            }
        }

        // Создание подзапроса
        $subqueryParams = [];

        $this->sql_from_string_buffer[] = '`article`';

        if ($searchKeyword) {
            $this->sql_where_string_buffer[] =
                'MATCH (`article`.`article_header`, `article`.`article_url`, `article`.`article_text`) AGAINST ("?s")';
            $this->sql_where_args_buffer[] = $searchKeyword;
        }

        [$fromSql, $fromArgs] = parent::createParamsFrom(true);
        if ($fromSql) {
            $subqueryParams[MapperParamsCreator::KEY_FROM][$fromSql] = $fromArgs;
        }

        [$whereSql, $whereArgs] = parent::createParamsWhere(true);
        if ($whereSql) {
            $subqueryParams[MapperParamsCreator::KEY_WHERE][$whereSql] = $whereArgs;
        }

        if (!$queryGetTotalRows) {
            $this->sql_limit_args_buffer = [
                $this->paginationManager->getStartLimit(),
                $this->paginationManager->getStopLimit(),
            ];
            $subqueryParams[MapperParamsCreator::KEY_LIMIT] = parent::createdParamsLimit(true);
            $subqueryParams[MapperParamsCreator::KEY_ORDER] = parent::createParamsOrder(true);
        }

        $params = (new MapperParamsCreator($subqueryParams))->createParams();

        $this->sql_from_string_buffer[] = "(
                SELECT `article`.`id`
                $params[from]
                $params[join]
                $params[where]
                $params[order]
                $params[limit]) AS `t`
            ";
        $this->sql_from_args_buffer = array_merge_recursive(
            $this->sql_from_args_buffer,
            $params[MapperParamsCreator::KEY_ARGS]
        );

        $this->sql_join_string_buffer[] = 'STRAIGHT_JOIN `article` ON `article`.`id` = `t`.`id`';

        if ($searchKeyword && !$queryGetTotalRows) {
            $this->sql_order_string_buffer['article__score'] = 'DESC';
        }
    }
}