<?php
return [
    [   // Системный роутер для CSS
        'pattern' => '~^/css/([a-z0-9_\-]+)/(.+)$~',
        'module' => 'resource',
        'controller' => 'css',
        'aliases' => ['module', 'file'],
    ],
    [   // Системный роутер для SVG
        'pattern' => '~^/svg/([a-z0-9_\-]+)/(.+)$~',
        'module' => 'resource',
        'controller' => 'svg',
        'aliases' => ['module', 'file'],
    ],
    [   // Системный роутер для JavaScript
        'pattern' => '~^/js/([a-z0-9_\-]+)/(.+)$~',
        'module' => 'resource',
        'controller' => 'js',
        'aliases' => ['module', 'file'],
    ],
    [   // Системный роутер для компилируемых CSS
        'pattern' => '~^/ccss/(.+)$~',
        'module' => 'resource',
        'controller' => 'ccss',
        'aliases' => ['enums'],
    ],
    [   // Системный роутер для изображений
        'pattern' => '~^/img/([a-z0-9_\-]+)/(.+)$~',
        'module' => 'resource',
        'controller' => 'img',
        'aliases' => ['module', 'file'],
    ],
    [   // Системный роутер для PDF
        'pattern' => '~^/pdf/([a-z0-9_\-]+)/(.+)$~',
        'module' => 'resource',
        'controller' => 'pdf',
        'aliases' => ['module', 'file'],
    ],
    [
        'pattern' => '~^/fonts/([a-z0-9_\-]+)/(.+)$~',
        'module' => 'resource',
        'controller' => 'fonts',
        'aliases' => ['module', 'file'],
    ],
    [   // Главная страница
        'pattern' => '~^/$~',
        'module' => 'index',
        'controller' => 'index'
    ],
    [   // Административная часть
        'pattern' => '~/admin/?$~',
        'module' => 'authorization',
        'controller' => 'backend-login'
    ],

    // Стаьи
    [
        'pattern' => '~^/articles/$~',
        'module' => 'article',
        'controller' => 'frontend-articles-list',
    ],
    [
        'pattern' => '~^/articles/[0-9a-z_\-]+_([0-9]+)\.html$~',
        'module' => 'article',
        'controller' => 'frontend-article-view',
        'aliases' => ['id']
    ],

    // Роуты сайта объявлений
    [
        'pattern' => '~^/([a-z_\-]+)/categories/$~i',
        'module' => 'category',
        'controller' => 'frontend-categories-list',
        'aliases' => ['country_name_en']
    ],
    [
        'pattern' => '~^/([a-z_\-]+)/([a-z_\-]+)/categories/$~i',
        'module' => 'category',
        'controller' => 'frontend-categories-list',
        'aliases' => ['country_name_en', 'region_name_en']
    ],
    [
        'pattern' => '~^/([a-z_\-]+)/([a-z_\-]+)/([a-z_\-]+)/categories/$~i',
        'module' => 'category',
        'controller' => 'frontend-categories-list',
        'aliases' => ['country_name_en', 'region_name_en', 'city_name_en']
    ],
    [
        'pattern' => '~^/([a-z_\-]+)/categories(/[a-z0-9_/\-]+/)$~i',
        'module' => 'advert',
        'controller' => 'frontend-category-list',
        'aliases' => ['country_name_en', 'category_url']
    ],
    [
        'pattern' => '~^/([a-z_\-]+)/([a-z_\-]+)/categories(/[a-z0-9_/\-]+/)$~i',
        'module' => 'advert',
        'controller' => 'frontend-category-list',
        'aliases' => ['country_name_en', 'region_name_en', 'category_url']
    ],
    [
        'pattern' => '~^/([a-z_\-]+)/([a-z_\-]+)/([a-z_\-]+)/categories(/[a-z0-9_/\-]+/)$~i',
        'module' => 'advert',
        'controller' => 'frontend-category-list',
        'aliases' => ['country_name_en', 'region_name_en', 'city_name_en', 'category_url'],
    ],
    [
        'pattern' => '~^/advert/([0-9]+)\.xhtml$~',
        'module' => 'advert',
        'controller' => 'frontend-advert-view',
        'aliases' => ['id'],
    ],
    [
        'pattern' => '~^/profile/([0-9]+)/$~',
        'module' => 'advert',
        'controller' => 'frontend-user-public-adverts-list',
        'aliases' => ['user'],
    ],

    // Платежи
    [
        'pattern' => '~^/payment/success.xhtml$~',
        'module' => 'Prodamus',
        'controller' => 'Success',
    ],
    [
        'pattern' => '~^/payment/result.xhtml$~',
        'module' => 'Prodamus',
        'controller' => 'Result',
    ],

    [
        'pattern' => '~^/help/?$~',
        'module' => 'help',
        'controller' => 'index',
    ],
];