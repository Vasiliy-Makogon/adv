<?php

namespace Krugozor\Framework\Module\Prodamus;

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