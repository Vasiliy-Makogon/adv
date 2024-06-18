<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Framework\Statical\Numeric;
use Krugozor\Framework\Statical\Strings;

/**
 * Проверка значения на целое число (без плавающей точки).
 */
class DecimalValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'INVALID_UNSIGNED_DECIMAL';

    /**
     * Должно ли проверяемое значение быть знаковым числом.
     * Если значение в true, то число проверяется как знаковое (т.е. может иметь знак),
     * иначе - как беззнаковое.
     *
     * @var boolean
     */
    private bool $signed = false;

    /**
     * @param bool $signed
     * @return $this
     */
    public function setSigned(bool $signed): self
    {
        $this->signed = $signed;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function validate(): bool
    {
        if (Strings::isEmpty($this->value)) {
            // Если передана пустая строка, возвращается true, как будто ошибки нет.
            // Это поведение создано для того, что бы каждый валидатор отвечал лишь за одну проверку.
            // В данном случае, если бы валидатор ругнулся на null или пустую строку,
            // то в модель попала бы информация о некорректном значении, т.е. запись в базу была бы невозможна.
            // Однако, пустая строка, false или null (все эти значения записываются как NULL в СУБД)
            // вполне может ожидаться СУБД.
            // Поэтому, если вместо числа пришла пустая строка, значит это может быть просто NULL, т.е. значения нет.
            return true;
        }

        return !empty(Numeric::isDecimal($this->value, $this->signed));
    }
}