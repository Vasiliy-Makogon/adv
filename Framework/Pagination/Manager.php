<?php

declare(strict_types=1);

namespace Krugozor\Framework\Pagination;

use Krugozor\Framework\Http\Request;

class Manager
{
    /**
     * Объект класса Helper;
     *
     * @var Helper|null
     */
    private ?Helper $helper = null;

    /**
     * Максимальное количество записей из СУБД,
     * которое необходимо выводить на одной странице.
     * Один из аргументов конструктора.
     *
     * @var int
     */
    private int $limit;

    /**
     * Количество ссылок на страницы, выводящихся
     * между ссылками-метками пагинатора << и >>.
     * Фактически, это количество ссылок с числовыми
     * индексами в ссылочном блоке.
     *
     * @var int
     */
    private int $link_count;

    /**
     * Номер текущей страницы.
     *
     * @var int|string
     */
    private int|string $current_page;

    /**
     * Номер текущего сепаратора.
     *
     * @var int|string
     */
    private int|string $current_sep;

    /**
     * Начальное значение для SQL-оператора LIMIT.
     *
     * @var int|string
     */
    private int|string $start_limit;

    /**
     * Конечное значение для SQL-оператора LIMIT.
     *
     * @var int|string
     */
    private int|string $stop_limit;

    /**
     * Общее количество записей в таблице БД, участвующих
     * в вычислениях и формировании данных для пагинатора.
     *
     * @var int
     */
    private int $total_rows;

    /**
     * Количество страниц пагинатора, которое получится, если на одну страницу
     * необходимо выводить $this->limit записей из базы.
     *
     * @var int
     */
    private int $total_pages;

    /**
     * Количество ссылочных блоков, на которые будет разделена БД.
     *
     * @var int
     */
    private int $total_blocks;

    /**
     * Имя переменной из Request, значение которой будет указывать страницу.
     *
     * @var string
     */
    private string $page_var_name;

    /**
     * Имя переменной из Request, значение которой будет указывать блок страниц (сепаратор).
     *
     * @var string
     */
    private string $separator_var_name;

    /**
     * Основной массив значений для вывода в шаблоне.
     *
     * @var array
     */
    private array $paginationListData = array();

    /**
     * @param int|string $limit - количество записей из таблицы СУБД на страницу
     * @param int|string $link_count - количество ссылок на страницы между ссылками пагинатора, т.е.:
     *                          «««  ««  «  $link_count  »  »»  »»»
     * @param Request $request
     * @param string $page_var_name - имя ключа переменной из запроса, указывающей страницу для открытия.
     * @param string $separator_var_name - имя ключа переменной из запроса, указывающей блок страниц (сепаратор).
     */
    public function __construct(
        int|string $limit,
        int|string $link_count,
        Request $request,
        string $page_var_name = 'page',
        string $separator_var_name = 'sep'
    ) {
        $this->limit = (int) $limit;
        $this->link_count = (int) $link_count;

        $this->page_var_name = $page_var_name;
        $this->separator_var_name = $separator_var_name;

        $this->current_sep = (int) $request->getRequest($separator_var_name, Request::SANITIZE_INT) ?? 1;
        $this->current_sep = $this->current_sep > 0 ? $this->current_sep : 1;

        $current_page = $request->getRequest($page_var_name, Request::SANITIZE_INT);
        $this->current_page = $current_page/* !== null*/
            ? (int) $current_page
            : ($this->current_sep - 1) * $this->link_count + 1;

        $this->start_limit = ($this->current_page - 1) * $this->limit;
        $this->stop_limit  = $this->limit;
    }

    /**
     * Возвращает начальное значение для SQL-оператора LIMIT.
     *
     * @param void
     * @return int
     */
    public function getStartLimit(): int
    {
        return $this->start_limit;
    }

    /**
     * Возвращает конечное значение для SQL-оператора LIMIT.
     *
     * @param void
     * @return int
     */
    public function getStopLimit(): int
    {
        return $this->stop_limit;
    }

    /**
     * Возвращает общее количество записей.
     *
     * @param void
     * @return int
     */
    public function getCount(): int
    {
        return $this->total_rows;
    }

    /**
     * Принимает числовое значение - общее количество записей в базе,
     * а также вычисляет все необходимые переменные для формирования строки навигации.
     *
     * Я пытался рефакторить алгоритм данного метода, но качественно сделать этого не удалось
     * в виду давности написания данного класса.
     *
     * @param int
     * @return Manager
     */
    public function setCount(int $total_rows): static
    {
        $this->total_rows = $total_rows;
        $this->total_pages = (int) ceil($this->total_rows/$this->limit);
        $this->total_blocks = (int) ceil($this->total_pages/$this->link_count);

        // Если количество блоков больше всех страниц, то
        // за количество блоков берём количество всех страниц.
        $this->total_blocks = ($this->total_blocks > $this->total_pages) ? $this->total_pages : $this->total_blocks;

        $k = ($this->current_sep - 1) * $this->link_count + 1;

            for ($i = $k; $i < $this->link_count + $k && $i <= $this->total_pages; $i++)
            {
                $temp = ($this->total_rows - (($i-1) * $this->limit));
                $temp2 = ($temp - $this->limit > 0) ? $temp - $this->limit + 1 : 1;

                $temp3 = ($this->limit * ($i - 1)) + 1;
                $temp4 = $i * $this->limit  > $this->total_rows ? $this->total_rows : $i * $this->limit;

                $this->paginationListData[] = array
                (
                    'page' => $i,
                    'separator' => $this->current_sep,
                    'decrement_anhor' => ($temp == $temp2 ? $temp : $temp . ' - ' . $temp2),
                    'increment_anhor' => ($temp3 == $temp4 ? $temp3 : $temp3 . ' - ' . $temp4)
                );
            }

        return $this;
    }

