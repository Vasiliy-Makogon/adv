<?php

declare(strict_types=1);

namespace Krugozor\Framework\Statical;

/**
 * Класс-обертка над функциями для работы с числами.
 */
class Numeric
{
    /**
     * Паттерн для поиска десятичных знаковых чисел в строке.
     *
     * @var string
     */
    public static string $pattern_sign_search = '~(?:[+\-]?[0-9]+)~';

    /**
     * Паттерн для точного определения десятичного знакового числа в строке.
     *
     * @var string
     */
    protected static string $pattern_sign = '~^(?:[+\-]?[0-9]+)$~';

    /**
     * Паттерн для точного определения десятичного беззнакового числа в строке.
     *
     * @var string
     */
    protected static string $pattern_unsigned = '~^(?:[0-9]+)$~';

    /**
     * Проверяет, является ли значение десятичным числом.
     *
     * @param mixed $value проверяемое значение
     * @param boolean если $signed в true, то число проверяется как знаковое, иначе - как беззнаковое.
     * @return bool
     */
    public static function isDecimal(mixed $value, bool $signed = false): bool
    {
        $pattern = $signed ? self::$pattern_sign : self::$pattern_unsigned;

        return preg_match($pattern, (string) $value, $matches) === 1;
    }

    /**
     * Извлечение десятичного числа из переменной.
     * Фактически, метод проверяет (и в случае успеха, возвращает) в виде строки бесконечное число,
     * что было бы не возможно сделать с помощью приведения к (int)
     *
     * @param mixed $value значение
     * @param boolean если $signed в true, то число проверяется как знаковое, иначе - как беззнаковое.
     * @return string|null
     */
    public static function detectAndExtractDecimal(mixed $value, bool $signed = false): ?string
    {
        $pattern = $signed ? self::$pattern_sign : self::$pattern_unsigned;

        if (preg_match($pattern, (string) $value, $matches) === 1) {
            return $matches[0];
        }

        return null;
    }
}