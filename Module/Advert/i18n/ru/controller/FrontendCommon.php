<?php

use Krugozor\Framework\Registry;

return [
    'mail' => [
        'header' => [
            'advert_was_saved' => 'Ваше объявление размещено',
        ]
    ],

    'notification' => [
        'header' => [
            'action_attention' => 'Обратите внимание',
            'advert_close' => 'Показ приостановлен',
            'advert_need_payment' => 'Необходимо оплатить услугу активации объявления',
        ],
        'message' => [
            'bad_id_advert' => '<p>Указан некорректный идентификатор объявления.</p>',
            'advert_does_not_exist' => '<p>Запрошенное объявление не существует.</p>',

            'advert_delete' => '<p>Объявление &laquo;<strong>{advert_header}</strong>&raquo; удалено.</p>',

            'advert_date_create_update' => '<p>Объявление &laquo;<strong>{advert_header}</strong>&raquo; в течение нескольких минут 
                будет поднято в результатах поиска. Это значит, его увидят больше посетителей сайта ' .
                Registry::getInstance()->get('HOSTINFO.DOMAIN_AS_TEXT') . ' . 
                Следующее поднятие данного объявления в поиске возможно через один час.</p>',

            'advert_date_create_not_update' => '<p>Объявление &laquo;<strong>{advert_header}</strong>&raquo; не может 
                быть поднято, т.к. недавно создано или уже было поднято в результатах поиска менее одного часа назад. 
                Повторите попытку после {date} мин.</p>',

            'advert_active_0' => '<p>Показ объявления &laquo;<strong>{advert_header}</strong>&raquo; приостановлен.</p>',
            'advert_active_1' => '<p>Показ объявления  &laquo;<strong>{advert_header}</strong>&raquo; возобновлён.</p>',

            'advert_close_user' => '<p>Показ объявления приостановлен автором объявления.</p>',
            'advert_close_user_ban' => '<p>Показ объявления приостановлен, т.к. автор объявления был заблокирован в ' .
                'связи с нарушением условий <a href="/help/terms_of_service/">пользовательского соглашения</a> сайта.</p>',
            'advert_close' => '<p>Показ объявления приостановлен, т.к. объявление было размещено ' .
                'с нарушением условий <a href="/help/terms_of_service/">пользовательского соглашения</a> сайта.</p>',

            // Не оплачено ничего
            'advert_save_without_payments' =>
                '<p>Объявление &laquo;<strong>{advert_header}</strong>&raquo; успешно сохранено 
                    и будет доступно на сайте через нескольких минут.</p>
        
                <div class="paragraph">
                    <h3>Хотите привлечь больше внимания на своё объявление и повысить его эффективность?</h3>
                    <div class="two_column">
                        <div class="left_column">
                            <div class="left_column_content">
                                <p class="price_description"><span class="price">Всего за ' . Registry::getInstance()->get('PAYMENTS.PAYMENT_ACTION_TOP') . ' рублей
                                вы можете сделать ваше объявление более заметным</span>, воспользовавшись услугой 
                                &laquo;<b>Выделить объявление</b>&raquo; &mdash; 30 дней объявление будет выделено особым цветом, 
                                будет находиться выше других бесплатных объявлений, соответственно его увидят больше посетителей сайта:</p>
                                <p><img src="/img/local/vip.jpg" alt=""></p>
                                <div class="pseudo_button"
                                    onclick="window.location.href=\'{kassa_auth_url_vip}\'">
                                    Выделить объявление и разместить в VIP-линейке <span class="nowrap">за ' . Registry::getInstance()->get('PAYMENTS.PAYMENT_ACTION_TOP') . ' руб.</span>
                                </div>
                            </div>
                        </div>
                        <div class="right_column">
                            <div class="right_column_content">
                                <p class="price_description"><span class="price">Всего за ' . Registry::getInstance()->get('PAYMENTS.PAYMENT_ACTION_SPECIAL') . ' рублей 
                                вы можете сделать ваше объявление более заметным</span>, воспользовавшись услугой &laquo;<b>Спецпредложение</b>&raquo;
                                &mdash; объявление будет размещено на 30 дней в блоке &laquo;Специальные&nbsp;предложения&raquo; на всех основных страницах сайта 
                                в увеличенных пропорциях, максимально привлекая внимание посетителей сайта:</p>
                                <p><img alt="" src="/img/local/special.jpg"></p>
                                <div class="pseudo_button"
                                    onclick="window.location.href=\'{kassa_auth_url_special}\'">
                                        Разместить объявление в блоке &laquo;Специальные предложения&raquo; <span class="nowrap">за ' . Registry::getInstance()->get('PAYMENTS.PAYMENT_ACTION_SPECIAL') . ' руб.</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
        
                <div class="paragraph">
                <h3>Также обратите внимание</h3>
                    <p>Наибольшая эффективность от поданного объявления достигается только в том случае, если Ваше объявление 
                        увидит как можно больше людей. Вы можете сами повысить количество просмотров объявления путём 
                        размещения ссылки на объявление в социальных сетях, форумах или в блогах. 
                        Для этого воспользуйтесь следующим кодом:</p>
                    <dl class="notification_advert_share">
                        <dt><p>Код для вставки в форумы и блоги, поддерживающие BB-теги:</p></dt>
                        <dd><div class="codes_for_blogs">[b][url=' . Registry::getInstance()->get('HOSTINFO.HOST') . '/advert/{id}.xhtml]{advert_header}[/url][/b]</div></dd>
                        <dt><p>Код для вставки в форумы и блоги, поддерживающие HTML-код:</p></dt>
                        <dd><div class="codes_for_blogs">&lt;p&gt;&lt;a href="' . Registry::getInstance()->get('HOSTINFO.HOST') . '/advert/{id}.xhtml"&gt;&lt;strong&gt;{advert_header}&lt;/strong&gt;&lt;/a&gt;&lt;/p&gt;</div></dd>
                        <dt><p>Постоянная ссылка на это объявление:</p></dt>
                        <dd><div class="codes_for_blogs">' . Registry::getInstance()->get('HOSTINFO.HOST_AS_TEXT') . '/advert/{id}.xhtml</div></dd>
                    </dl>
                </div>',

            // Оплачено только спецпредложение
            'advert_save_with_special' =>
                '<p>Объявление &laquo;<strong>{advert_header}</strong>&raquo; успешно сохранено
                    и доступно для поиска.</p>

                <div class="paragraph">
                    <div class="two_column">
                        <div class="left_column">
                            <div class="left_column_content">
                                <h3>Хотите привлечь больше внимания на своё объявление и повысить его эффективность?</h3>
                                <p class="price_description"><span class="price">Всего за ' . Registry::getInstance()->get('PAYMENTS.PAYMENT_ACTION_TOP') . ' рублей
                                вы можете сделать ваше объявление более заметным</span>, воспользовавшись услугой 
                                &laquo;<b>Выделить объявление</b>&raquo; &mdash; 30 дней объявление будет выделено особым цветом, 
                                будет находиться выше других бесплатных объявлений, соответственно его увидят больше посетителей сайта:</p>
                                <p><img src="/img/local/vip.jpg" alt=""></p>
                                <div class="pseudo_button"
                                    onclick="window.location.href=\'{kassa_auth_url_vip}\'">
                                    Выделить объявление и разместить в VIP-линейке <span class="nowrap">за ' . Registry::getInstance()->get('PAYMENTS.PAYMENT_ACTION_TOP') . ' руб.</span>
                                </div>
                            </div>
                        </div>
                        <div class="right_column">
                            <div class="right_column_content">
                                <h3>Также обратите внимание</h3>
                                <p>Наибольшая эффективность от поданного объявления достигается только в том случае, если Ваше объявление 
                                    увидит как можно больше людей. Вы можете сами повысить количество просмотров объявления путём 
                                    размещения ссылки на объявление в социальных сетях, форумах или в блогах. 
                                    Для этого воспользуйтесь следующим кодом:</p>
                                <dl class="notification_advert_share">
                                    <dt><p>Код для вставки в форумы и блоги, поддерживающие BB-теги:</p></dt>
                                    <dd><div class="codes_for_blogs">[b][url=' . Registry::getInstance()->get('HOSTINFO.HOST') . '/advert/{id}.xhtml]{advert_header}[/url][/b]</div></dd>
                                    <dt><p>Код для вставки в форумы и блоги, поддерживающие HTML-код:</p></dt>
                                    <dd><div class="codes_for_blogs">&lt;p&gt;&lt;a href="' . Registry::getInstance()->get('HOSTINFO.HOST') . '/advert/{id}.xhtml"&gt;&lt;strong&gt;{advert_header}&lt;/strong&gt;&lt;/a&gt;&lt;/p&gt;</div></dd>
                                    <dt><p>Постоянная ссылка на это объявление:</p></dt>
                                    <dd><div class="codes_for_blogs">' . Registry::getInstance()->get('HOSTINFO.HOST_AS_TEXT') . '/advert/{id}.xhtml</div></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>',

            // Оплачен только VIP
            'advert_save_with_vip' =>
                '<p>Объявление &laquo;<strong>{advert_header}</strong>&raquo; успешно сохранено
                    и доступно для поиска.</p>

                <div class="paragraph">
                    <div class="two_column">
                        <div class="left_column">
                            <div class="left_column_content">
                                <h3>Хотите привлечь больше внимания на своё объявление и повысить его эффективность?</h3>
                                <p class="price_description"><span class="price">Всего за ' . Registry::getInstance()->get('PAYMENTS.PAYMENT_ACTION_SPECIAL') . ' рублей 
                                вы можете сделать ваше объявление более заметным</span>, воспользовавшись услугой &laquo;<b>Спецпредложение</b>&raquo;
                                &mdash; объявление будет размещено на 30 дней в блоке &laquo;Специальные&nbsp;предложения&raquo; на всех основных страницах сайта 
                                в увеличенных пропорциях, максимально привлекая внимание посетителей сайта:</p>
                                <p><img alt="" src="/img/local/special.jpg"></p>
                                <div class="pseudo_button"
                                    onclick="window.location.href=\'{kassa_auth_url_special}\'">
                                        Разместить объявление в блоке &laquo;Специальные предложения&raquo; <span class="nowrap">за ' . Registry::getInstance()->get('PAYMENTS.PAYMENT_ACTION_SPECIAL') . ' руб.</span>
                                </div>
                            </div>
                        </div>
                        <div class="right_column">
                            <div class="right_column_content">
                                <h3>Также обратите внимание</h3>
                                <p>Наибольшая эффективность от поданного объявления достигается только в том случае, если Ваше объявление
                                    увидит как можно больше людей. Вы можете сами повысить количество просмотров объявления путём
                                    размещения ссылки на объявление в социальных сетях, форумах или в блогах.
                                    Для этого воспользуйтесь следующим кодом:</p>
                                <dl class="notification_advert_share">
                                    <dt><p>Код для вставки в форумы и блоги, поддерживающие BB-теги:</p></dt>
                                    <dd><div class="codes_for_blogs">[b][url=' . Registry::getInstance()->get('HOSTINFO.HOST') . '/advert/{id}.xhtml]{advert_header}[/url][/b]</div></dd>
                                    <dt><p>Код для вставки в форумы и блоги, поддерживающие HTML-код:</p></dt>
                                    <dd><div class="codes_for_blogs">&lt;p&gt;&lt;a href="' . Registry::getInstance()->get('HOSTINFO.HOST') . '/advert/{id}.xhtml"&gt;&lt;strong&gt;{advert_header}&lt;/strong&gt;&lt;/a&gt;&lt;/p&gt;</div></dd>
                                    <dt><p>Постоянная ссылка на это объявление:</p></dt>
                                    <dd><div class="codes_for_blogs">' . Registry::getInstance()->get('HOSTINFO.HOST_AS_TEXT') . '/advert/{id}.xhtml</div></dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>',

            // Оплачены все услуги.
            'advert_save_with_payments' =>
                '<p>Объявление &laquo;<strong>{advert_header}</strong>&raquo; успешно сохранено и доступно для поиска.</p>
                <div class="paragraph">
                    <h3>Обратите внимание</h3>
                    <p>Наибольшая эффективность от поданного объявления достигается только в том случае, если Ваше объявление 
                        увидит как можно больше людей. Вы можете сами повысить количество просмотров объявления путём 
                        размещения ссылки на объявление в социальных сетях, форумах или в блогах. 
                        Для этого воспользуйтесь следующим кодом:</p>
                    <dl class="notification_advert_share">
                        <dt><p>Код для вставки в форумы и блоги, поддерживающие BB-теги:</p></dt>
                        <dd><div class="codes_for_blogs">[b][url=' . Registry::getInstance()->get('HOSTINFO.HOST') . '/advert/{id}.xhtml]{advert_header}[/url][/b]</div></dd>
                        <dt><p>Код для вставки в форумы и блоги, поддерживающие HTML-код:</p></dt>
                        <dd><div class="codes_for_blogs">&lt;p&gt;&lt;a href="' . Registry::getInstance()->get('HOSTINFO.HOST') . '/advert/{id}.xhtml"&gt;&lt;strong&gt;{advert_header}&lt;/strong&gt;&lt;/a&gt;&lt;/p&gt;</div></dd>
                        <dt><p>Постоянная ссылка на это объявление:</p></dt>
                        <dd><div class="codes_for_blogs">' . Registry::getInstance()->get('HOSTINFO.HOST_AS_TEXT') . '/advert/{id}.xhtml</div></dd>
                    </dl>
                </div>',

            'advert_need_payment' => '
                <p>Ваше объявление &laquo;<b>{advert_header}</b>&raquo; добавлено, но на сайте пока ещё не отображается &mdash;
                    за размещение объявлений в раздел &laquo;<b>{category_name}</b>&raquo; взимается разовая плата в размере
                    <span class="price">' .
                    Registry::getInstance()->get('PAYMENTS.PAYMENT_ACTION_ACTIVATE') .
                '&nbsp;руб</span>.</p>
                <p>Что бы объявление было доступно всему интернету, пожалуйста,
                произведите процедуру оплаты объявления любым удобным для Вас способом:</p>
                     <input class="button_margin_auto"
                        onclick="window.location.href=\'{kassa_auth_url_payment}\'"
                        type="button"
                        value="Активировать объявление">
                
            ',
        ]
    ],
];