<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Framework\Statical\Strings;
use RuntimeException;

class AbstractBadWordsValidator extends AbstractValidator
{
    /**
     * @var array
     */
    protected static array $letters = [
        ['а', 'е', 'о', 'с', 'х', 'м', 'к', 'р'], ['a', 'e', 'o', 'c', 'x', 'm', 'k', 'p']
    ];

    /**
     * Кэш слов с преобразованием (см. self::createFakeWords) для каждого конкретного
     * класса по ключу - имени класса, пример:
     *
     * [
     *   Krugozor\Framework\Validator\ProfanityWordsValidator => [слово, слово, ...],
     *   Krugozor\Framework\Module\Advert\Validator\StopWordsValidator => [слово, слово, ...],
     * ]
     *
     * @var array
     */
    protected static array $cacheWords = [];

    /**
     * @param mixed $value
     */
    public function __construct(mixed $value)
    {
        if (!isset(static::$words) || !isset($this->error_key)) {
            throw new RuntimeException(sprintf(
                '%s: Не объявлены необходимые свойства в дочернем классе ',
                __METHOD__ ,
                get_class($this)
            ));
        }

        parent::__construct($value);
    }

    /**
     * Возвращает false (факт ошибки), если найдено объявление с плохими словами в строке.
     *
     * @inheritdoc
     */
    public function validate(): bool
    {
        if (!$this->value) {
            return true;
        }

        $texts = preg_split('~\s~', $this->value);

        array_walk($texts, function (&$val) {
            $val = trim($val, '.,:;!?');
            $val = mb_strtolower($val);
        });

        $texts = array_filter($texts, function($v) {
            return mb_strlen($v) >= 3;
        });

        $className = get_class($this);
        if (!isset(self::$cacheWords[$className])) {
            self::$cacheWords[$className] = array_merge(static::$words, self::createFakeWords(static::$words));
        }

        return ! (bool) array_intersect($texts, self::$cacheWords[$className]);
    }

    /**
     * Заменяет русские буквы на английские поочередно и все сразу.
     *
     * @param array $words
     * @return array
     */
    public static function createFakeWords(array $words): array
    {
        $data = [];
        foreach ($words as $word) {
            $tmp = [];
            foreach (self::$letters[0] as $key => $letter) {
                $offset = 0;
                while (($position = mb_strpos($word, $letter, $offset)) !== false) {
                    $tmp[] = Strings::mb_substr_replace($word, self::$letters[1][$key], $position, 1);
                    $offset = $position + 1;
                }
            }
            $tmp[] = str_replace(self::$letters[0], self::$letters[1], $word);
            $data = array_merge($data, array_unique($tmp));
        }

        return $data;
    }
}