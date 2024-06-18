<?php

declare(strict_types=1);

namespace Krugozor\Framework\Pagination;

use Krugozor\Framework\Pagination\Manager as PaginationManager;
use Krugozor\Framework\Statical\Strings;

class Helper
{
    /**
     * Стандартный вид пагинации:
     * «««  ««  «  1 2 3 4 5 6 7 8 9 10  »  »»  »»»
     *
     * @var int
     */
    public const PAGINATION_NORMAL_TYPE = 1;

    /**
     * Вид интервальной декрементной пагинации:
     * «««  ««  «  50-41 40-31 30-21 20-11 10-1  »  »»  »»»
     *
     * @var int
     */
    public const PAGINATION_DECREMENT_TYPE = 2;

    /**
     * Вид интервальной инкрементной пагинации:
     * «««  ««  «  1-10 11-20 21-30 31-40 41-50  »  »»  »»»
     *
     * @var int
     */
    public const PAGINATION_INCREMENT_TYPE = 3;

    /**
     * @var PaginationManager
     */
    private PaginationManager $paginationManager;

    /**
     * Хранилище CSS-классов для каждого <a> элемента пагинатора.
     *
     * @var array
     */
    private array $styles = [];

    /**
     * Хранилище пар ключ=>значение для подстановки в QUERY_STRING
     * гиперссылок пагинатора.
     *
     * @var array
     */
    private array $request_uri_params = [];

    /**
     * Якоря и title для всех элементов <a> пагинатора.
     *
     * @var array
     */
    private array $html = [
        'first_page_anchor'  => '«««',
        'previous_block_anchor'  => '««',
        'previous_page_anchor'   => '«',
        'next_page_anchor'  => '»',
        'next_block_anchor' => '»»',
        'last_page_anchor'   => '»»»',

        'first_page_title' => 'На первую страницу',
        'previous_block_title' => 'Предыдущие страницы',
        'previous_page_title'  => 'Предыдущая страница',
        'next_page_title' => 'Следующая страница',
        'next_block_title' => 'Следующие страницы',
        'last_page_title'  => 'На последнюю страницу',
    ];

    /**
     * Показывать ли элемент <a> '«««'.
     *
     * @var bool
     */
    private bool $view_first_page_label = true;

    /**
     * Показывать ли элемент <a> '»»»'.
     *
     * @var bool
     */
    private bool $view_last_page_label = true;

    /**
     * Показывать ли элемент <a> '««'.
     *
     * @var bool
     */
    private bool $view_previous_block_label = true;

    /**
     * Показывать ли элемент <a> '»»'.
     *
     * @var bool
     */
    private bool $view_next_block_label = true;

    /**
     * Идентификатор фрагмента (#primer), ссылающийся на некоторую часть открываемого документа.
     *
     * @var string
     */
    private string $fragment_identifier;

    /**
     * Тип интерфейса пагинатора (см. константы класса PAGINATION_*_TYPE).
     *
     * @var int
     */
    private int $pagination_type = self::PAGINATION_NORMAL_TYPE;

    /**
     * @param Manager $manager
     */
    public function __construct(Manager $manager)
    {
        $this->paginationManager = $manager;
    }

    /**
     * Возвращает объект Manager
     *
     * @param void
     * @return Manager
     */
    public function getPaginationManager(): Manager
    {
        return $this->paginationManager;
    }

    /**
     * Устанавливает тип интерфейса пагинатора.
     *
     * @param int $pagination_type
     * @return $this
     */
    public function setPaginationType(int $pagination_type): self
    {
        $this->pagination_type = $pagination_type;

        return $this;
    }

    /**
     * Устанавливает очередной параметр для QUERY_STRING гиперссылок пагинатора.
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function setRequestUriParameter(string $key, mixed $value): self
    {
        $this->request_uri_params[$key] = (string) $value;

        return $this;
    }

    /**
     * Устанавливает, показывать ли элемент <a> '«««'.
     *
     * @param bool $value
     * @return $this
     */
    public function setViewFirstPageLabel(bool $value): self
    {
        $this->view_first_page_label = $value;

        return $this;
    }

