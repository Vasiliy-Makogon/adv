<?php

declare(strict_types=1);

namespace Krugozor\Framework;

abstract class AbstractAnchor
{
    /**
     * @param string|null $path
     * @return string
     */
    abstract static function getPath(?string $path = null): string;

    /**
     * Дополняет физический путь к модулю строкой $path.
     *
     * @param string|null $path
     * @return string
     */
    protected static function addPath(?string $path = null): string
    {
        return $path !== null ? DIRECTORY_SEPARATOR . ltrim($path, '\/') : '';
    }
}