    /**
     * @return Helper|null
     */
    public function getHelper(): ?Helper
    {
        if ($this->helper === null || !($this->helper instanceof Helper)) {
            $this->helper = new Helper($this);
        }

        return $this->helper;
    }

    /**
     * Возвращает число для начала отсчёта записей при декрементной пагинации.
     * В цикле, при выводе записей, данное число нужно декрементировать при
     * каждой итерации цикла.
     *
     * @param void
     * @return int
     */
    public function getAutodecrementNum(): int
    {
        return $this->total_rows - $this->start_limit;
    }

    /**
     * Возвращает число для начала отсчёта записей при инкрементной пагинации.
     * В цикле, при выводе записей, данное число нужно инкрементировать при
     * каждой итерации цикла.
     *
     * @param void
     * @return int
     */
    public function getAutoincrementNum(): int
    {
        return $this->limit * ($this->current_page-1) + 1;
    }

    /**
     * Возвращает номер сепаратора для формирования ссылки (««).
     *
     * @param void
     * @return int
     */
    public function getPreviousBlockSeparator(): int
    {
        return $this->current_sep - 1 ?: 0;
    }

    /**
     * Возвращает номер сепаратора для формирования ссылки (»»).
     *
     * @param void
     * @return int
     */
    public function getNextBlockSeparator(): int
    {
        return $this->current_sep < $this->total_blocks ? $this->current_sep + 1 : 0;
    }

    /**
     * Возвращает номер сепаратора для формирования ссылки (»»»).
     *
     * @param void
     * @return int
     */
    public function getLastSeparator(): int
    {
        return $this->total_blocks;
    }

    /**
     * Возвращает номер страницы для формирования ссылки (»»»).
     *
     * @param void
     * @return int
     */
    public function getLastPage(): int
    {
        return $this->total_pages;
    }

    /**
     * Возвращает многомерный массив для цикла вывода номеров страниц в шаблоне.
     *
     * @see Helper
     * @return array
     */
    public function getPaginationListData(): array
    {
        return $this->paginationListData;
    }

    /**
     * Возвращает номер текущей страницы.
     *
     * @param void
     * @return int
     */
    public function getCurrentPage(): int
    {
        return $this->current_page;
    }

    /**
     * Возвращает номер текущего сепаратора.
     *
     * @param void
     * @return int
     */
    public function getCurrentSeparator(): int
    {
        return $this->current_sep;
    }

    /**
     * Возвращает номер сепаратора для формирования ссылки («).
     *
     * @param void
     * @return int
     */
    public function getPreviousPageSeparator(): int
    {
        // Текущий сепаратор, определённый програмно
        $cs = (int) ceil($this->current_page / $this->link_count);
        // Определяем сепаратор страницы current_page - 1
        $cs2 = (int) ceil(($this->current_page - 1) / $this->link_count);

        // Если сепаратор страницы current_page - 1 меньше текущего сепаратора,
        // значит страница current_page - 1 относится к следующему блоку с сепаратором $cs2
        return $cs2 < $cs ? $cs2 : $cs;
    }

    /**
     * Возвращает номер сепаратора для формирования ссылки (»).
     *
     * @param void
     * @return int
     */
    public function getNextPageSeparator(): int
    {
        // Текущий сепаратор, определённый програмно.
        $cs = (int) ceil($this->current_page / $this->link_count);
        // Определяемсепаратор страницы current_page + 1.
        $cs2 = (int) ceil(($this->current_page + 1) / $this->link_count);

        // Если сепаратор страницы current_page + 1 больше текущего сепаратора,
        // значит страница current_page + 1 относится к следующему блоку с сепаратором $cs2.
        return $cs2 > $cs ? $cs2 : $cs;
    }

    /**
     * Возвращает номер страницы для формирования ссылки («).
     *
     * @param void
     * @return int
     */
    public function getPreviousPage(): int
    {
        return $this->current_page - 1 ?: 0;
    }

    /**
     * Возвращает номер страницы для формирования ссылки (««).
     *
     * @param void
     * @return int
     */
    public function getPageForPreviousBlock(): int
    {
        return $this->current_page - ($this->current_page % $this->link_count ?: $this->link_count);
    }

    /**
     * Возвращает номер страницы для формирования ссылки (»).
     *
     * @param void
     * @return int
     */
    public function getNextPage(): int
    {
        return $this->current_page < $this->total_pages ? $this->current_page + 1 : 0;
    }

    /**
     * Возвращает имя переменной из запроса, содержащей номер сепаратора.
     *
     * @param void
     * @return string
     */
    public function getSeparatorName(): string
    {
        return $this->separator_var_name;
    }

    /**
     * Возвращает имя переменной из запроса, содержащей номер страницы.
     *
     * @param void
     * @return string
     */
    public function getPageName(): string
    {
        return $this->page_var_name;
    }
}