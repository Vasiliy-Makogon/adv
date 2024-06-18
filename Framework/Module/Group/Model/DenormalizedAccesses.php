<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Group\Model;

class DenormalizedAccesses
{
    /**
     * @var array
     */
    private array $denormalized_accesses;

    /**
     * @param array $denormalized_accesses
     */
    public function __construct(array $denormalized_accesses)
    {
        $this->denormalized_accesses = $denormalized_accesses;
    }

    /**
     * Возвращает true, если у контроллера с ключом $controller_key модуля с ключом $module_key
     * стоит значение `1` как право доступа, false - в противном случае.
     *
     * @param string $module_key
     * @param string $controller_key
     * @return bool
     */
    public function checkAccess(string $module_key, string $controller_key): bool
    {
        return !empty($this->denormalized_accesses[$module_key][$controller_key]);
    }
}