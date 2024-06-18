<?php

namespace Krugozor\Framework\Module\Category\Mapper;

use Krugozor\Database\MySqlException;
use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Context;
use Krugozor\Framework\Module\Category\Model\Category;
use Krugozor\Framework\Module\User\Model\AbstractTerritory;

/**
 * Категории с учетом региональности.
 * Вынесено из маппера категорий, дабы не захламлять основной маппер частным случаем.
 */
class TerritoryMapper extends CategoryMapper
{
    /**
     * Переопределяем свойство, что бы суперкласс не высчитывал имя модели и таблицы автоматически.
     *
     * @var string|null
     */
    protected ?string $db_table_name = 'category';

    /**
     * Переопределяем свойство, что бы суперкласс не высчитывал имя модели и таблицы автоматически.
     *
     * @var string|null
     */
    protected ?string $model_class_name = Category::class;

    /**
     * Ключ мемкэша метода получения кол-ва объявлений в территории %s
     */
    public const MC_KEY_findAdvertCountInTerritory = 'findAdvertCountInTerritory.%s';

    /**
     * Ключ мемкэша метода получения кол-ва объявлений в территории %s категории %s
     */
    public const MC_KEY_findAdvertCountInTerritoryAndCategory = 'findAdvertCountInTerritoryAndCategory.%s.%s';

    /**
     * Возвращает количество объявлений в регионе $territory категории $category.
     *
     * @param AbstractTerritory $territory
     * @param Category $category
     * @return int
     * @throws MySqlException
     */
    public function findAdvertCountInTerritoryAndCategory(AbstractTerritory $territory, Category $category): int
    {
        $key = sprintf(
            self::MC_KEY_findAdvertCountInTerritoryAndCategory,
            $territory->getNameEn(),
            $category->getId()
        );

        if (($data = Context::getInstance()->getMemcache()->get($key)) !== false) {
            return (int) $data;
        }

        $sql = '
          SELECT `count` 
          FROM ?f FORCE INDEX(`__?s,id_category`) 
          WHERE ?f = ?i AND `id_category` = ?i';

        $data = (int) $this->getDb()->query(
            $sql,
            $territory->getCountableTableName(),
            $territory->getCountableFieldName(),
            $territory->getCountableFieldName(),
            $territory->getId(),
            $category->getId()
        )->getOne() ?: 0;

        Context::getInstance()->getMemcache()->set(
            $key,
            $data,
            MEMCACHE_COMPRESSED,
            60 * 5
        );

        return $data;
    }

    /**
     * @param AbstractTerritory $territory
     * @param Category $category
     * @return bool
     */
    public function clearCahcheAdvertCountInTerritoryAndCategory(AbstractTerritory $territory, Category $category): bool
    {
        $key = sprintf(
            self::MC_KEY_findAdvertCountInTerritoryAndCategory,
            $territory->getNameEn(),
            $category->getId()
        );

        return Context::getInstance()->getMemcache()->delete($key);
    }

    /**
     * Возвращает количество объявлений в регионе $territory.
     *
     * @param AbstractTerritory $territory
     * @return int
     * @throws MySqlException
     */
    public function findAdvertCountInTerritory(AbstractTerritory $territory): int
    {
        $key = sprintf(
            self::MC_KEY_findAdvertCountInTerritory, $territory->getNameEn()
        );

        if (($data = Context::getInstance()->getMemcache()->get($key)) !== false) {
            return (int) $data;
        }

        $sql = 'SELECT `count` FROM ?f FORCE INDEX (`__?s`) WHERE ?f = ?i';

        $data = (int) $this->getDb()->query(
            $sql,
            $territory->getCountableSumTableName(),
            $territory->getCountableFieldName(),
            $territory->getCountableFieldName(),
            $territory->getId()
        )->getOne() ?: 0;

        Context::getInstance()->getMemcache()->set(
            $key,
            $data,
            MEMCACHE_COMPRESSED,
            60 * 5
        );

        return $data;
    }

    /**
     * @param AbstractTerritory $territory
     * @return bool
     */
    public function clearCahcheAdvertCountInTerritory(AbstractTerritory $territory): bool
    {
        $key = sprintf(
            self::MC_KEY_findAdvertCountInTerritory, $territory->getNameEn()
        );

        return Context::getInstance()->getMemcache()->delete($key);
    }

    /**
     * @param AbstractTerritory $territory
     * @param CoverArray $coverArray
     */
    public function clearCahcheLevelCountInTerritoryAndCategory(AbstractTerritory $territory, CoverArray $coverArray): void
    {
        foreach ($coverArray as $category) {
            self::clearCahcheAdvertCountInTerritoryAndCategory($territory, $category);
            if ($category->getTree()) {
                (__METHOD__)($territory, $category->getTree());
            }
        }
    }
}