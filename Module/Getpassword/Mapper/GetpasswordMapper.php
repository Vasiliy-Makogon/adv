<?php

namespace Krugozor\Framework\Module\Getpassword\Mapper;

use Krugozor\Framework\Mapper\CommonMapper;
use Krugozor\Framework\Module\Getpassword\Model\Getpassword;

class GetpasswordMapper extends CommonMapper
{
    /**
     * Находит объект по хэшу.
     *
     * @param string $hash
     * @return Getpassword
     */
    public function findByHash(string $hash): Getpassword
    {
        $params = array(
            'where' => [
                'hash = "?s"' => [$hash],
            ],
        );

        return parent::findModelByParams($params);
    }
}