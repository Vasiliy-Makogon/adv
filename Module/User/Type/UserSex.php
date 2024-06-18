<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Type;

use Krugozor\Framework\Type\TypeInterface;

class UserSex implements TypeInterface
{
    /** @var string */
    public const TYPE_MALE = 'M';

    /** @var string */
    public const TYPE_FEMALE = 'F';

    /** @var string[] */
    public const TYPES = [
        self::TYPE_MALE => 'Мужчина',
        self::TYPE_FEMALE => 'Женщина'
    ];

    /** @var string|null */
    protected ?string $sex;

    /**
     * @param string|null $sex
     */
    public function __construct(?string $sex)
    {
        $this->sex = $sex;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->sex;
    }

    /**
     * @return string|null
     */
    public function getAsText(): ?string
    {
        return self::TYPES[$this->sex] ?? null;
    }
}