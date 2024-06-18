<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Advert\Type;

use Krugozor\Framework\Type\TypeInterface;

/**
 * Валюта объявления
 */
class PriceType implements TypeInterface
{
    /** @var string */
    public const TYPE_RUB = 'RUB';

    /** @var string */
    public const TYPE_USD = 'USD';

    /** @var string */
    public const TYPE_EUR = 'EUR';

    /** @var string[][] */
    public const TYPES = [
        self::TYPE_RUB => array('рубли', '₽'),
        self::TYPE_USD => array('доллары США', '$'),
        self::TYPE_EUR => array('евро', '€')
    ];

    /**
     * @var string|null
     */
    protected ?string $price_type = null;

    /**
     * @param string|null $price_type
     */
    public function __construct(?string $price_type = null)
    {
        if ($price_type) {
            $this->price_type = strtoupper($price_type);
        }
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->price_type;
    }

    /**
     * @return null|string
     */
    public function getAsSymbol(): ?string
    {
        return
            isset(self::TYPES[$this->price_type])
            ? self::TYPES[$this->price_type][1]
            : null;
    }
}