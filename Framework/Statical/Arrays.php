<?php

declare(strict_types=1);

namespace Krugozor\Framework\Statical;

/**
 * Класс-обертка над функциями для работы с массивами.
 */
class Arrays
{
    /**
     * Аналог array_unshift, но ключами становятся не числовые индексы,
     * а значение $key.
     *
     * @param array $arr
     * @param string $key
     * @param mixed $val
     * @return int
     */
    public static function array_unshift_assoc(array &$arr, string $key, mixed $val): int
    {
        $arr = array_reverse($arr, true);
        $arr[$key] = $val;
        $arr = array_reverse($arr, true);
        return count($arr);
    }

    /**
     * array_shift для ассоциативных массивов.
     *
     * @param array $arr
     * @return array
     */
    public static function array_kshift(array &$arr): array
    {
        list($k) = array_keys($arr);
        $r = array($k => $arr[$k]);
        unset($arr[$k]);
        return $r;
    }
}