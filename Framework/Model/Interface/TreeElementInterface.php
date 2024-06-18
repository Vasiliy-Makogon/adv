<?php

namespace Krugozor\Framework\Model\Interface;

use Krugozor\Cover\CoverArray;

/**
 * Интерфейс элемента дерева
 */
interface TreeElementInterface
{
    /**
     * @return CoverArray
     */
    public function getTree(): CoverArray;

    /**
     * @param CoverArray $tree
     * @return static
     */
    public function setTree(CoverArray $tree): static;
}