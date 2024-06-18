<?php

namespace Krugozor\Framework\Module\User\Mapper;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Context;
use Krugozor\Framework\Mapper\CommonMapper;
use Krugozor\Framework\Module\User\Model\Country;

class CountryMapper extends CommonMapper
{
    /** @var string Активные страны. */
    CONST SQL_FIND_LIST_ACTIVE_COUNTRY = '
            SELECT `id`, `country_name_ru`
            FROM ?f
            WHERE `country_active` = 1
            ORDER BY `weight` DESC';

    /**
     * Метод для получения списка активных стран для Ajax-ответа.
     *
     * @return CoverArray
     */
    public function getListForSelectOptions(): CoverArray
    {
        $result = parent::findModelListBySql(self::SQL_FIND_LIST_ACTIVE_COUNTRY, $this->getTableName());

        $data = new CoverArray();

        if ($result->count()) {
            foreach ($result as $element) {
                $data->append(array($element->getId(), $element->getNameRu()));
            }
        }

        return $data;
    }

    /**
     * Возвращает список активных стран.
     *
     * @return CoverArray
     */
    public function getListActiveCountry(): CoverArray
    {
        return parent::findModelListBySql(self::SQL_FIND_LIST_ACTIVE_COUNTRY, $this->getTableName());
    }

    /**
     * Находит страну по имени в транслите.
     *
     * @param string $name_en
     * @return Country
     */
    public function findByNameEn(string $name_en): Country
    {
        $memcacheKey = md5(implode('', [__METHOD__, $name_en]));
        if ($data = Context::getInstance()->getMemcache()->get($memcacheKey)) {
            return $data;
        }

        $sql = '
            /* ?f */
            SELECT * 
            FROM `user-country` FORCE INDEX (`name_en`)
            WHERE `country_name_en` = "?s"';

        $data = parent::findModelBySql($sql, __METHOD__, $name_en);

        Context::getInstance()->getMemcache()->set($memcacheKey, $data, false, 60 * 60 * 24 * 30);

        return $data;
    }

    /**
     * Возвращает список записей для административной части.
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