<?php

declare(strict_types=1);

namespace Krugozor\Framework\Helper;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Statical\Strings;

class Format
{
    /**
     * Создаёт строку для meta description длинной $length символов.
     *
     * @param string $in
     * @param int $length
     * @return string
     */
    public static function description(string $in, int $length = 300): string
    {
        $in = strip_tags($in);
        $in = static::getPreviewStr($in, $length);
        return static::outPut($in, false, true);
    }

    /**
     * @param string $string
     * @param int $mode
     * @return string
     * @see html_entity_decode()
     */
    public static function decode(string $string, int $mode = ENT_QUOTES): string
    {
        return html_entity_decode($string, $mode, 'UTF-8');
    }

    /**
     * @param string|null $in
     * @param int $mode
     * @return string
     * @see htmlspecialchars()
     */
    public static function hsc(?string $in, int $mode = ENT_COMPAT | ENT_HTML401): string
    {
        return htmlspecialchars((string) $in, $mode);
    }

    /**
     * @param string $in
     * @return string
     */
    public static function cleanHtml(string $in): string
    {
        $in = preg_replace("/(\r?\n)+/", '', $in);
        $in = preg_replace("/\t+/", '', $in);
        $in = preg_replace("/ +/", ' ', $in);
        return preg_replace("/> </", '><', $in);
    }

    /**
     * @param string $in
     * @return string
     */
    public static function cleanCss(string $in): string
    {
        // комментарии
        $in = preg_replace("~/\*(.*?)\*/~usi", '', $in);
        $in = preg_replace("/(\r?\n)+/", '', $in);
        $in = preg_replace("/(\t+| +)/", ' ', $in);
        // убирает пробелы в выражении " {" или "} "
        $in = preg_replace("/\s*(\{|\})\s*/", '$1', $in);
        // убирает пробелы в выражениях:
        // "carousel > ul > li"
        // "html, body, div, span"
        // " --var"
        $in = preg_replace("/\s*(>|,|--)\s*/", '$1', $in);
        // замена выражения "padding : 10px 10px;" или
        // background:linear-gradient(to bottom, #336699 30%, #395773 100%)
        //$in = preg_replace("/(\S+)\s*:\s*(.+?)\s*(;|\))/ui", '$1:$2$3', $in);
        // убирает пробелы после знака ";"
        return preg_replace("/(;)\s*(:?[^;]+?)/", '$1$2', $in);
    }

    /**
     * Расстановка пробелов после знаков пунктуации.
     *
     * @param string $str
     * @return string
     */
    public static function spaceAfterPunctuation(string $str): string
    {
        return preg_replace('~(\S)([.,:;?!])(\S)~', '$1$2 $3', $str);
    }

    /**
     * @param string $in
     * @return string
     */
    public static function nl2space(string $in): string
    {
        return preg_replace("/(\r?\n)+/", ' ', $in);
    }

    /**
     * Формирование значений для JavaScript-переменных.
     *
     * @param string строка, возможно с параметрами как у Strings::createMessageFromParams
     * @param array $params массив с параметрами как у Strings::createMessageFromParams
     * @return string
     * @see Strings::createMessageFromParams()
     */
    public static function js(string $str, array $params = []): string
    {
        $str = Strings::createMessageFromParams($str, $params, false);
        $str = json_encode(
            $str,
            JSON_INVALID_UTF8_IGNORE |
            JSON_HEX_TAG |
            JSON_HEX_APOS |
            JSON_HEX_AMP |
            JSON_HEX_QUOT
        );

        return str_replace("\\\\n", '\n', $str);
    }

    /**
     * Функция "красиво" обрезает строку $str до максимум $num символов,
     * если она больше числа $num и добавляет строку $postfix
     * в конец строки. Обрезание строки идет после последнего символа $char в строке.
     *
     * @param string $str обрабатываемая строка
     * @param int $num максимальное количество символов
     * @param string $postfix строка, дописываемая к обрезанной строке
     * @param string $char
     * @return string
     */
    public static function getPreviewStr(string $str, int $num = 300, string $postfix = '…', string $char = ' '): string
    {
        if (mb_strlen($str) > $num) {
            $str = mb_substr($str, 0, $num);
            $str = mb_substr($str, 0, mb_strrpos($str, $char));
            $str .= $postfix;
        }

        return $str;
    }

    /**
     * Склонение существительных с числительными.
     * Функция принимает число $n и три строки - разные формы произношения измерения величины.
     * Например: triumviratForm(100, "рубль", "рубля", "рублей") вернёт "рублей".
     *
     * @param int $value величина
     * @param array|CoverArray $triumvirat_forms
     * @return string
     */
    public static function triumviratForm(int $value, array|CoverArray $triumvirat_forms): string
    {
        $value = abs($value) % 100;
        $value1 = $value % 10;

        if ($value > 10 && $value < 20) {
            return $triumvirat_forms[2];
        } elseif ($value1 > 1 && $value1 < 5) {
            return $triumvirat_forms[1];
        } elseif ($value1 == 1) {
            return $triumvirat_forms[0];
        }

        return $triumvirat_forms[2];
    }

    /**
     * Формирование числа разделенного пробелом для приятной визуализации.
     *
     * @param float $value
     * @return string
     */
    public static function prettyNumber(float $value): string
    {
        return number_format($value, 0, ".", " ");
    }

    /**
     * Вывод пользовательского ввода в HTML.
     *
     * @param string|null $value
     * @param bool $useNl2Br true, если использовать nl2br
     * @param bool $nl2space true, если заменять символы новой строки на пробел
     * @return string
     */
    public static function outPut(?string $value, bool $useNl2Br = true, bool $nl2space = false): string
    {
        $value = trim((string) $value);
        $value = self::decode($value);
        $value = self::hsc($value);

        if ($useNl2Br) {
            $value = nl2br($value);
        }
        if ($nl2space) {
            $value = self::nl2space($value);
        }

        return $value;
    }
}