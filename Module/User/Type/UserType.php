<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Type;

use Krugozor\Framework\Type\TypeInterface;

class UserType implements TypeInterface
{
    /** @var string */
    public const TYPE_PRIVATE_PERSON = 'private_person';

    /** @var string */
    public const TYPE_COMPANY = 'company';

    /** @var string[] */
    public const TYPES = [
        self::TYPE_PRIVATE_PERSON => 'Частное лицо',
        self::TYPE_COMPANY => 'Компания',
    ];

    /** @var string|null */
    protected ?string $user_type;

    /**
     * @param string|null $user_type
     */
    public function __construct(?string $user_type = null)
    {
        $this->user_type = $user_type;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->user_type;
    }

    /**
     * @return string|null
     */
    public function getAsText(): ?string
    {
        return self::TYPES[$this->user_type] ?? null;
    }

    /**
     * @return bool
     */
    public function isCompany(): bool
    {
        return $this->user_type === self::TYPE_COMPANY;
    }
}