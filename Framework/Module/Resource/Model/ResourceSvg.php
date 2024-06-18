<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Resource\Model;

class ResourceSvg extends AbstractResource
{
    /** @var string[] */
    public const RESOURCE_INFO = [
        'svg' => 'image/svg+xml; charset=utf-8',
    ];
}