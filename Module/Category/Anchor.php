<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Category;

use Krugozor\Framework\AbstractAnchor;

class Anchor extends AbstractAnchor
{
    /**
     * @inheritDoc
     */
    public static function getPath(string $path = null): string
    {
        return dirname(__FILE__) . self::addPath($path);
    }
}