    /**
     * Устанавливает, показывать ли элемент <a> '»»»'.
     *
     * @param bool $value
     * @return $this
     */
    public function setViewLastPageLabel(bool $value): self
    {
        $this->view_last_page_label = $value;

        return $this;
    }

    /**
     * Устанавливает, показывать ли элемент <a> '««'.
     *
     * @param bool $value
     * @return $this
     */
    public function setViewPreviousBlockLabel(bool $value): self
    {
        $this->view_previous_block_label = $value;

        return $this;
    }

    /**
     * Устанавливает, показывать ли элемент <a> '»»'.
     *
     * @param bool $value
     * @return $this
     */
    public function setViewNextBlockLabel(bool $value): self
    {
        $this->view_next_block_label = $value;

        return $this;
    }

    /**
     * Устанавливает идентификатор фрагмента (#primer) гиперссылок пагинатора.
     *
     * @param string $fragment_identifier
     * @return $this
     */
    public function setFragmentIdentifier(string $fragment_identifier): self
    {
        $this->fragment_identifier = rtrim($fragment_identifier, ' #');

        return $this;
    }

    /**
     * Устанавливает CSS-класс каждого элемента <a> в интерфейсе пагинатора.
     *
     * @param string $class
     * @return $this
     */
    public function setCssNormalLinkClass(string $class): self
    {
        $this->styles['normal_link_class'] = $class;

        return $this;
    }

    /**
     * Устанавливает CSS-класс элемента <span> в интерфейсе пагинатора,
     * страница которого открыта в текущий момент.
     *
     * @param string $class
     * @return $this
     */
    public function setCssActiveLinkClass(string $class): self
    {
        $this->styles['active_link_class'] = $class;

        return $this;
    }

    /**
     * Устанавливает CSS-класс элемента <a> '«««'
     *
     * @param string $class
     * @return $this
     */
    public function setCssFirstPageClass(string $class): self
    {
        $this->styles['first_page_class'] = $class;

        return $this;
    }

    /**
     * Устанавливает CSS-класс элемента <a> '»»»'
     *
     * @param string $class
     * @return $this
     */
    public function setCssLastPageClass(string $class): self
    {
        $this->styles['last_page_class'] = $class;

        return $this;
    }

    /**
     * Устанавливает CSS-класс элемента <a> '««'
     *
     * @param string $class
     * @return $this
     */
    public function setCssPreviousBlockClass(string $class): self
    {
        $this->styles['previous_block_class'] = $class;

        return $this;
    }

    /**
     * Устанавливает CSS-класс элемента <a> '»»'
     *
     * @param string $class
     * @return $this
     */
    public function setCssNextBlockClass(string $class): self
    {
        $this->styles['next_block_class'] = $class;

        return $this;
    }

    /**
     * Устанавливает CSS-класс элемента <a> '«'
     *
     * @param string $class
     * @return $this
     */
    public function setCssPreviousPageClass(string $class): self
    {
        $this->styles['previous_page_class'] = $class;

        return $this;
    }

    /**
     * Устанавливает CSS-класс элемента <a> '»'
     *
     * @param string $class
     * @return $this
     */
    public function setCssNextPageClass(string $class): self
    {
        $this->styles['next_page_class'] = $class;

        return $this;
    }

    /**
     * Устанавливает якорь для элемента <a> '«««'
     *
     * @param string $anchor
     * @return $this
     */
    public function setFirstPageAnchor(string $anchor): self
    {
        $this->html['first_page_anchor'] = $anchor;

        return $this;
    }

    /**
     * Устанавливает якорь для элемента <a> '»»»'
     *
     * @param string $anchor
     * @return $this
     */
    public function setLastPageAnchor(string $anchor): self
    {
        $this->html['last_page_anchor'] = $anchor;

        return $this;
    }

    /**
     * Устанавливает якорь для элемента <a> '««'
     *
     * @param string $anchor
     * @return $this
     */
    public function setPreviousBlockAnchor(string $anchor): self
    {
        $this->html['previous_block_anchor'] = $anchor;

        return $this;
    }

