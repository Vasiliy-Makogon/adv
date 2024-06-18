<?php

namespace Krugozor\Framework\Module\Category\Helper;

use Krugozor\Cover\CoverArray;

class BreadCrumbs
{
    /** @var bool Делать ли последний элемент BreadCrumbs ссылкой */
    private bool $last_link = false;

    /** @var bool Добавлять ли перед хлебными крошками символ $this->separator */
    private bool $add_first_separator = true;

    /** @var bool Вывести хлебные крошки как простой текст, без HTML. */
    private bool $only_plain_text = false;

    /** @var string|null Строка, добавляемая в конец строки последнего элемента хлебных крошек. */
    private ?string $postfix_text = null;

    /** @var string|null В какой тег обрамить последний элемент */
    private ?string $last_element_wrap_tag = null;

    /** @var int|null */
    private ?int $position = null;

    /**
     * @param CoverArray $tree Дерево категорий
     * @param string $prefix_url Префикс URL каждого узла, например /russia/leningradskaja/categories
     * @param string $separator Разделитель элементов хлебных крошек
     */
    public function __construct(
        private CoverArray $tree,
        private string $prefix_url = '',
        private string $separator = '&raquo;'
    )
    {
    }

    /**
     * @param int $position
     * @return $this
     */
    public function withSchemaPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    /**
     * @param string $separator
     * @return static
     */
    public function setSeparator(string $separator): static
    {
        $this->separator = $separator;

        return $this;
    }

    /**
     * @param string $last_element_wrap_tag
     * @return static
     */
    public function setLastElementWrapTag(string $last_element_wrap_tag): static
    {
        $this->last_element_wrap_tag = $last_element_wrap_tag;

        return $this;
    }

    /**
     * @return string
     */
    public function getHtml(): string
    {
        return ($this->add_first_separator ? ' ' . $this->separator . ' ' : '') . $this->createBreadCrumbs($this->tree);
    }

    /**
     * @param string $string
     * @return static
     */
    public function setPostfixText(string $string): static
    {
        $this->postfix_text = $string;

        return $this;
    }

    /**
     * @param bool $value
     * @return static
     */
    public function addFirstSeparator(bool $value): static
    {
        $this->add_first_separator = $value;

        return $this;
    }

    /**
     * @param bool $value
     * @return static
     */
    public function setOnlyPlainText(bool $value): static
    {
        $this->only_plain_text = $value;

        return $this;
    }

    /**
     * Если параметр установлен в TRUE, последний элемент хлебных крошек будет ссылкой.
     *
     * @param bool $value
     * @return static
     */
    public function lastElementIsLink(bool $value): static
    {
        $this->last_link = $value;

        return $this;
    }

    /**
     * @param CoverArray $tree
     * @return string
     */
    private function createBreadCrumbs(CoverArray $tree): string
    {
        $str = '';

        if (!$tree->count()) {
            return $str;
        }

        foreach ($tree as $category) {
            if ($category->getTree() && $category->getTree()->count()) {
                if ($this->only_plain_text) {
                    $str .= $category->getName() . ' ' . $this->separator . ' ';
                } else {
                    if ($this->position !== null) {
                        $str .= sprintf(
                            '<span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">' .
                            '<a itemprop="item" href="%s"><span itemprop="name">%s</span>' .
                            '<meta itemprop="position" content="%s">' .
                            '</a></span> %s ',
                            $this->prefix_url . $category->getUrl(),
                            $category->getName(),
                            $this->position++,
                            $this->separator,
                        );
                    } else {
                        $str .= sprintf(
                            '<a href="%s">%s</a> %s ',
                            $this->prefix_url . $category->getUrl(),
                            $category->getName(),
                            $this->separator
                        );
                    }
                }

                $str .= $this->createBreadCrumbs($category->getTree());
            } else {
                if ($this->position !== null) {
                    if ($this->last_link) {
                        $template =
                            '<span itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">' .
                            '<a itemprop="item" href="%s">' .
                            '<span itemprop="name">%s</span>' .
                            '<meta itemprop="position" content="%s">' .
                            '</a></span>';

                        $str .= sprintf(
                            $template,
                            $this->prefix_url . $category->getUrl(),
                            $category->getName(),
                            $this->position++,
                        );
                    } else {
                        $last_element_wrap_tag = $this->last_element_wrap_tag ?? 'span';
                        $template =
                            '<%s itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">' .
                            '<span itemprop="name">%s</span>' .
                            '<meta itemprop="position" content="%s">' .
                            '</%s>';

                        $str .= sprintf(
                            $template,
                            $last_element_wrap_tag,
                            $category->getName(),
                            $this->position++,
                            $last_element_wrap_tag
                        );
                    }
                } else {
                    $firstTag = $this->last_element_wrap_tag ? "<$this->last_element_wrap_tag>" : '';
                    $lastTag = $this->last_element_wrap_tag ? "</$this->last_element_wrap_tag>" : '';

                    $str .= $this->last_link
                        ? sprintf(
                            '<a href="%s">%s</a>',
                            $this->prefix_url . $category->getUrl(),
                            $category->getName() . $this->postfix_text
                        )
                        : implode('', [$firstTag, $category->getName(), $this->postfix_text, $lastTag]);
                }
            }
        }

        return $str;
    }
}