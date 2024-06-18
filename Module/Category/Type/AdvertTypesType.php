<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Category\Type;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Module\Advert\Type\AdvertType;
use Krugozor\Framework\Type\TypeInterface;

class AdvertTypesType implements TypeInterface
{
    /** @var CoverArray */
    private CoverArray $types;

    /**
     * @param string|null $types
     */
    public function __construct(?string $types = null)
    {
        $this->types = CoverArray::fromExplode(',', (string) $types)
            ->unique()
            ->filter()
            ->map(function (string $value) {
                return new AdvertType(trim($value));
            });
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->types->map(function (AdvertType $advertType) {
            return $advertType->getValue();
        })->implode(',');
    }

    /**
     * @return CoverArray
     */
    public function getAdvertTypes(): CoverArray
    {
        return $this->types;
    }
}