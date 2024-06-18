<?php

declare(strict_types=1);

namespace Krugozor\Framework\Statical;

/**
 * Класс-обертка над функциями для работы со строками.
 */
class Strings
{
    /**
     * @param array $data
     * @return string
     */
    public static function httpBuildQuery(array $data): string
    {
        return http_build_query(array_filter(
            $data, fn(mixed $v): bool => (string) $v !== ''
        ), encoding_type: PHP_QUERY_RFC3986);
    }

    /**
     * Паттерн для поиска URL адреса в тексте.
     *
     * @var string
     */
    public static string $url_pattern_search = "#
        (?:
            (?:https?://(?:www\.)?|(?:www\.))
            (?:\S+)
            (?::[0-9]+)?
            (?:/\S+)*
            [^\s.,'\"]*
        )
        #uxi";

    /**
     * Паттерн для точного определения URL адреса.
     *
     * @var string
     */
    public static string $url_pattern = "#^
        (?:
            https?://(?:www\.)?
            (\S+)
            (:[0-9]+)?
            (/\S+)?
            [^\s.,'\"]
        )
        $#uxi";

    /**
     * Паттерн для точного определения email адреса.
     *
     * @var string
     */
    public static string $email_pattern = "/^[a-zA-Z0-9!#$%&'*+\/=?^_`{|}~-]+
                                    (?:\.[a-zA-Z0-9!#$%&'*+\/=?^_`{|}~-]+)*@
                                    (?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+
                                    (?:[a-zA-Z]{2,6})$/uix";
    /**
     * Паттерн для поиска email адреса в тексте.
     *
     * @var string
     */
    public static string $email_pattern_search = "/[a-zA-Z0-9!#$%&'*+\/=?^_`{|}~-]+
                                          (?:\.[a-zA-Z0-9!#$%&'*+\/=?^_`{|}~-]+)*@
                                          (?:[a-zA-Z0-9](?:[a-zA-Z0-9-]*[a-zA-Z0-9])?\.)+
                                          (?:[a-zA-Z]{2,6})/uix";

    /**
     * Паттерн для вычисления действия со свойством (set- или get-) по имени виртуального метода.
     * См. метод self::camelCaseToProperty()
     *
     * @var string
     */
    public static string $pattern_search_method_name = '/(?<=\w)(?=[A-Z])/';

    /**
     * Преобразование из строки из CamelCase вида в camel_case.
     *
     * @param string $method_name
     * @return string
     */
    public static function camelCaseToProperty(string $method_name): string
    {
        $args = preg_split(self::$pattern_search_method_name, $method_name);

        return strtolower(implode('_', $args));
    }

    /**
     * Возвращает true, если значение пусто - содержит пустую строку,
     * false или null. Применяется для валидаторов, для проверки
     * данных из REQUEST, когда 0 нельзя трактовать как false.
     *
     * @param mixed $string
     * @return bool
     */
    public static function isEmpty(mixed $string): bool
    {
        if (!is_numeric($string)) {
            return empty($string);
        }

        return false;
    }

    /**
     * Проверяет, является ли строка URL адресом.
     *
     * @param string $string
     * @return bool
     */
    public static function isUrl(string $string): bool
    {
        return preg_match(self::$url_pattern, $string) === 1;
    }

    /**
     * Проверяет, является ли строка email адресом.
     *
     * @param string $string
     * @return boolean
     */
    public static function isEmail(string $string): bool
    {
        return preg_match(self::$email_pattern, $string) === 1;
    }

    /**
     * Возвращает уникальную строку длинной $length.
     * Если $length не задана, то длинной в 32 символа.
     *
     * @param int|null $length
     * @return string
     */
    public static function getUnique(?int $length = null): string
    {
        if (is_null($length) || $length > 32) {
            $length = 32;
        }

        return substr(md5(microtime() . rand(1, 10000000)), 0, $length);
    }

    /**
     * Создает строку-сообщение для вывода пользователю.
     * Принимает языковой шаблон $str и массив аргументов вида 'key' => 'value' и заменяет в шаблоне
     * все вставки типа {var_name} на значения из массива аргументов с соответствующими ключами.
     * Применяется для замены в языковых файлах.
     *
     * @param string $string
     * @param array $args ассоциативный массив аргументов
     * @param bool $useHtmlEncode
     * @return string
     */
    public static function createMessageFromParams(string $string, array $args, bool $useHtmlEncode = true): string
    {
        foreach ($args as $key => $value) {
            $value = (string) $value;
            $value = $useHtmlEncode && $value ? htmlspecialchars($value, ENT_QUOTES) : $value;
            $string = str_replace('{' . $key . '}', $value, $string);
        }

        return $string;
    }

    /**
     * Форматирует строку $string в CamelCase-стиль,
     * включая первый символ первого слова.
     *
     * @param string $string
     * @return string
     */
    public static function formatToCamelCaseStyle(string $string): string
    {
        $parts = preg_split('~-|_~', $string);

        if (count($parts) <= 1) {
            return ucfirst($string);
        }

        $str = '';
        foreach ($parts as $part) {
            $str .= ucfirst($part);
        }

        return $str;
    }

    /**
     * Удаляет в начале и конце строки знаки пунктуации.
     *
     * @param string $value
     * @return string
     */
    public static function trimPunctuation(string $value): string
    {
        return trim($value, '.,!?:; ');
    }

    /**
     * Аналог str_replace, но заменяет только последнее искомое слово в строке.
     *
     * @param string $search
     * @param string $replace
     * @param string $subject
     * @return string
     */
    public static function strLastReplace(string $search, string $replace, string $subject): string
    {
        if (($pos = strrpos($subject, $search)) !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }

        return $subject;
    }

    /**
     * @param string $value
     * @return bool|array|string
     */
    public static function string2Utf(string $value): bool|array|string
    {
        return mb_convert_encoding($value, 'UTF-8');
    }

    /**
     * @param string $value
     * @return array|string|null
     */
    public static function prepareBeforeFulltext(string $value): array|string|null
    {
        return preg_replace('/[^а-яёa-z 0-9\-]/iu', '', $value);
    }

    /**
     * @param string $str
     * @return int
     */
    public static function getBytesFromString(string $str): int
    {
        if (!preg_match('/^([\d.]+)([BKMGTPE]?)(B)?$/i', trim($str), $m)) {
            return 0;
        }
        return (int) floor($m[1] * ($m[2] ? (1024**strpos('BKMGTPE', strtoupper($m[2]))) : 1));
    }

    /**
     * Заменяет часть строки string, начинающуюся с символа с порядковым номером start
     * и (необязательной) длиной length, строкой replacement и возвращает результат.
     *
     * @param $string
     * @param $replacement
     * @param $start
     * @param null $length
     * @param null $encoding
     * @return string
     */
    public static function mb_substr_replace($string, $replacement, $start, $length = null, $encoding = null): string
    {
        if ($encoding == null) {
            $encoding = mb_internal_encoding();
        }

        if ($length == null) {
            return mb_substr($string, 0, $start, $encoding) . $replacement;
        } else {
            if ($length < 0) {
                $length = mb_strlen($string, $encoding) - $start + $length;
            }

            return
                mb_substr($string, 0, $start, $encoding) .
                $replacement .
                mb_substr($string, $start + $length, mb_strlen($string, $encoding), $encoding);
        }
    }

    /**
     * Возвращает копию str, в которой все вхождения каждого символа
     * из from были заменены на соответствующий символ в параметре to.
     *
     * @param $str
     * @param $from
     * @param $to
     * @return array|string
     */
    public static function mb_strtr($str, $from, $to): array|string
    {
        return str_replace(mb_str_split($from), mb_str_split($to), $str);
    }

    /**
     * @param $str
     * @return string
     */
    public static function mb_ucfirst($str): string
    {
        return mb_strtoupper(mb_substr($str, 0, 1)) . mb_substr($str, 1);
    }
}