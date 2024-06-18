<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Advert\Service;

use Krugozor\Cover\CoverArray;
use Krugozor\Database\Statement;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Mapper\AbstractMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\Advert\Service\Trait\MemcacheTrait;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Module\Category\Model\Category;
use Krugozor\Framework\Pagination\Manager as PaginationManager;
use Krugozor\Framework\Service\AbstractListService;

class FrontendUserAdvertsListService extends AbstractListService
{
    use MemcacheTrait;

    /** @var CategoryMapper|null */
    protected ?CategoryMapper $categoryMapper = null;

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
        $this->order_options['id'] = 'advert.id';
        $this->order_options['header'] = 'advert.advert_header';
        $this->order_options['active'] = 'advert.advert_active';
        $this->order_options['image'] = 'advert.advert_thumbnail_count';
        $this->order_options['advert_create_date'] = 'advert.advert_create_date';
        $this->order_options['advert_edit_date'] = 'advert.advert_edit_date';
        $this->order_options['price'] = 'advert.advert_price';
        $this->order_options['balance'] = 'advert.advert_balance';

        $this->default_order_options = [
            'field_name' => 'advert.id',
            'sort_order' => 'DESC'
        ];

        parent::__construct($request, $mapper, $paginationManager);
    }

    /**
     * @param CategoryMapper $categoryMapper
     * @return static
     */
    public function setCategoryMapper(CategoryMapper $categoryMapper): static
    {
        $this->categoryMapper = $categoryMapper;

        return $this;
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
                    if ($advertData = $this->findByIdForViewThroughCache($data['id'])->getFirst()) {
                        $advertData->get('advert')->setScore($data['advert__score'] ?? 0);
                        $result->append($advertData);
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
     * @param bool $queryGetTotalRows использовать COUNT(*) для пагинации
     */
    private function findListConditions(bool $queryGetTotalRows = false): void
    {
        // Используется поиск
        $searchKeyword = $this->request->getRequest('keyword', Request::SANITIZE_STRING_FULLTEXT);
        $searchKeyword = $searchKeyword && mb_strlen($searchKeyword) < 50 ? $searchKeyword : '';

        if ($queryGetTotalRows) {
            $this->sql_what_string_buffer[] = 'COUNT(*)';
        } else {
            $this->sql_what_string_buffer[] = '`advert`.`id`';

            if ($searchKeyword) {
                $this->sql_what_string_buffer[] =
                    'ROUND(MATCH (
                        `advert`.`advert_header`, 
                        `advert`.`advert_text`) AGAINST ("?s"), 2) as `advert__score`';
                $this->sql_what_args_buffer[] = $searchKeyword;
            }
        }

        $user = $this->request->getRequest('user', Request::SANITIZE_INT);
        if ($user) {
            $this->sql_where_string_buffer[] = '?f = ?i';
            $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('id_user');
            $this->sql_where_args_buffer[] = $user;
        }

        /** @var Category $category */
        $category = null;
        if ($categoryId = $this->request->getRequest('category', Request::SANITIZE_INT)) {
            $category = $this->categoryMapper->findModelById($categoryId);
        }

        if ($category) {
            // Категория имеющая потомков
            if ($category->findChildsIds()->count()) {
                $this->sql_from_string_buffer[] = '`category-category_all_childs_with_parent` AS `c`';
                $this->sql_join_string_buffer[] = 'STRAIGHT_JOIN `advert` ON `advert`.`advert_category` = `c`.`child_id`';
                $this->sql_where_string_buffer[] = '`category_id` = ?i';
            } else {
                $this->sql_from_string_buffer[] = '`advert`';
                $this->sql_where_string_buffer[] = '`advert`.`advert_category` = ?i';
            }
            $this->sql_where_args_buffer[] = $categoryId;
        } else {
            $this->sql_from_string_buffer[] = '`advert`';
        }

        if ($searchKeyword) {
            $this->sql_where_string_buffer[] = 'MATCH (`advert`.`advert_header`, `advert`.`advert_text`) AGAINST ("?s")';
            $this->sql_where_args_buffer[] = $searchKeyword;
        }

        if (!$queryGetTotalRows) {
            $this->sql_limit_args_buffer = [
                $this->paginationManager->getStartLimit(),
                $this->paginationManager->getStopLimit(),
            ];
        }

        if ($searchKeyword && !$queryGetTotalRows) {
            $this->sql_order_string_buffer['advert__score'] = 'DESC';
        }

        $this->sql_order_string_buffer[$this->getFieldName()] = $this->getOrder();
    }
}