<?php
return [
    'title' => [
        'Административная часть'
    ],

    'notification' => [
        'header' => [
            'action_complete' => 'Действие выполнено',
            'action_failed' => 'Действие не может быть выполнено',
            'action_warning' => 'Предупреждение',
        ],
        'message' => [
            'unknown_error' => '<p>Ошибка системы</p>',
            'element_does_not_exist' => '<p>Запрошенный элемент не существует</p>',
            'elements_does_not_exists' => '<p>Запрошенные элементы не существуют</p>',
            'bad_id_element' => '<p>Указан некорректный идентификатор элемента</p>',
            'id_element_not_exists' => '<p>Не указан идентификатор элемента</p>',
            'ids_elements_not_exists' => '<p>Не указаны идентификаторы элементов</p>',
            'data_saved' => '<p>Данные сохранены</p>',
            'data_deleted' => '<p>Данные удалены</p>',
            'forbidden_access' => '<p>У вас нет прав доступа к данному действию</p>',
            'post_errors' => '<p>Произошли ошибки заполнения формы. Пояснения приводятся ниже.</p>',
            'element_motion_up' => '<p>Элемент перемещён на одну позицию выше</p>',
            'element_motion_down' => '<p>Элемент перемещён на одну позицию ниже</p>',
            'unknown_tomotion' => '<p>Неизвестный параметр tomotion.</p>',
            'inside_system' => '<p>Вы вошли в систему</p>',
            'outside_system' => '<p>Вы успешно завершили сеанс работы с системой</p>',
            'missing_email' => '<p>Отсутствует email-адрес</p>',

            'category_does_not_exists' => '<p>Запрошенная категория не существуют</p>',
        ]
    ],

    'content' => [
        'actions' => 'Действия над элементами',
        'id' => 'ID',
        'yes' => 'Да',
        'no' => 'Нет',
        'not_found_request_data' => 'Данных, удовлетворяющих запросу, не найдено',
        'return_to_this_page' => 'возврат на эту страницу',
        'save_changes' => 'Сохранить изменения',
        'select_value' => 'Выберите значение',
        'select_all' => 'Выбрать все',

        'date' => [
            // Именительный
            'months_nominative' => [
                1 => 'Январь',
                2 => 'Февраль',
                3 => 'Март',
                4 => 'Апрель',
                5 => 'Май',
                6 => 'Июнь',
                7 => 'Июль',
                8 => 'Август',
                9 => 'Сентябрь',
                10 => 'Октябрь',
                11 => 'Ноябрь',
                12 => 'Декабрь',
            ],
            // Родительный
            'months_genitive' => [
                1 => 'Января',
                2 => 'Февраля',
                3 => 'Марта',
                4 => 'Апреля',
                5 => 'Мая',
                6 => 'Июня',
                7 => 'Июля',
                8 => 'Августа',
                9 => 'Сентября',
                10 => 'Октября',
                11 => 'Ноября',
                12 => 'Декабря',
            ],
            // Именительный
            'days_nominative' => [
                1 => 'понедельник',
                2 => 'вторник',
                3 => 'среда',
                4 => 'четверг',
                5 => 'пятница',
                6 => 'суббота',
                7 => 'воскресенье'
            ]
        ],
    ]
];