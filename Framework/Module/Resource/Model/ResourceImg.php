<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Resource\Model;

class ResourceImg extends AbstractResource
{
    /** @var string[] */
    public const RESOURCE_INFO = [
        'gif' => 'image/gif',
        'png' => 'image/png',
        'jpeg' => 'image/jpeg',
        'jpg' => 'image/jpeg',
        'jpe' => 'image/jpeg',
    ];
}