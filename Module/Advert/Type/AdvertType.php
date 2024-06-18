<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Advert\Type;

use Krugozor\Framework\Type\TypeInterface;

/**
 * Тип объявления
 */
class AdvertType implements TypeInterface
{
    public const TYPE_SALE = 'sale';
    public const TYPE_BUY = 'buy';
    public const TYPE_RENT_SALE = 'rent_sale';
    public const TYPE_RENT_BUY = 'rent_buy';

    public const TYPE_JOB_FULL = 'job_full';
    public const TYPE_JOB_PART = 'job_part';
    public const TYPE_JOB_PROJECT = 'job_project';
    public const TYPE_JOB_VOLUNTEER = 'job_volunteer';
    public const TYPE_JOB_PROBATION = 'job_probation';

    /** @var string|null */
    protected ?string $type = null;

    /**
     * Типы возможных предложений.
     *
     * @var array
     */
    public const ADVERT_TYPES = [
        self::TYPE_SALE => 'Предложения',
        self::TYPE_BUY => 'Спрос',
        self::TYPE_RENT_SALE => 'Предложения аренды',
        self::TYPE_RENT_BUY => 'Спрос аренды',

        self::TYPE_JOB_FULL => 'Полная занятость',
        self::TYPE_JOB_PART => 'Частичная занятость',
        self::TYPE_JOB_PROJECT => 'Проектная работа',
        self::TYPE_JOB_VOLUNTEER => 'Волонтерство',
        self::TYPE_JOB_PROBATION => 'Стажировка',
    ];

    /**
     * Типы возможных предложений для страницы добавления объявления.
     *
     * @var array
     */
    public const ADVERT_TYPES_FOR_ADD = [
        self::TYPE_SALE => 'Предложение',//Продам
        self::TYPE_BUY => 'Спрос',//Куплю
        self::TYPE_RENT_SALE => 'Предложение аренды',//Предлагаю в аренду
        self::TYPE_RENT_BUY => 'Спрос аренды',//Возьму в аренду

        self::TYPE_JOB_FULL => 'Полная занятость',
        self::TYPE_JOB_PART => 'Частичная занятость',
        self::TYPE_JOB_PROJECT => 'Проектная работа',
        self::TYPE_JOB_VOLUNTEER => 'Волонтерство',
        self::TYPE_JOB_PROBATION => 'Стажировка',
    ];

    /**
     * @param string|null $advert_type
     */
    public function __construct(?string $advert_type = null)
    {
        $this->type = $advert_type;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->type;
    }

    /**
     * Возвращает значение типа как человекопонятную строку.
     *
     * @return string|null
     */
    public function getAsText(): ?string
    {
        return self::ADVERT_TYPES_FOR_ADD[$this->type] ?? null;
    }
}