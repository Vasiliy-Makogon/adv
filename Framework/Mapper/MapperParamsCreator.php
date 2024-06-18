<?php

declare(strict_types=1);

namespace Krugozor\Framework\Mapper;

use Krugozor\Cover\CoverArray;

/**
 * Класс формирования SQL запросов или их частей из массива параметров @see MapperParamsCreator::__construct.
 * Массив параметров представляет собой ассоциативный массив, где ключи являются
 * условиями и элементами SQL запроса, а значения - данные для подстановки в SQL.
 *
 * Возможныфе ключи массива параметров:
 *
 * 'what', 'from', 'join', 'where' могут быть вида:
 *    'string'
 * или
 *    ['string' => [scalar, ...], 'string' => scalar, ...]
 *
 * 'order' должен быть вида:
 *    ['field1' => 'ORDER', 'field2' => 'ORDER', ...]
 *
 * 'limit' должен быть вида:
 *    [int, int]
 *
 */
class MapperParamsCreator
{
    /** @var string Ключ результирующего массива, содержащий все данные SQL-запроса под систему заполнителей механизма БД */
    public const KEY_ARGS = 'args';

    /** @var string */
    public const KEY_WHAT = 'what';

    /** @var string */
    public const KEY_FROM = 'from';

    /** @var string */
    public const KEY_JOIN = 'join';

    /** @var string */
    public const KEY_WHERE = 'where';

    /** @var string */
    public const KEY_GROUP = 'group';

    /** @var string */
    public const KEY_ORDER = 'order';

    /** @var string */
    public const KEY_LIMIT = 'limit';

    /** @var array Аргументы заполнителей SQL-запросов */
    private array $args = [];

    /** @var CoverArray */
    private CoverArray $params;

    /**
     * @param array|CoverArray $params
     */
    public function __construct(array|CoverArray $params = [])
    {
        $this->params = new CoverArray($params);
    }

    /**
     * @return array
     */
    public function createSqlQueryString(): array
    {
        $params = $this->createParams();
        array_pop($params);

        return [implode(PHP_EOL, $params), $this->args];
    }

    /**
     * @param bool $addKeyword
     * @return array
     */
    public function createParams(bool $addKeyword = true): array
    {
        return [
            self::KEY_WHAT => $this->createSqlWhatString(
                $this->params->item(self::KEY_WHAT)
            ),
            self::KEY_FROM => $this->createSqlFromString(
                $this->params->item(self::KEY_FROM)
            ),
            self::KEY_JOIN => $this->createSqlJoinString(
                $this->params->item(self::KEY_JOIN)
            ),
            self::KEY_WHERE => $this->createSqlWhereString(
                $this->params->item(self::KEY_WHERE), $addKeyword
            ),
            self::KEY_GROUP => $this->createSqlGroupByString(
                $this->params->item(self::KEY_GROUP), $addKeyword
            ),
            self::KEY_ORDER => $this->createSqlOrderByString(
                $this->params->item(self::KEY_ORDER), $addKeyword
            ),
            self::KEY_LIMIT => $this->createSqlLimitString(
                $this->params->item(self::KEY_LIMIT), $addKeyword
            ),
            self::KEY_ARGS => $this->args
        ];
    }

    /**
     * @param mixed $condition
     * @param bool $addKeyword
     * @return string
     */
    public function createSqlWhatString(mixed $condition, bool $addKeyword = true): string
    {
        $result = match (true) {
            is_string($condition) => $condition,
            $condition instanceof CoverArray => $this->createCondition($condition)->implode(', '),
            default => ''
        };

        return $result ? ($addKeyword ? 'SELECT ' : '') . $result : $result;
    }

    /**
     * @param mixed $condition
     * @param bool $addKeyword
     * @return string
     */
    public function createSqlFromString(mixed $condition, bool $addKeyword = true): string
    {
        $result = match (true) {
            is_string($condition) => $condition,
            $condition instanceof CoverArray => $this->createCondition($condition)->implode(', '),
            default => ''
        };

        return $result ? ($addKeyword ? 'FROM ' : '') . $result : $result;
    }

    /**
     * @param mixed $condition
     * @return string
     */
    public function createSqlJoinString(mixed $condition): string
    {
        return match (true) {
                is_string($condition) => $condition,
                $condition instanceof CoverArray => $this->createCondition($condition)->implode(PHP_EOL),
                default => ''
            };
    }

    /**
     * @param mixed $condition
     * @param bool $addKeyword
     * @return string
     */
    public function createSqlWhereString(mixed $condition, bool $addKeyword = true): string
    {
        $result = match (true) {
            is_string($condition) => $condition,
            $condition instanceof CoverArray => $this->createCondition($condition)->implode(' AND '),
            default => ''
        };

        return $result ? ($addKeyword ? 'WHERE ' : '') . $result : $result;
    }

    /**
     * @param mixed $condition
     * @param bool $addKeyword
     * @return string
     */
    public function createSqlGroupByString(mixed $condition, bool $addKeyword = true): string
    {
        $result = match (true) {
            is_string($condition) => $condition,
            $condition instanceof CoverArray => $this->createCondition($condition)->implode(', '),
            default => ''
        };

        return $result ? ($addKeyword ? 'GROUP BY ' : '') . $result : $result;
    }

    /**
     * @param mixed $condition
     * @param bool $addKeyword
     * @return string
     */
    public function createSqlOrderByString(mixed $condition, bool $addKeyword = true): string
    {
        $result = match (true) {
                is_string($condition) => $condition,
                $condition instanceof CoverArray => $this->createOrderByCondition($condition)->implode(', '),
                default => ''
            };

        return $result ? ($addKeyword ? 'ORDER BY ' : '') . $result : $result;
    }

    /**
     * @param CoverArray|null $condition
     * @param bool $addKeyword
     * @return string
     */
    public function createSqlLimitString(?CoverArray $condition, bool $addKeyword = true): string
    {
        if (is_null($condition) || !$condition->count()) {
            return '';
        }

        $countConditions = $condition->count();

        if ($condition->item(0) === null || !($countConditions <= 2 && $countConditions >= 1)) {
            return '/* limit is not valid ! */';
        }

        $this->args = array_merge($this->args, $condition->getDataAsArray());

        return ($addKeyword ? 'LIMIT ' : '') . implode(', ', array_fill(0, $countConditions, '?i'));
    }

    /**
     * @param mixed $condition
     * @return CoverArray
     */
    protected function createCondition(mixed $condition): CoverArray
    {
        return $condition->mapAssociative(function ($sql, $arg) {
            if (is_iterable($arg)) {
                foreach ($arg as $value) {
                    if (is_object($value)) { // зачем тут объект?
                        $value = $value->getValue();
                    }
                    $this->args[] = $value;
                }
            } else if (is_scalar($arg)) {
                $this->args[] = $arg;
            }

            return $sql;
        });
    }

    /**
     * @param mixed $condition
     * @return CoverArray
     */
    protected function createOrderByCondition(mixed $condition): CoverArray
    {
        return $condition->mapAssociative(function ($field, $order) {
            return "$field $order";
        });
    }
}