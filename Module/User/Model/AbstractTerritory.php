<?php

namespace Krugozor\Framework\Module\User\Model;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Module\Category\Mapper\TerritoryMapper;
use Krugozor\Framework\Module\Category\Model\Category;
use Krugozor\Framework\Statical\Translit;

/**
 * Базовый класс для регионов, т.е. для стран, областей и городов.
 */
abstract class AbstractTerritory extends AbstractModel
{
    /**
     *  Имя поля, по которому будут делать выборку для получения кол-ва объявлений в регионе.
     *
     * @var string
     */
    protected string $countable_field_name;

    /**
     * Имя таблицы, по которому будут делать выборку для получения кол-ва объявлений
     * в регионе по определенным категориям.
     *
     * @var string
     */
    protected string $countable_table_name;

    /**
     * Имя таблицы, по которому будут делать выборку для получения кол-ва объявлений в регионе.
     *
     * @var string
     */
    protected string $countable_sum_table_name;

    /**
     * URL-адрес региона с учетом регионов-родителей, например:
     * /russia - для стран
     * /russia/moskovskaja - для регионов
     * /russia/moskovskaja/moskva - для городов
     * Свойство $url устанавливается в результате работы класса @see TerritoryList
     *
     * @var string|null
     */
    protected ?string $url = null;

    /**
     * @return string
     */
    public function getCountableFieldName(): string
    {
        return $this->countable_field_name;
    }

    /**
     * @return string
     */
    public function getCountableTableName(): string
    {
        return $this->countable_table_name;
    }

    /**
     * @return string
     */
    public function getCountableSumTableName(): string
    {
        return $this->countable_sum_table_name;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $url
     * @return $this
     */
    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Устанавливает имя территории на латинице, транслитерируя значение.
     * explicit-метод.
     *
     * @param string $name_ru имя региона
     * @return string имя региона в транслите
     */
    protected function _setNameEn(string $name_ru): string
    {
        return Translit::UrlTranslit($name_ru);
    }

    /**
     * @param Category|null $category
     * @return int
     */
    public function getAdvertCount(Category $category = null): int
    {
        // Если есть заполненное fake-свойство модели
        if (isset($this->data['advert_count'])) {
            return $this->data['advert_count'];
        }

        if ($category !== null) {
            $this->data['advert_count'] = $this->getMapperManager()
                ->getMapper(TerritoryMapper::class)
                ->findAdvertCountInTerritoryAndCategory($this, $category);
        } else {
            $this->data['advert_count'] = $this->getMapperManager()
                ->getMapper(TerritoryMapper::class)
                ->findAdvertCountInTerritory($this);
        }

        return $this->data['advert_count'];
    }

    /**
     * Очищает кэш по кол-ву объявлений в данной территории дерева категорий $tree.
     *
     * @param CoverArray $tree
     * @return static
     */
    public function clearCacheAdvertCountInCategory(CoverArray $tree): static
    {
        $this->getMapperManager()
            ->getMapper(TerritoryMapper::class)
            ->clearCahcheLevelCountInTerritoryAndCategory($this, $tree);

        return $this;
    }

    /**
     * Очищает кэш по общему кол-ву объявлений в данной территории.
     *
     * @return $this
     */
    public function clearCacheAdvertCountTotal(): self
    {
        $this->getMapperManager()
            ->getMapper(TerritoryMapper::class)
            ->clearCahcheAdvertCountInTerritory($this);

        return $this;
    }
}
