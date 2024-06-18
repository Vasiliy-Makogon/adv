<?php
return array
(
    'title' => array('Оплата услуг'),

    'notification' => [
        'header' => [
            'fail' => 'Ошибка',
        ],
        'message' => [
            'not_found_advert_id' => '<p>В запросе не указан ID объявления.</p>',
            'not_found_advert' => '<p>Объявления с ID {id} на сайте нет, проверьте правильность набора данных.</p>',
            'undefined_action' => '<p>Неизвестный параметр action или отсутствует.</p>',

            'advert_set_vip' => '<p>Платёж успешно совершён.</p>' .
                '<p>Объявление «<b>{advert_header}</b>» будет выделено и поднято в поиске в течение нескольких минут (обычно - в течение 3-5 минут).</p>' .
                '<p>Это значит, что его увидят больше посетителей сайта и Ваше объявление с большей вероятностью 
                        попадёт в кэш поисковых систем интернета, что со временем даст постоянный приток посетителей 
                        на Ваше объявление.</p>' .
                '<p>С уважением, команда {http_host}</p>' .
                '<p>&nbsp;</p>' .
                '<p>Сейчас вы можете: <a href="/advert/{id}.xhtml">Посмотреть своё объявление</a> | 
                        <a href="/advert/frontend-edit-advert/">Подать еще одно объявление</a> | 
                        <a href="/user/frontend-registration/">Зарегистрироваться</a> |
                        <a href="/authorization/frontend-login/">Перейти в свой личный кабинет</a></p>',

            'advert_set_payment' => '<p>Платёж успешно совершён.</p>' .
                '<p>Объявление «<b>{advert_header}</b>» активировано и будет размещено на сайте в течение нескольких минут.</p>' .
                '<p>С уважением, команда {http_host}</p>' .
                '<p>&nbsp;</p>' .
                '<p>Сейчас вы можете: <a href="/advert/{id}.xhtml">Посмотреть своё объявление</a> | 
                            <a href="/advert/frontend-edit-advert/">Подать еще одно объявление</a> | 
                            <a href="/user/frontend-registration/">Зарегистрироваться</a> |
                            <a href="/authorization/frontend-login/">Перейти в свой личный кабинет</a></p>'
        ]
    ],

    'content' => [
        'bad_signature' => 'Неверная сигнатура платежа. Запрос: {request_data}',
        'not_found_advert_id' => 'Не указан ID объявления. Запрос: {request_data}',
        'not_found_advert' => 'Объявления с ID {id} на сайте нет, проверьте правильность набора данных. Запрос: {request_data}',
        'undefined_action' => 'Неизвестный параметр action {action}. Запрос: {request_data}',
    ]
);