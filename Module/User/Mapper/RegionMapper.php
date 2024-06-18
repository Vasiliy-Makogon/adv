<?php

namespace Krugozor\Framework\Module\User\Mapper;

use Krugozor\Database\MySqlException;
use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Context;
use Krugozor\Framework\Mapper\CommonMapper;
use Krugozor\Framework\Module\User\Model\Country as CountryModel;
use Krugozor\Framework\Module\User\Model\Region as RegionModel;

class RegionMapper extends CommonMapper
{
    /**
     * Метод для получения списка регионов с ключами URl-адресами для Ajax-ответа.
     *
     * @param int $id_country
     * @param int $id_category
     * @return CoverArray
     * @throws MySqlException
     */
    public function getListForSelectOptionsWithsUrl(int $id_country, int $id_category = 0): CoverArray
    {
        $result = $this->getDb()->query(
            "SELECT
                CONCAT('/', c.country_name_en, '/', r.region_name_en, '/categories', IFNULL(ct.category_url, '/')) as `url`,
                r.region_name_ru as `name`
            FROM `user-country` c
            JOIN `user-region` r ON r.id_country = c.id
            LEFT JOIN `category` ct ON ct.id = ?i
            WHERE c.id = ?i 
            ORDER BY r.weight DESC", $id_category, $id_country
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
     * Метод для получения списка регионов с ключами ID's для Ajax-ответа.
     *
     * @param int $id_country
     * @return CoverArray
     */
    public function getListForSelectOptions(int $id_country): CoverArray
    {
        $sql = 'SELECT `id`, `region_name_ru` FROM ?f WHERE `id_country` = ?i ORDER BY `weight` DESC';
        $result = parent::findModelListBySql(
            $sql,
            $this->getTableName(),
            $id_country
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

    /**
     * Находит регион по имени в транслите.
     *
     * @param string $name_en
     * @return RegionModel
     */
    public function findByNameEn(string $name_en): RegionModel
    {
        $key = implode('.', [__FUNCTION__, $name_en]);
        if ($data = Context::getInstance()->getMemcache()->get($key)) {
            return $data;
        }

        $sql = '
          SELECT * 
          FROM `user-region` FORCE INDEX(`name_en`)
          WHERE `region_name_en` = "?s"';

        $data = parent::findModelBySql($sql, $name_en);

        Context::getInstance()->getMemcache()->set($key, $data, false, 60 * 60 * 24 * 30);

        return $data;
    }

    /**
     * Возвращает список активных регионов.
     *
     * @return CoverArray
     */
    public function getListActiveRegion(): CoverArray
    {
        $sql = '
          SELECT r.`id`, r.`region_name_ru`
          FROM ?f r
          JOIN `user-country` c ON c.id = r.id_country 
          ORDER BY c.`weight` DESC, r.`weight` DESC';

        return parent::findModelListBySql($sql, $this->getTableName());
    }

    /**
     * Получение списка регионов по объекту страны с join таблицы суммарного кол-ва
     * объявлений по регионам, что бы не выводить в списке регионы без объявлений.
     * Метод для view.
     *
     * @param CountryModel $country
     * @return CoverArray
     */
    public function findListByCountry(CountryModel $country): CoverArray
    {
        $key = implode('.', [__FUNCTION__, $country->getId()]);
        if ($data = Context::getInstance()->getMemcache()->get($key)) {
            return $data;
        }

        $sql = '
            SELECT *, s.`count` AS `advert_count`
            FROM `user-region` r FORCE INDEX (`id_country`)
            JOIN `advert-region_count_sum` s FORCE INDEX (`__id_region`) ON r.id = s.id_region
            WHERE r.id_country = ?i AND s.`count` > 0
            ORDER BY r.weight DESC, r.region_name_ru ASC';

        $data = parent::findModelListBySql($sql, $country->getId());

        Context::getInstance()->getMemcache()->set($key, $data, false, 60 * 60);

        return $data;
    }
}