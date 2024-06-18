<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

/**
 * Проверка на наличие в объявлении русского текста.
 */
class RussianTextsValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'EMPTY_RUSSIAN_WORDS';

    /**
     * Возвращает false (факт ошибки), если не найден русский текст.
     *
     * @return bool
     */
    public function validate(): bool
    {
        $value = (string) $this->value;
        if (!$value) {
            return true;
        }

        return preg_match('~([а-яё]+)~ui', $value) === 1;
    }
}