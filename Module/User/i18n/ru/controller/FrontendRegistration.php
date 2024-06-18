<?php

use Krugozor\Framework\Registry;

return [
    'title' => ['Подача объявления. Шаг 1 из 2. Регистрация'],
    'meta' => [
        'description' => 'Регистрация на сайте объявлений',
        'keywords' => 'регистрация сайт объявлений, создать аккаунт сайт объявлений',
    ],

    'mail' => [
        'header' => [
            'send_mail_user_header' => 'Ваши регистрационные данные на сайте ' . Registry::getInstance()->get('HOSTINFO.DOMAIN_AS_TEXT'),
        ]
    ],

    'notification' => [
        'header' => [
            'you_registration_ok' => 'Поздравляем с успешной регистрацией на сайте!',
        ],
        'message' => [
            'you_registration_with_email' => '<p>Вы указали при регистрации email-адрес, на него придет письмо с Вашим логином и паролем. ' .
                'Обязательно сохраните данное письмо в надёжном месте, что бы в будущем иметь возможность управлять своими объявлениями на сайте \'' .
                Registry::getInstance()->get('HOSTINFO.DOMAIN_AS_TEXT') .
                '\'. На всякий случай напоминаем, Ваш логин и пароль: ' .
                '<a href="#" onclick="return view_login_password(this, \'{login}\', \'{password}\')">[показать]</a></p>',

            'you_registration_without_email' => '<p>Что бы в будущем иметь возможность управлять своими объявлениями на сайте \'' .
                Registry::getInstance()->get('HOSTINFO.DOMAIN_AS_TEXT') .
                '\', сохраните свой логин и пароль в надёжном месте. ' .
                'На всякий случай напоминаем, Ваш логин и пароль: ' .
                '<a href="#" onclick="return view_login_password(this, \'{login}\', \'{password}\')">[показать]</a></p>',
        ]
    ],
];