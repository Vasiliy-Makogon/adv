<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Service;

use Krugozor\Database\Statement;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Mapper\AbstractMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Pagination\Manager as PaginationManager;
use Krugozor\Framework\Service\AbstractListService;

class UserListService extends AbstractListService
{
    /**
     * Алиасы из запроса и поля таблицы, содержащие имена столбцов территорий.
     *
     * @var array
     */
    private static array $territory = [
        'user_city' => 'user.user_city',
        'user_region' => 'user.user_region',
        'user_country' => 'user.user_country`',
    ];

    /**
     * Алиасы и имена полей для текстового поиска.
     *
     * @var array
     */
    private static array $text_search_cols = array
    (
        'user_first_name' => '`user`.`user_first_name`',
        'user_telegram' => '`user`.`user_telegram`',
        'user_url' => '`user`.`user_url`',
        'user_email' => '`user`.`user_email`',
        'user_login' => '`user`.`user_login`',
        'user_id' => '`user`.`id`',
        'user_ip' => '`user`.`user_ip`',
    );

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
        $this->order_options['id'] = 'user.id';
        $this->order_options['login'] = 'user.user_login';
        $this->order_options['ip'] = 'user.user_ip';
        $this->order_options['active'] = 'user.user_active';

        $this->default_order_options = array(
            'field_name' => 'user.id',
            'sort_order' => 'DESC'
        );

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
     * @param bool $queryGetTotalRows
     */
    private function findListConditions(bool $queryGetTotalRows = false): void
    {
        if ($queryGetTotalRows) {
            $this->sql_what_string_buffer[] = 'COUNT(*)';
        } else {
            $this->sql_what_string_buffer[] = '`user`.*';
            $this->sql_what_string_buffer[] = 'COUNT(advert.id) as `advert_count`';
            $this->sql_what_string_buffer[] = '`user-city`.*';
            $this->sql_what_string_buffer[] = '`user-region`.*';
            $this->sql_what_string_buffer[] = '`user-country`.*';
        }

        $this->sql_from_string_buffer[] = '`user`';
        if (!$queryGetTotalRows) {
            $this->sql_join_string_buffer[] = 'LEFT JOIN `user-country` ON `user`.`user_country` = `user-country`.`id`';
            $this->sql_join_string_buffer[] = 'LEFT JOIN `user-region` ON `user`.`user_region` = `user-region`.`id`';
            $this->sql_join_string_buffer[] = 'LEFT JOIN `user-city` ON `user`.`user_city` = `user-city`.`id`';
            $this->sql_join_string_buffer[] = 'LEFT JOIN `advert` ON `user`.`id` = `advert`.`advert_id_user`';
        }

        $this->processAnonymousExceptionCondition();
        $this->processTerritorySearchCondition();
        $this->processTextSearchCondition();
        $this->processUserActiveSearchCondition();

        if (!$queryGetTotalRows) {
            $this->sql_group_string_buffer[] = 'user.id';
            $this->sql_order_string_buffer[$this->getFieldName()] = $this->getOrder();
        }

        if (!$queryGetTotalRows) {
            $this->sql_limit_args_buffer = [
                $this->paginationManager->getStartLimit(),
                $this->paginationManager->getStopLimit(),
            ];
        }
    }

    /**
     * Установка параметров поиска по анонимным пользователям для условия WHERE.
     */
    private function processAnonymousExceptionCondition(): void
    {
        $this->sql_where_string_buffer[] = '?f <> ?i';
        $this->sql_where_args_buffer[] = 'user.id';
        $this->sql_where_args_buffer[] = User::GUEST_USER_ID;
    }

    /**
     * Установка параметров поиска по строке для условия WHERE.
     */
    private function processTextSearchCondition(): void
    {
        if ($keyword = $this->request->getRequest('keyword', Request::SANITIZE_STRING)) {
            $colsearch = $this->request->getRequest('colsearch', Request::SANITIZE_STRING);

            if ($colsearch != 'user_id') {
                $columns = !empty(static::$text_search_cols[$colsearch])
                    ? [static::$text_search_cols[$colsearch]]
                    : array_values(static::$text_search_cols);
                $this->sql_where_string_buffer[] = 'CONCAT_WS(",", ' .
                    implode(', ', $columns) .
                    ') LIKE "%?s%"';
                $this->sql_where_args_buffer[] = $keyword;
            } else {
                $this->sql_where_string_buffer[] = '`user`.`id` = ?i';
                $this->sql_where_args_buffer[] = $keyword;
            }
        }
    }

    /**
     * Установка параметров поиска по активности пользователя для условия WHERE.
     */
    private function processUserActiveSearchCondition(): void
    {
        if (($user_active = $this->request->getRequest('user_active', Request::SANITIZE_INT)) !== null) {
            $this->sql_where_string_buffer[] = '?f = ?i';
            $this->sql_where_args_buffer[] = 'user.user_active';
            $this->sql_where_args_buffer[] = $user_active;
        }
    }

    /**
     * Установка параметров поиска по территориям для условия WHERE.
     */
    private function processTerritorySearchCondition(): void
    {
        foreach (static::$territory as $territory_key => $territory_field_name) {
            if ($request_territory_id = $this->request->getGet($territory_key, Request::SANITIZE_INT)) {
                $this->sql_where_string_buffer[] = '?f = ?i';
                $this->sql_where_args_buffer[] = $territory_field_name;
                $this->sql_where_args_buffer[] = $request_territory_id;
            }
        }
    }
}