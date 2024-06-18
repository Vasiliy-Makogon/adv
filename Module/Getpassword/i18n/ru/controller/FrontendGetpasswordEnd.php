<?php

use Krugozor\Framework\Registry;

return [
    'title' => ['Восстановление пароля к аккаунту'],
    'mail' => [
        'header' => [
            'send_mail_user' => sprintf('Ваш новый пароль на %s', Registry::getInstance()->get('HOSTINFO.DOMAIN_AS_TEXT')),
        ]
    ],
    'notification' => [
        'header' => [
            'bad_hash' => 'Устаревший запрос',
        ],
        'message' => [
            'bad_hash' => '<p>Запрос на восстановление данных по этой ссылке уже выполнен. Если Вы — инициатор запроса, проверяйте почту.</p>',
            'getpassword_send_message' => '<p>На указанную Вами при регистрации почту высланы данные для авторизации на сайте. Проверяйте почту.</p>',
        ]
    ]
];