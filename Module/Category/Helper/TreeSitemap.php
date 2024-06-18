<?php

namespace Krugozor\Framework\Module\Category\Helper;

use Krugozor\Cover\CoverArray;

class TreeSitemap extends Subcategories
{
    /**
     * @param CoverArray $tree
     * @return string
     */
    protected function createSubcategories(CoverArray $tree): string
    {
        if (!$tree->count()) {
            return '';
        }

        $str = '<ul>';

        foreach ($tree as $category) {
            $str .= '<li><a href="' . $this->prefix_url . $category->getUrl() . '">' .
                $category->getName() . '</a>';

            $str .= $this->createSubcategories($category->getTree()) . '</li>';
        }

        $str .= '</ul>';

        return $str;
    }
}