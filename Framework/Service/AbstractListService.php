<?php

declare(strict_types=1);

namespace Krugozor\Framework\Service;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Mapper\AbstractMapper;
use Krugozor\Framework\Mapper\MapperParamsCreator;
use Krugozor\Framework\Pagination\Manager as PaginationManager;

/**
 * Сервис получения списка записей на основе Request-данных, таких как сортировка, лимитирование и пр.
 */
abstract class AbstractListService
{
    /** @var array */
    protected array $sql_where_string_buffer = [];

    /** @var array */
    protected array $sql_where_args_buffer = [];

    /** @var array */
    protected array $sql_order_string_buffer = [];

    /** @var array */
    protected array $sql_what_string_buffer = [];

    /** @var array */
    protected array $sql_what_args_buffer = [];

    /** @var array */
    protected array $sql_from_string_buffer = [];

    /** @var array */
    protected array $sql_from_args_buffer = [];

    /** @var array */
    protected array $sql_join_string_buffer = [];

    /** @var array */
    protected array $sql_join_args_buffer = [];

    /** @var array */
    protected array $sql_group_string_buffer = [];

    /** @var array */
    protected array $sql_group_args_buffer = [];

    /** @var array */
    protected array $sql_limit_args_buffer = [];

    /**
     * Параметры сортировки по-умолчанию.
     * @see $order_options
     *
     * @var array
     */
    protected array $default_order_options = [
        'field_name' => 'id',
        'sort_order' => 'DESC',
    ];

    /**
     * Массив возможных сортировок, объявляемый в конкретном классе, где ключом является алиас из запроса,
     * а значением - имя реального столбца в БД, пример: ['col1' => '`table_name`.`col_name`', ...]
     *
     * @var array
     */
    protected array $order_options = [];

    /**
     * Список найденных записей.
     *
     * @var CoverArray
     */
    protected CoverArray $list;

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
        $this->list = new CoverArray();

