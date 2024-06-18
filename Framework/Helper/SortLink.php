<?php

declare(strict_types=1);

namespace Krugozor\Framework\Helper;

use Krugozor\Framework\Statical\Strings;

/**
 * Класс для построения гиперссылки в формате HTML, которые
 * используются в качестве управляющего элемента сортировки
 * по конкретному полю таблицы админ-интерфейса.
 * Класс вызывается в шаблоне в следующем контексте:
 *
 * $linker = (new SortLink())
 *     // имя поля сортировки этой ссылки
 *     ->setFieldName('id')
 *     // якорь ссылки
 *     ->setAnchor('ID пользователя')
 *     // url ссылки
 *     ->setUrl('/admin/user/')
 *     // путь к директории с иконками ASC и DESC
 *     ->setIconSrc('/img/common/system/icon/')
 *     // имя поля, по которому в данный момент проходит сортировка
 *     ->setCurrentFieldName($_REQUEST['field_name'])
 *     // текущий метод сортировки (ASC и DESC)
 *     ->setCurrentSortOrder($_REQUEST['sort_order'])
 *     // дополнительные параметры для Query String
 *     ->setQueryStringFromArray(array(
 *         'param1' => 1,
 *         'param2' => 2,
 *     ));
 *
 * echo $linker->getHtml();
 */
class SortLink
{
    /**
     * URL-адрес гиперссылки.
     *
     * @var string
     */
    protected string $url;

    /**
     * Якорь гиперссылки.
     *
     * @var string
     */
    protected string $anchor;

    /**
     * Путь к директории с иконками.
     *
     * @var string
     */
    protected string $icon_src;

    /**
     * Имена иконок типа сортировки по умолчанию.
     *
     * @var array
     */
    protected array $icons_name = array('asc' => 'asc.png', 'desc' => 'desc.png');

    /**
     * Имя поля.
     *
     * @var string
     */
    protected string $field_name;

    /**
     * Текущий столбец поля таблицы,
     * по которому происходит сортировка.
     *
     * @var string
     */
    protected string $current_field_name;

    /**
     * Текущий порядок сортировки,
     * по которому происходит сортировка.
     *
     * @var string
     */
    protected string $current_sort_order;

    /**
     * Параметры QUERY_STRING в виде ассоциативного массива
     * array('key' => 'value'), которые будут добавлены к гиперссылке.
     *
     * @var array
     */
    protected array $query_string = [];

    /**
     * Устанавливает URL ссылки.
     *
     * @param string $url
     * @return static
     */
    public function setUrl(string $url): static
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Устанавливает якорь ссылки.
     *
     * @param string $anchor
     * @return static
     */
    public function setAnchor(string $anchor): static
    {
        $this->anchor = $anchor;

        return $this;
    }

    /**
     * Путь до директории с изображениями-иконками.
     * В данной директории должны лежать два изображения
     * обозначающие порядок сортировки ASC и DESC.
     *
     * @param string $icon_src
     * @return static
     */
    public function setIconSrc(string $icon_src): static
    {
        $this->icon_src = $icon_src;

        return $this;
    }

    /**
     * Устанавливает имя столбца, по которому будет происходить сортировка.
     *
     * @param string $field_name
     * @return static
     */
    public function setFieldName(string $field_name): static
    {
        $this->field_name = $field_name;

        return $this;
    }

    /**
     * Имя столбца, по которому в данный момент происходит сортировка.
     * Подразумевается, что $current_field_name берется из запроса.
     *
     * @param string $current_field_name
     * @return static
     */
    public function setCurrentFieldName(string $current_field_name): static
    {
        $this->current_field_name = $current_field_name;

        return $this;
    }

    /**
     * Тип сортировки (ASC или DESC) в данный момент.
     * Подразумевается, что это значение берется из запроса.
     *
     * @param string $current_sort_order
     * @return static
     */
    public function setCurrentSortOrder(string $current_sort_order): static
    {
        $this->current_sort_order = strtoupper($current_sort_order);

        return $this;
    }

    /**
     * Принимает ассоциативный массив одного уровня вложенности, который представляет собой
     * набор ключей и значений, из которых будет сформирован QUERY_STRING.
     *
     * @param array $data
     * @return static
     */
    public function setQueryStringFromArray(array $data): static
    {
        $this->query_string = $data;

        return $this;
    }

    /**
     * Возвращает HTML-код ссылки.
     *
     * @return string
     */
    public function getHtml(): string
    {
        ob_start();
        ?>
        <a href="<?=$this->url?>?<?=$this->makeQueryString()?>"><?=$this->anchor?></a><!--
         --><?php if ($this->current_field_name == $this->field_name): ?>&nbsp;<img alt="<?php echo $this->current_sort_order?>" src="<?php echo $this->icon_src.$this->icons_name[strtolower($this->current_sort_order)]?>"><?php endif; ?>
        <?php
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }

    /**
     * Создает QUERY_STRING.
     *
     * @return string
     */
    protected function makeQueryString(): string
    {
        $data = $this->query_string;
        $data['sort_order'] = $this->current_sort_order == 'DESC' ? 'ASC' : 'DESC';
        $data['field_name'] = $this->field_name;

        return Strings::httpBuildQuery($data);
    }
}