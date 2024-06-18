<?php

declare(strict_types=1);

namespace Krugozor\Framework\Validator;

use Krugozor\Framework\Mapper\AbstractMapper;
use Krugozor\Framework\Model\AbstractModel;

/**
 * Абстрактный класс конкретного валидатора.
 *
 * Валидатор модели посредством метода validate() возвращает false в случае,
 * если имеется факт ошибки и true в обратном случае.
 */
abstract class AbstractValidator
{
    /**
     * Ключ ошибки, описанный в файлах i18n конкретного модуля,
     * и установленный в базовый валидатор через \Krugozor\Framework\Validator::__construct()
     *
     * @var string
     */
    protected string $error_key;

    /**
     * Массив вида ключ => значение, где ключ - заполнитель строки описания ошибки $this->error_key
     * См. пример валидатор @see HasBadEmailValidator
     *
     * @var array
     */
    protected array $error_params = [];

    /**
     * Обрывать ли проверку значения, если текущий валидатор обнаружил ошибку.
     *
     * @var boolean
     */
    protected bool $break = true;

    /**
     * @var AbstractMapper
     */
    protected AbstractMapper $mapper;

    /**
     * @param mixed $value проверяемое значение или массив или объект, из которого берётся проверяемое значение
     */
    public function __construct(protected mixed $value)
    {}

    /**
     * Производит валидацию значения.
     * Возвращает false в случае обнаружения ошибки, true в обратном случае.
     *
     * @return bool
     */
    abstract public function validate(): bool;

    /**
     * @param AbstractMapper $mapper
     * @return static
     */
    final public function setMapper(AbstractMapper $mapper): static
    {
        $this->mapper = $mapper;

        return $this;
    }

    /**
     * Возвращает ошибку текущего валидатора.
     *
     * @return array
     */
    final public function getError(): array
    {
        return [$this->error_key, $this->error_params];
    }

    /**
     * @return bool
     */
    final public function getBreak(): bool
    {
        return $this->break;
    }

    /**
     * @param bool $break
     * @return self
     */
    final public function setBreak(bool $break): self
    {
        $this->break = $break;

        return $this;
    }
}