        $this->declareDefaultSortOptions();
    }

    /**
     * Находит список записей.
     *
     * @see $list
     * @return static
     */
    abstract public function findList(): static;

    /**
     * Возвращает объект менеджера пагинации.
     *
     * @return PaginationManager
     */
    final public function getPagination(): PaginationManager
    {
        return $this->paginationManager;
    }

    /**
     * Возвращает список найденных записей.
     *
     * @return CoverArray
     */
    final public function getList(): CoverArray
    {
        return $this->list;
    }

    /**
     * Возвращает реальное имя поля сортировки.
     *
     * @return string
     */
    public function getFieldName(): string
    {
        $alias = $this->request->getRequest('field_name', Request::SANITIZE_STRING);

        return $this->order_options[$alias] ?? $this->default_order_options['field_name'];
    }

    /**
     * Возвращает алиас поля сортировки.
     *
     * @return string
     */
    public function getAlias(): string
    {
        $alias = $this->request->getRequest('field_name', Request::SANITIZE_STRING);

        return
            isset($this->order_options[$alias])
                ? $alias
                : $this->default_order_options['field_name'];
    }

    /**
     * Возвращает порядок сортировки.
     *
     * @return string
     */
    public function getOrder(): string
    {
        return match ($this->request->getRequest('sort_order', Request::SANITIZE_STRING)) {
            'ASC' => 'ASC',
            default => 'DESC',
        };
    }

    /**
     * Создает массив параметров для передачи в @param bool $clear true, если нужно очистить буферы. Эта ситуация
     * типичная для случаев, когда в силу каких-то причин будет вызван данный метод более одного раза в сервисе,
     * например, в случае получения общего кол-ва данных в запросе через COUNT. См. пример @return array
     *
     * @see FrontendAdvertsListService
     * @see MapperParamsCreator
     * Фактически, преобразует промежуточные динамические данные под необходимый формат.
     *
     */
    protected function createParams(bool $clear = false): array
    {
        [$whatSql, $whatArgs] = $this->createParamsWhat($clear);
        if ($whatSql) {
            $params[MapperParamsCreator::KEY_WHAT][$whatSql] = $whatArgs;
        }

        [$fromSql, $fromArgs] = $this->createParamsFrom($clear);
        if ($fromSql) {
            $params[MapperParamsCreator::KEY_FROM][$fromSql] = $fromArgs;
        }

        [$joinSql, $joinArgs] = $this->createParamsJoin($clear);
        if ($joinSql) {
            $params[MapperParamsCreator::KEY_JOIN][$joinSql] = $joinArgs;
        }

        [$groupSql, $groupArgs] = $this->createParamsGroup($clear);
        if ($groupSql) {
            $params[MapperParamsCreator::KEY_GROUP][$groupSql] = $groupArgs;
        }

        [$whereSql, $whereArgs] = $this->createParamsWhere($clear);
        if ($whereSql) {
            $params[MapperParamsCreator::KEY_WHERE][$whereSql] = $whereArgs;
        }

        $params[MapperParamsCreator::KEY_ORDER] = $this->createParamsOrder($clear);
        $params[MapperParamsCreator::KEY_LIMIT] = $this->createdParamsLimit($clear);

        return $params;
    }

    /**
     * @param bool $clear
     * @return array
     */
    protected function createParamsWhat(bool $clear = false): array
    {
        $data = [null, null];

        if ($buffer = array_filter($this->sql_what_string_buffer)) {
            $data = [implode(', ', $buffer), $this->sql_what_args_buffer];
        }

        if ($clear) {
            $this->sql_what_string_buffer = [];
            $this->sql_what_args_buffer = [];
        }

        return $data;
    }

    /**
     * @param bool $clear
     * @return array
     */
    protected function createParamsOrder(bool $clear = false): array
    {
        $orderData = $this->sql_order_string_buffer;

        if ($clear) {
            $this->sql_order_string_buffer = [];
        }

        return $orderData;
    }

    /**
     * @param bool $clear
     * @return array
     */
    protected function createParamsJoin(bool $clear = false): array
    {
        $data = [null, null];

        if ($buffer = array_filter($this->sql_join_string_buffer)) {
            $data = [implode(PHP_EOL, $buffer), $this->sql_join_args_buffer];
        }

        if ($clear) {
            $this->sql_join_string_buffer = [];
            $this->sql_join_args_buffer = [];
        }

        return $data;
    }

    /**
     * @param bool $clear
     * @return array
     */
    protected function createParamsGroup(bool $clear = false): array
    {
        $data = [null, null];

        if ($buffer = array_filter($this->sql_group_string_buffer)) {
            $data = [implode(PHP_EOL, $buffer), $this->sql_group_args_buffer];
        }

        if ($clear) {
            $this->sql_group_string_buffer = [];
            $this->sql_group_args_buffer = [];
        }

        return $data;
    }

    /**
     * @param bool $clear
     * @return array
     */
    protected function createParamsFrom(bool $clear = false): array
    {
        $data = [null, null];

        if ($buffer = array_filter($this->sql_from_string_buffer)) {
            $data = [implode(', ', $buffer), $this->sql_from_args_buffer];
        }

        if ($clear) {
            $this->sql_from_string_buffer = [];
            $this->sql_from_args_buffer = [];
        }

        return $data;
    }

    /**
     * @param bool $clear
     * @return array
     */
    protected function createParamsWhere(bool $clear = false): array
    {
        $data = [null, null];

        if ($buffer = array_filter($this->sql_where_string_buffer)) {
            $data = [implode(' AND ', $buffer), $this->sql_where_args_buffer];
        }

        if ($clear) {
            $this->sql_where_string_buffer = [];
            $this->sql_where_args_buffer = [];
        }

        return $data;
    }

    /**
     * @param bool $clear
     * @return array
     */
    protected function createdParamsLimit(bool $clear = false): array
    {
        $data = $this->sql_limit_args_buffer;

        if ($clear) {
            $this->sql_limit_args_buffer = [];
        }

        return $data;
    }

    /**
     * В случае, если в Request не определены параметры сортировки, то определяем их
     * согласно значениям по умолчанию.
     */
    private function declareDefaultSortOptions()
    {
        if ($this->request->getRequest('field_name', Request::SANITIZE_STRING) === null) {
            $this->request->getRequest()->setData([
                'field_name' => array_search($this->default_order_options['field_name'], $this->order_options)
                    ?: $this->default_order_options['field_name']
            ]);
        }

        if ($this->request->getRequest('sort_order', Request::SANITIZE_STRING) === null) {
            $this->request->getRequest()->setData([
                'sort_order' => $this->default_order_options['sort_order']
            ]);
        }
    }
}