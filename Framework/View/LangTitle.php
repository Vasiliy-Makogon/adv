<?php

declare(strict_types=1);

namespace Krugozor\Framework\View;

use Krugozor\Framework\Helper\Format;

class LangTitle extends Lang
{
    /** @var string */
    protected const SEPARATOR_VERTICAL_LINE = '|';

    /** @var string */
    protected const SEPARATOR_FORWARD_SLASH = '/';

    /**
     * Возвращает строку для подстановки в тег html title
     *
     * @param string $separator
     * @return string
     */
    public function getTitle(string $separator = self::SEPARATOR_VERTICAL_LINE): string
    {
        return $this->reverse()->implode(" $separator ");
    }

    /**
     * Возвращает html-код тега title.
     *
     * @return string
     */
    public function getHtml(): string
    {
        return '<title>' . Format::outPut($this->getTitle()) . '</title>' . PHP_EOL;
    }

    /**
     * Возвращает html-код тега title для Open Graph.
     *
     * @return string
     */
    public function getOgHtml(): string
    {
        return '<meta property="og:title" content="' . Format::outPut($this->getTitle()) . '">' . PHP_EOL;
    }
}