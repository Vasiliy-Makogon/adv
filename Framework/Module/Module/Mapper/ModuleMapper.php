<?php

namespace Krugozor\Framework\Module\Module\Mapper;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Mapper\CommonMapper;

class ModuleMapper extends CommonMapper
{
    /**
     * Возвращает список записей для административной части.
     *
     * @param array $params
     * @return CoverArray
     */
    public function findListForBackend(array $params = []): CoverArray
    {
        $params['what'] = 'SQL_CALC_FOUND_ROWS *';

        return parent::findModelListByParams($params);
    }
}