<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Advert\Service;

use Krugozor\Database\Statement;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Mapper\AbstractMapper;
use Krugozor\Framework\Mapper\MapperParamsCreator;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\Advert\Type\AdvertType;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Module\Category\Model\Category;
use Krugozor\Framework\Pagination\Manager as PaginationManager;
use Krugozor\Framework\Service\AbstractListService;

class BackendAdvertsListService extends AbstractListService
{
    /** @var CategoryMapper */
    private CategoryMapper $categoryMapper;

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
        $this->order_options['id'] = 'advert.id';
        $this->order_options['header'] = 'advert.advert_header';
        $this->order_options['category'] = 'category.category_name';
        $this->order_options['active'] = 'advert.advert_active';
        $this->order_options['vip'] = 'advert.advert_vip_date';
        $this->order_options['special'] = 'advert.advert_special_date';
        $this->order_options['image'] = 'advert.advert_thumbnail_count';
        $this->order_options['user_name'] = 'user.user_first_name';
        $this->order_options['payment'] = 'advert.advert_payment';
        $this->order_options['was_moderated'] = 'advert.advert_was_moderated';
        $this->order_options['advert_create_date'] = 'advert.advert_create_date';
        $this->order_options['advert_type'] = 'advert.advert_type';

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
        // @todo это не будет работать с пагинацией, изменить передачу сортировок в пагинации
        $this->sql_order_string_buffer['advert_was_moderated'] = 'DESC';

        // Используется поиск
        $searchKeyword = $this->request->getRequest('keyword', Request::SANITIZE_STRING_FULLTEXT);
        $searchKeyword = $searchKeyword && mb_strlen($searchKeyword) < 50 ? $searchKeyword : '';

        // Запрос на общее кол-во данных
        if ($queryGetTotalRows) {
            $this->sql_what_string_buffer[] = 'COUNT(*)';
            // Запрос на получение данных
        } else {
            $this->sql_what_string_buffer[] = '`advert`.*';
            $this->sql_what_string_buffer[] = '`user`.*';
            $this->sql_what_string_buffer[] = '`category`.*';
            $this->sql_what_string_buffer[] = '`user-city`.*';
            $this->sql_what_string_buffer[] = '`user-region`.*';
            $this->sql_what_string_buffer[] = '`user-country`.*';
            $this->sql_what_string_buffer[] = '`user-invite_anonymous_user`.*';

            if ($searchKeyword) {
                $this->sql_what_string_buffer[] = 'ROUND(MATCH (`advert`.`advert_header`, `advert`.`advert_text`) AGAINST ("?s"), 2) as `advert__score`';
                $this->sql_what_args_buffer[] = $searchKeyword;
            }
        }

        $user = $this->request->getRequest('user', Request::SANITIZE_INT);
        if ($user) {
            $this->sql_where_string_buffer[] = '?f = ?i';
            $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('id_user');
            $this->sql_where_args_buffer[] = $user;
        }

        $unique_user_cookie_id = $this->request->getRequest('unique_user_cookie_id', Request::SANITIZE_STRING);
        if ($unique_user_cookie_id) {
            $this->sql_where_string_buffer[] = '?f = "?s"';
            $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('unique_user_cookie_id');
            $this->sql_where_args_buffer[] = $unique_user_cookie_id;
        }

        $type = $this->request->getRequest('type', Request::SANITIZE_STRING);
        if ($type && in_array($type, array_keys(AdvertType::ADVERT_TYPES))) {
            $this->sql_where_string_buffer[] = '?f = "?s"';
            $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('type');
            $this->sql_where_args_buffer[] = $type;
        }


        // Создание подзапроса
        $subqueryParams = [];

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

        [$fromSql, $fromArgs] = parent::createParamsFrom(true);
        if ($fromSql) {
            $subqueryParams[MapperParamsCreator::KEY_FROM][$fromSql] = $fromArgs;
        }

        [$joinSql, $joinArgs] = parent::createParamsJoin(true);
        if ($joinSql) {
            $subqueryParams[MapperParamsCreator::KEY_JOIN][$joinSql] = $joinArgs;
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
                SELECT `advert`.`id`
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


        $this->sql_join_string_buffer[] = 'STRAIGHT_JOIN `advert` ON `advert`.`id` = `t`.`id`';
        if (!$queryGetTotalRows) {
            $this->sql_join_string_buffer[] = 'STRAIGHT_JOIN `user-country` ON `advert`.`advert_place_country` = `user-country`.`id`';
            $this->sql_join_string_buffer[] = 'STRAIGHT_JOIN `user-region` ON `advert`.`advert_place_region` = `user-region`.`id`';
            $this->sql_join_string_buffer[] = 'STRAIGHT_JOIN `user-city` ON `advert`.`advert_place_city` = `user-city`.`id`';
            $this->sql_join_string_buffer[] = 'STRAIGHT_JOIN `category` ON `advert`.`advert_category` = `category`.`id`';
            $this->sql_join_string_buffer[] = 'LEFT JOIN `user` ON `advert`.`advert_id_user` = `user`.`id`';
            $this->sql_join_string_buffer[] = 'LEFT JOIN `user-invite_anonymous_user` ON `user-invite_anonymous_user`.`unique_cookie_id` = `advert`.`advert_unique_user_cookie_id`';
        }

        if ($searchKeyword && !$queryGetTotalRows) {
            $this->sql_order_string_buffer['advert__score'] = 'DESC';
        }
    }
}