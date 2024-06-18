<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Resource\Model;

use Krugozor\Framework\Module\Resource\ResourceCompileTrait;

class ResourceCss extends AbstractResource
{
    use ResourceCompileTrait;

    /** @var string[] */
    public const RESOURCE_INFO = [
        'css' => 'text/css; charset=utf-8',
    ];

    /** @var string */
    public const RESOURCE_EXTENSION = 'css';
}