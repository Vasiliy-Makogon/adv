<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Resource\Model;

class ResourceFont extends AbstractResource
{
    /** @var string[] */
    public const RESOURCE_INFO = [
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'ttf' => 'font/ttf',
    ];
}