<?php

namespace Krugozor\Framework\Module\Category\Helper;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Module\Category\Model\Category;
use Krugozor\Framework\Module\User\Model\AbstractTerritory;
use Krugozor\Framework\Module\User\Model\Country;

/**
 * Создание простого, древовидного списка подкатегорий раздела на основании дерева.
 */
class Subcategories
{
    /** @var AbstractTerritory */
    protected AbstractTerritory $territory;

    /** @var Category|null Текущая категория */
    protected ?Category $current_category = null;

    /**
     * @param CoverArray $tree Дерево категорий
     */
    public function __construct(protected CoverArray $tree)
    {
    }

    /**
     * @return string
     */
    public function getHtml(): string
    {
        return $this->createSubcategories($this->tree);
    }

    /**
     * @param AbstractTerritory $territory
     * @return $this
     */
    public function setCurrentTerritory(AbstractTerritory $territory): self
    {
        $this->territory = $territory;

        return $this;
    }

    /**
     * Устанавливает текущую категорию, если необходимо в списке категорий выделить её особо.
     *
     * @param Category $current_category
     * @return $this
     */
    public function setCurrentCategory(Category $current_category): self
    {
        $this->current_category = $current_category;

        return $this;
    }

    /**
     * Создает список категорий на основе дерева категорий $tree.
     *
     * @param CoverArray $tree дерево категорий
     * @param bool $subcategoryRun флаг, указывающий, работает ли рекурсия в рамках субкатегорий
     * @return string
     */
    protected function createSubcategories(CoverArray $tree, bool $subcategoryRun = false): string
    {
        if (!$tree->count()) {
            return '';
        }

        $str = '<ul>';
        $template = '<li>%s<div>%s<!--noindex--><span class="count">[%s]</span><!--/noindex-->%s</div></li>';

        /** @var Category $category */
        foreach ($tree as $category) {
            $categoryAdvertCount = $category->findAdvertCount($this->territory);

            $htmlLink = $categoryAdvertCount ? sprintf(
                '%s<a title="%s объявления" href="%s/categories%s">%s<span class="%s"> в %s</span></a>%s',
                (!$subcategoryRun ? '<h2>' : ''),
                sprintf('%s в %s', $category->getName(), $this->territory->getNameRu2()),
                $this->territory->getUrl(),
                $category->getUrl(),
                $category->getName(),
                (!$subcategoryRun ? 'show_territory' : 'hide_territory'),
                $this->territory->getNameRu2(),
                (!$subcategoryRun ? '</h2>' : ''),
            ) : sprintf(
                '%s%s%s',
                (!$subcategoryRun ? '<h2 class="show_territory">' : '<span class="show_territory">'),
                sprintf('%s<span class="hide_territory"> в %s</span>', $category->getName(), $this->territory->getNameRu2()),
                (!$subcategoryRun ? '</h2>' : '</span>')
            );

            $str .= sprintf(
                $template,
                ($subcategoryRun ? '<svg class="icon-right-arrow"><use href="/svg/local/sprite.svg#icon-right-arrow"></use></svg>' : ''),
                $htmlLink,
                $categoryAdvertCount,
                $this->createSubcategories($category->getTree(), true)
            );
        }

        $str .= '</ul>';

        return $str;
    }
}