    /**
     * Устанавливает якорь для элемента <a> '»»'
     *
     * @param string $anchor
     * @return $this
     */
    public function setNextBlockAnchor(string $anchor): self
    {
        $this->html['next_block_anchor'] = $anchor;

        return $this;
    }

    /**
     * Устанавливает якорь для элемента <a> '«'
     *
     * @param string $anchor
     * @return $this
     */
    public function setPreviousPageAnchor(string $anchor): self
    {
        $this->html['previous_page_anchor'] = $anchor;

        return $this;
    }

    /**
     * Устанавливает якорь для элемента <a> '»'
     *
     * @param string $anchor
     * @return $this
     */
    public function setNextPageAnchor(string $anchor): self
    {
        $this->html['next_page_anchor'] = $anchor;

        return $this;
    }

    /**
     * Формирует и возвращает HTML-код строки навигации.
     *
     * @return string
     */
    public function getHtml(): string
    {
        ob_start();
    ?>
    <nav>
        <ul>
        <? if ($this->view_first_page_label && $this->paginationManager->getCurrentSeparator() && $this->paginationManager->getCurrentSeparator() != 1): ?>
            <li<?=$this->createInlineCssClassDeclaration('first_page_class', 'normal_link_class')?>><a title="<?=$this->html['first_page_title']?>" href="<?=$this->createUrl([$this->paginationManager->getPageName() => 1, $this->paginationManager->getSeparatorName() => 1])?>"><?=$this->html['first_page_anchor']?></a></li>
        <? endif; ?>

        <? if ($this->view_previous_block_label && $this->paginationManager->getPreviousBlockSeparator()): ?>
            <li<?=$this->createInlineCssClassDeclaration('previous_block_class', 'normal_link_class')?>><a title="<?=$this->html['previous_block_title']?>" href="<?=$this->createUrl([$this->paginationManager->getPageName() => $this->paginationManager->getPageForPreviousBlock(), $this->paginationManager->getSeparatorName() => $this->paginationManager->getPreviousBlockSeparator()])?>"><?=$this->html['previous_block_anchor']?></a></li>
        <? endif; ?>

        <? if($this->paginationManager->getPreviousPageSeparator() && $this->paginationManager->getPreviousPage()): ?>
            <li<?=$this->createInlineCssClassDeclaration('previous_page_class', 'normal_link_class')?>><a title="<?=$this->html['previous_page_title']?>" href="<?=$this->createUrl([$this->paginationManager->getPageName() => $this->paginationManager->getPreviousPage(), $this->paginationManager->getSeparatorName() => $this->paginationManager->getPreviousPageSeparator()])?>"><?=$this->html['previous_page_anchor']?></a></li>
        <? endif; ?>

        <? foreach($this->paginationManager->getPaginationListData() as $row): ?>
            <? if($this->paginationManager->getCurrentPage() == $row["page"]): ?>
                <li<?=$this->createInlineCssClassDeclaration('active_link_class')?>><span><?=$this->createHyperlinkAnchor($row)?></span></li>
            <? else: ?>
                <li<?=$this->createInlineCssClassDeclaration('normal_link_class')?>><a href="<?=$this->createUrl([$this->paginationManager->getPageName() => $row["page"], $this->paginationManager->getSeparatorName() => $row["separator"]])?>"><?=$this->createHyperlinkAnchor($row)?></a></li>
            <? endif; ?>
        <? endforeach; ?>

        <? if($this->paginationManager->getNextPageSeparator() && $this->paginationManager->getNextPage()): ?>
            <li<?=$this->createInlineCssClassDeclaration('next_page_class', 'normal_link_class')?>><a title="<?=$this->html['next_page_title']?>" href="<?=$this->createUrl([$this->paginationManager->getPageName() => $this->paginationManager->getNextPage(), $this->paginationManager->getSeparatorName() => $this->paginationManager->getNextPageSeparator()])?>"><?=$this->html['next_page_anchor']?></a></li>
        <? endif; ?>

        <? if($this->view_next_block_label && $this->paginationManager->getNextBlockSeparator()): ?>
            <li<?=$this->createInlineCssClassDeclaration('next_block_class', 'normal_link_class')?>><a title="<?=$this->html['next_block_title']?>" href="<?=$this->createUrl([$this->paginationManager->getSeparatorName() => $this->paginationManager->getNextBlockSeparator()])?>"><?=$this->html['next_block_anchor']?></a></li>
        <? endif; ?>

        <? if ($this->view_last_page_label && $this->paginationManager->getLastSeparator() && $this->paginationManager->getCurrentSeparator() != $this->paginationManager->getLastSeparator()): ?>
            <li<?=$this->createInlineCssClassDeclaration('last_page_class', 'normal_link_class')?>><a title="<?=$this->html['last_page_title']?>" href="<?=$this->createUrl([$this->paginationManager->getPageName() => $this->paginationManager->getLastPage(), $this->paginationManager->getSeparatorName() => $this->paginationManager->getLastSeparator()])?>"><?=$this->html['last_page_anchor']?></a></li>
        <? endif; ?>
        </ul>
    </nav>
<?php
        $str = ob_get_contents();
        ob_end_clean();

        return $str;
    }

