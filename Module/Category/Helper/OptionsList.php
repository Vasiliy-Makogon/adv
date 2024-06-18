<?php

namespace Krugozor\Framework\Module\Category\Helper;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Helper\Form;

/**
 * Отличается от OptionsListWithOptgroup тем, что не создает optgroup.
 */
class OptionsList extends OptionsListWithOptgroup
{
    /**
     * @param CoverArray $tree
     * @return CoverArray
     */
    protected function createOptionsList(CoverArray $tree): CoverArray
    {
        $categories = new CoverArray();

        foreach ($tree as $category) {
            $option = Form::inputOption($category->getId(), $category->getNameForOptionElement());
            $categories->append($option);

            if ($category->getTree() && $category->getTree()->count()) {
                foreach ($this->createOptionsList($category->getTree()) as $element) {
                    $categories->append($element);
                }
            }
        }

        return $categories;
    }
}