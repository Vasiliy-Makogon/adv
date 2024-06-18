<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Advert;

class PaymentActionsEnum
{
    /** @var int Размещение объявления */
    public const ACTION_ACTIVATE = 1;

    /** @var int Выделение объявления */
    public const ACTION_TOP = 2;

    /** @var int Спецпредложение */
    public const ACTION_SPECIAL = 3;

    /**
     * @return int[]
     */
    public static function all(): array
    {
        return [
            self::ACTION_ACTIVATE,
            self::ACTION_TOP,
            self::ACTION_SPECIAL,
        ];
    }
}