    /**
     * Создаёт якорь для элемента <a> в зависимости от типа $this->pagination_type.
     *
     * @param array $params
     * @return string
     */
    private function createHyperlinkAnchor(array $params): string
    {
        return (string) match ($this->pagination_type) {
            self::PAGINATION_DECREMENT_TYPE => $params['decrement_anhor'],
            self::PAGINATION_INCREMENT_TYPE => $params['increment_anhor'],
            default => $params['page'],
        };
    }

    /**
     * Возвращает строку вида `class="class_name"` если $class_name объявлен и
     * описан в $this->styles[$class_name].
     * В обратном случае возвращает строку вида `class="replacement_class_name"`,
     * если $replacement_class_name объявлен в качестве аргумента метода и
     * описан в $this->styles[$replacement_class_name].
     * Если $replacement_class_name не объявлен, возвращается пустая строка.
     *
     * @param string $class_name
     * @param string|null $replacement_class_name
     * @return string
     */
    private function createInlineCssClassDeclaration(string $class_name, ?string $replacement_class_name = null): string
    {
        return !empty($this->styles[$class_name])
               ? ' class="' . $this->styles[$class_name] . '"'
               : ($replacement_class_name === null
                  ? ''
                  : call_user_func_array($this->{__FUNCTION__}(...), array($replacement_class_name))
                 );
    }

    /**
     * Возвращает идентификатор фрагмента с символом #
     * для подстановки непосредственно в URL-адрес.
     *
     * @return string
     */
    private function createFragmentIdentifier(): string
    {
        return !empty($this->fragment_identifier) ? '#' . $this->fragment_identifier : '';
    }

    /**
     * Возвращает REQUEST_URI без QUERY_STRING.
     *
     * @return string
     */
    private function createRequestUri(): string
    {
        if (str_contains($_SERVER["REQUEST_URI"], '?')) {
            return substr($_SERVER["REQUEST_URI"], 0, strpos($_SERVER["REQUEST_URI"], '?'));
        } else {
            return $_SERVER["REQUEST_URI"];
        }
    }

    /**
     * @param array $paginationParams
     * @return string
     */
    private function createQueryString(array $paginationParams): string
    {
        if (!empty($paginationParams[$this->paginationManager->getPageName()]) &&
            !empty($paginationParams[$this->paginationManager->getSeparatorName()]) &&
            $paginationParams[$this->paginationManager->getPageName()] == 1 &&
            $paginationParams[$this->paginationManager->getSeparatorName()] == 1
        ) {
            unset($paginationParams[$this->paginationManager->getPageName()]);
            unset($paginationParams[$this->paginationManager->getSeparatorName()]);
        }

        $queryString = Strings::httpBuildQuery(array_merge(
                $this->request_uri_params,
                $paginationParams
        ));

        return $queryString ? "?$queryString" : '';
    }

    /**
     * @param array $paginationParams
     * @return string
     */
    private function createUrl(array $paginationParams): string
    {
        return $this->createRequestUri() . $this->createQueryString($paginationParams) . $this->createFragmentIdentifier();
    }
}