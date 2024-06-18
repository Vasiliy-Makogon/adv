<?php

use Krugozor\Framework\Registry;

return [
    'title' => [
        Registry::getInstance()->get('HOSTINFO.SITE_NAME')
    ],
];