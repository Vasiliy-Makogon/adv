<?php

namespace Krugozor\Framework\Module\Category\Mapper;

use Krugozor\Cover\CoverArray;
use Krugozor\Database\MySqlException;
use Krugozor\Database\Statement;
use Krugozor\Framework\Context;
use Krugozor\Framework\Mapper\CommonMapper;
use Krugozor\Framework\Mapper\Tree;
use Krugozor\Framework\Module\Category\Model\Category;

class CategoryMapper extends Tree
{
    /**
     * Данные страны $country_id для карты сайта.
     *
     * @param int $country_id
     * @return array[]
     * @throws MySqlException
     */
    public function findDataForSiteMap(int $country_id): array
    {
        $country = $region = [];

        /** @var Statement $res */
        $res = $this->getDb()->query("
            SELECT 
                category.category_url, 
                category.category_name,
                country.id AS region_id,
                country.country_name_en AS region_name_en,
                country.country_name_ru AS region_name_ru,
                country.country_name_ru2 AS region_name_ru2,
                cnt.`count`
            FROM `category`
            JOIN `user-country` AS country
            JOIN `advert-country_count` cnt ON cnt.id_country = country.id AND category.id = cnt.id_category
            WHERE country.id = ?i AND cnt.`count` >= 10
            ORDER BY category.category_name ASC
        ", $country_id);

        while ($row = $res->fetchAssoc()) {
            if (!isset($country[$row['region_id']])) {
                $country[$row['region_id']] = ['name' => $row['region_name_ru2'], 'data' => []];
            }
            $country[$row['region_id']]['data'][] = $row;
        }

        $res = $this->getDb()->query("
            SELECT 
                category.category_url, 
                category.category_name,
                region.id AS region_id,
                CONCAT(country.country_name_en, '/', region.region_name_en) AS region_name_en,
                region.region_name_ru AS region_name_ru,
                region.region_name_ru2 AS region_name_ru2,
                cnt.`count`
            FROM `category`
            JOIN `user-country` AS country
            JOIN `user-region` AS region ON region.id_country = country.id
            JOIN `advert-region_count` cnt ON cnt.id_region = region.id AND category.id = cnt.id_category
            WHERE region.id_country = ?i AND cnt.`count` >= 10
            ORDER BY region.region_name_ru ASC, category.category_name ASC
        ", $country_id);

        while ($row = $res->fetchAssoc()) {
            if (!isset($region[$row['region_id']])) {
                $region[$row['region_id']] = ['name' => $row['region_name_ru2'], 'data' => []];
            }
            $region[$row['region_id']]['data'][] = $row;
        }

        return [$country, $region];
    }

    /**
     * Находит потомков узла.
     *
     * @param int $id
     * @return CoverArray
     * @throws MySqlException
     */
    public function findChilds(int $id): CoverArray
    {
        $memcacheKey = md5(implode('', [__METHOD__, $id]));
        if ($data = Context::getInstance()->getMemcache()->get($memcacheKey)) {
            return $data;
        }

        $data = parent::loadChilds($id);

        Context::getInstance()->getMemcache()->set($memcacheKey, $data, false, 60 * 60);

        return $data;
    }

    /**
     * Находит ID's потомков узла (обращение к таблице `category-category_childs`).
     *
     * @param int $id
     * @return CoverArray
     * @throws MySqlException
     */
    public function findChildsIds(int $id): CoverArray
    {
        $key = implode('', [__METHOD__, $id]);
        if ($data = Context::getInstance()->getMemcache()->get($key)) {
            return $data;
        }

        $sql = '
            /* ?f */
            SELECT `child_id` 
            FROM `category-category_childs` FORCE INDEX (`category_id`) 
            WHERE `category_id` = ?i
        ';

        $res = $this->getDb()->query($sql, __METHOD__, $id);

        $data = new CoverArray();

        if ($res->getNumRows()) {
            while ($child = $res->fetchAssoc()) {
                $data->append($child['child_id']);
            }
        }

        Context::getInstance()->getMemcache()->set($key, $data, false, 60 * 60);

        return $data;
    }

    /**
     * Находит категорию по URL
     *
     * @param string $url
     * @return Category
     */
    public function findByUrl(string $url): Category
    {
        $key = implode('', [__FUNCTION__, $url]);
        if ($data = Context::getInstance()->getMemcache()->get($key)) {
            return $data;
        }

        $params['where'] = [
            Category::getPropertyFieldName('url') . ' = "?s"' => [$url]
        ];

        $data = parent::findModelByParams($params);

        Context::getInstance()->getMemcache()->set($key, $data, false, 60 * 60);

        return $data;
    }

    /**
     * Сохраняет объект Категории.
     * Этот метод не должен переопределять @see CommonMapper::saveModel()
     *
     * @param Category $category
     * @return Category
     * @throws MySqlException
     */
    public function saveCategory(Category $category): Category
    {
        if (!$category->getId()) {
            parent::saveModel($category);
            $this->updateOrderField($category);
            $category->updateUrl();
        } else {
            parent::saveModel($category);
            $category->updateUrl();

            // получаем подчинённые узлы
            $tree = $this->loadSubTreeWithoutSpecifiedLevel($category->getId());
            // изменяем их URL-адреса
            $tree = $this->changeTreeUrls($tree, $category->getUrl());
            // сохраняем подчинённые
            $this->saveTree($tree);
        }

        return $category;
    }

    /**
     * Сохраняет дерево категорий.
     *
     * @param CoverArray $tree
     * @throws MySqlException
     */
    public function saveTree(CoverArray $tree): void
    {
        foreach ($tree as $category) {
            parent::saveModel($category);

            if ($category->getTree() && $category->getTree()->count()) {
                $this->saveTree($category->getTree());
            }
        }
    }

    /**
     * Дерево категорий, в которых пользователь разместил свои объявления.
     *
     * @param int $id_user
     * @return CoverArray
     * @throws MySqlException
     */
    public function loadPathAllUserCategories(int $id_user): CoverArray
    {
        $memcacheKey = md5(implode('', [__METHOD__, $id_user]));
        if ($data = Context::getInstance()->getMemcache()->get($memcacheKey)) {
            return $data;
        }

        $sql = '
        WITH recursive cte(id, category_name, pid, category_indent, `order`) AS ( 
            SELECT id, category_name, pid, category_indent, `order`
            FROM category 
            WHERE id IN (SELECT DISTINCT advert_category id FROM `advert` WHERE advert_id_user = ?i) 
            UNION 
            SELECT c.id, c.category_name, c.pid, c.category_indent, c.`order`
            FROM category c INNER JOIN cte ON c.id = cte.pid
        ) SELECT * FROM cte ORDER BY `order` DESC
        ';

        $statement = $this->getDb()->query($sql, $id_user);
        $data = $this->createTreeByStatement($statement);

        Context::getInstance()->getMemcache()->set($memcacheKey, $data, false, 60 * 10);

        return $data;
    }

    /**
     * Изменяет URL адреса дерева, добавляя поочередно
     * к каждому следующему узлу префикс, состоящий из предыдущего URL.
     * В качестве начального URL передается строка $url.
     *
     * @param CoverArray $tree дерево категорий
     * @param string $url префикс URL для всех URL адресов
     * @return CoverArray
     */
    private function changeTreeUrls(CoverArray $tree, string $url): CoverArray
    {
        foreach ($tree as $key => $category) {
            $tree->item($key)->setUrl($url . $tree->item($key)->getAlias() . '/');

            $tree->item($key)->setTree(
                $this->changeTreeUrls($tree->item($key)->getTree(), $tree->item($key)->getUrl())
            );
        }

        return $tree;
    }

    /**
     * Методы для работы с "весом" строки в списке строк.
     *
     * Описание: После добавлении статьи берется значение поля id (autoincrement)
     * добавленной статьи и дублируется в поле order_id.
     * При нажатии кнопки "вверх" на текущей статье -
     * 1. беру максимальное предыдущее значение order_id не равное текущему (обменная статья)
     * 2. меняю order_id обменной статьи на временное (0)
     * 3. меняю order_id текущей статьи на order_id обменной статьи
     * 4. меняю order_id обменной статьи на order_id текущей статьи
     *
     * При необходимости вынести в трейты.
     */

    /**
     * Поднимает запись в иерархии на одну позицию выше.
     * Используя метод, нужно, быть уверенным в том,
     * что в таблице есть поле `order` предназначенное для сортировки.
     *
     * @param Category $object
     * @throws MySqlException
     */
    public function motionUp(Category $object): void
    {
        $sql = '
            SELECT `id`, `order`
            FROM ?f
            WHERE `order` > (
                SELECT `order`
                FROM ?f
                WHERE `id` = ?i
            )  AND `pid` = ?i
            ORDER BY `order` ASC
            LIMIT 0, 1';

        $res = $this->getDb()->query(
            $sql,
            $this->getTableName(),
            $this->getTableName(),
            $object->getId(),
            $object->getPid()
        );

        [$down_id, $new_order] = $res->fetchRow();

        if ($down_id && $new_order) {
            $sql = '
                SELECT `order`
                FROM ?f
                WHERE `id` = ?i AND `pid` = ?i';

            $res = $this->getDb()->query(
                $sql,
                $this->getTableName(),
                $object->getId(),
                $object->getPid()
            );

            $down_order = $res->getOne();

            $sql = '
                UPDATE ?f
                SET `order` = ?i
                WHERE `id` = ?i AND `pid` = ?i';

            $this->getDb()->query(
                $sql,
                $this->getTableName(),
                $down_order,
                $down_id,
                $object->getPid()
            );

            $sql = '
                UPDATE ?f
                SET `order` = ?i
                WHERE `id` = ?i AND `pid` = ?i';

            $this->getDb()->query(
                $sql,
                $this->getTableName(),
                $new_order,
                $object->getId(),
                $object->getPid()
            );
        }
    }

    /**
     * Опускает запись в иерархии на одну позицию ниже.
     * Используя метод, нужно, быть уверенным в том,
     * что в таблице есть поле `order` предназначенное для сортировки.
     *
     * @param Category $object
     * @throws MySqlException
     */
    public function motionDown(Category $object): void
    {
        $sql = '
            SELECT `id`, `order`
            FROM ?f
            WHERE `order` < (
                SELECT `order`
                FROM ?f
                WHERE `id` = ?i
            ) AND `pid` = ?i
            ORDER BY `order` DESC
            LIMIT 0, 1';

        $res = $this->getDb()->query(
            $sql,
            $this->getTableName(),
            $this->getTableName(),
            $object->getId(),
            $object->getPid()
        );

        [$up_id, $new_order] = $res->fetchRow();

        if ($up_id && $new_order) {
            $sql = '
                SELECT `order`
                FROM ?f
                WHERE `id` = ?i AND `pid` = ?i';

            $res = $this->getDb()->query(
                $sql,
                $this->getTableName(),
                $object->getId(),
                $object->getPid()
            );

            $up_order = $res->getOne();

            $sql = '
                UPDATE ?f
                SET `order` = ?i
                WHERE `id` = ?i AND `pid` = ?i';

            $this->getDb()->query(
                $sql,
                $this->getTableName(),
                $up_order,
                $up_id,
                $object->getPid()
            );

            $sql = '
                UPDATE ?f
                SET `order` = ?i
                WHERE `id` = ?i AND `pid` = ?i';

            $this->getDb()->query(
                $sql,
                $this->getTableName(),
                $new_order,
                $object->getId(),
                $object->getPid()
            );
        }
    }

    /**
     * Возвращает родительскую категорию для категории $category.
     *
     * @param int $pid
     * @return Category
     */
    public function findParentCategory(int $pid): Category
    {
        return parent::findModelByParams([
            'where' => [
                '?f = "?i"' => [Category::getPropertyFieldName('id'), $pid]
            ]
        ]);
    }

    /**
     * Обновляет поле `order` таблицы на ID только что вставленной записи.
     * Вызывается сразу после метода saveModel (вручную).
     * Применяется для таблиц, где используется сортировка ($this->motionUp() и $this->motionDown()).
     *
     * @param Category $category
     * @throws MySqlException
     */
    protected function updateOrderField(Category $category): void
    {
        $tableMetadata = parent::getTableMetadata();

        if (!empty($tableMetadata['order'])) {
            $this->getDb()->query(
                'UPDATE ?f SET `order` = ?i WHERE `id` = ?i',
                $this->getTableName(),
                $category->getId(),
                $category->getId()
            );
        }
    }
}