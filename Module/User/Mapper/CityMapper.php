<?php

namespace Krugozor\Framework\Module\User\Mapper;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Context;
use Krugozor\Framework\Mapper\CommonMapper;
use Krugozor\Framework\Module\User\Model\Region as RegionModel;
use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Module\User\Model\City as CityModel;

class CityMapper extends CommonMapper
{
    /**
     * Метод для получения списка городов с ключами URl-адресами для Ajax-ответа.
     *
     * @param int $id_region
     * @param int $id_category
     * @return CoverArray
     * @throws MySqlException
     */
    public function getListForSelectOptionsWithsUrl(int $id_region, int $id_category = 0): CoverArray
    {
        $result = $this->getDb()->query(
            "SELECT
                CONCAT('/', c.country_name_en, '/', r.region_name_en, '/', ci.city_name_en, '/categories', IFNULL(ct.category_url, '/')) as `url`,
                ci.city_name_ru as `name`
            FROM `user-country` c
            JOIN `user-region` r ON r.id_country = c.id
            JOIN `user-city` ci ON ci.id_region = r.id
            LEFT JOIN `category` ct ON ct.id = ?i
            WHERE r.id = ?i 
            ORDER BY ci.weight DESC", $id_category, $id_region
        );

        $data = new CoverArray();

        if ($result->getNumRows()) {
            while ($res = $result->fetchAssoc()) {
                $data->append(array($res['url'], $res['name']));
            }
        }

        return $data;
    }

    /**
     * Метод для получения списка городов с ключами ID's для Ajax-ответа.
     *
     * @param int $id_region
     * @return CoverArray
     */
    public function getListForSelectOptions(int $id_region): CoverArray
    {
        $sql = 'SELECT `id`, `city_name_ru` FROM ?f WHERE `id_region` = ?i ORDER BY `weight` DESC';
        $result = parent::findModelListBySql(
            $sql,
            $this->getTableName(),
            $id_region
        );

        $data = new CoverArray();

        if ($result->count()) {
            foreach ($result as $element) {
                $data->append(array($element->getId(), $element->getNameRu()));
            }
        }

        return $data;
    }

    /**
     * Находит город по имени в транслите и объекту региона.
     * Второй параметр необходим для того, что бы исключить нахождение городов с аналогичными названиями.
     *
     * @param string $name_en
     * @param RegionModel $region
     * @return CityModel
     */
    public function findByNameEnAndRegion(string $name_en, RegionModel $region): CityModel
    {
        $key = implode('.', [__FUNCTION__, $name_en, $region->getId()]);
        if ($data = Context::getInstance()->getMemcache()->get($key)) {
            return $data;
        }

        $sql = '
          SELECT * 
          FROM `user-city` FORCE INDEX(`name_en,id_region`)
          WHERE `city_name_en` = "?s" 
          AND `id_region` = ?i';

        $data = parent::findModelBySql($sql, $name_en, $region->getId());

        Context::getInstance()->getMemcache()->set($key, $data, false, 60 * 60 * 24 * 30);

        return $data;
    }

    /**
     * Получение списка городов по объекту региона с join таблицы суммарного кол-ва
     * объявлений по городам, что бы не выводить в списке города без объявлений.
     * Метод для view.
     *
     * @param RegionModel $region
     * @return CoverArray
     */
    public function findListByRegion(RegionModel $region): CoverArray
    {
        $key = implode('.', [__FUNCTION__, $region->getId()]);
        if ($data = Context::getInstance()->getMemcache()->get($key)) {
            return $data;
        }

        $sql = '
            SELECT *, s.`count` AS `advert_count`
            FROM `user-city` c FORCE INDEX (`id_region`)
            JOIN `advert-city_count_sum` s FORCE INDEX (`__id_city`) ON c.id = s.id_city
            WHERE c.id_region = ?i AND s.`count` > 0
            ORDER BY c.weight DESC, c.city_name_ru ASC';

        $data = parent::findModelListBySql($sql, $region->getId());

        Context::getInstance()->getMemcache()->set($key, $data, false, 60 * 60);

        return $data;
    }

    /**
     * Возвращает список записей для админитративной части.
     *
     * @param array $params
     * @return CoverArray
     */
    public function findListForBackend(array $params = array()): CoverArray
    {
        $params['what'] = 'SQL_CALC_FOUND_ROWS *';

        return parent::findModelListByParams($params);
    }
}