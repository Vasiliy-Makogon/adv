<?php

namespace Krugozor\Framework\Module\Module\Mapper;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Mapper\CommonMapper;
use Krugozor\Framework\Module\Module\Model\Module;

class ControllerMapper extends CommonMapper
{
    /**
     * Возвращает список контроллеров модуля.
     *
     * @param Module $module
     * @return CoverArray
     */
    public function findControllerModelListByModule(Module $module): CoverArray
    {
        $params = [
            'where' => array('controller_id_module = ?i' => array($module->getId())),
            'order' => array('controller_name' => 'ASC')
        ];

        return parent::findModelListByParams($params);
    }
}