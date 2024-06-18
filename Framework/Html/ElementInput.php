<?php

namespace Krugozor\Framework\Html;

use DOMDocument;

class ElementInput extends Element
{
    /**
     * Сравниваемое значение для radio и checkbox-ов.
     *
     * @access protected
     * @var string
     */
    private $checked_value;

    public function __construct($type = null)
    {
        parent::__construct();

        $this->attrs = array
        (
            // Устанавливает фильтр на типы файлов, которые вы можете отправить через поле загрузки файлов.
            'accept' => 'ContentTypes',
            // Альтернативный текст для кнопки с изображением.
            'alt' => 'Text',
            // Включает или отключает автозаполнение. HTML5
            'autocomplete' => array('on', 'off'),
            // Переход к элементу с помощью комбинации клавиш.
            'accesskey' => 'Character',
            // Указывает, должен ли входной элемент получить фокус при загрузке страницы
            'autofocus' => array('autofocus'),
            // Предварительно активированный переключатель или флажок.
            'checked' => array('checked'),
            // Блокирует доступ и изменение элемента.
            'disabled' => array('disabled'),
            // Связывает поле с формой по её идентификатору.
            'form' => 'ID',
            // Определяет адрес обработчика формы.
            'formaction' => 'URI',
            // Устанавливает способ кодирования данных формы при их отправке на сервер.
            'formenctype' => array('application/x-www-form-urlencoded', '"multipart/form-data', 'text/plain'),
            // Сообщает браузеру каким методом следует передавать данные формы на сервер.
            'formmethod' => array('get', 'post'),
            // Отменяет встроенную проверку данных на корректность.
            'formnovalidate' => array('formnovalidate'),
            // Определяет окно или фрейм в которое будет загружаться результат, возвращаемый обработчиком формы.
            // @todo: добавить по спецификации возможность указывать имя окна
            'formtarget' => array('_blank', '_self', '_parent', '_top'),
            // Указывает на список вариантов, которые можно выбирать при вводе текста.
            'list' => 'ID',
            // Устанавливает верхнее значение для ввода числа или даты в поле формы.
            // @todo: добавить по спецификации возможность указывать дату
            'max' => 'Number',
            // Максимальное количество символов разрешенных в тексте.
            'maxlength' => 'Number',
            // Нижнее значение для ввода числа или даты.
            'min' => 'Number',
            // Позволяет загрузить несколько файлов одновременно.
            'multiple' => array('multiple'),
            // Имя поля, предназначено для того, чтобы обработчик формы мог его идентифицировать.
            'name' => 'CDATA',
            // Устанавливает шаблон ввода.
            'pattern' => 'CDATA',
            // Выводит подсказывающий текст.
            'placeholder' => 'CDATA',
            // Устанавливает, что поле не может изменяться пользователем.
            'readonly' => array('readonly'),
            // Обязательное для заполнения поле.
            'required' => array('required'),
            // Ширина текстового поля.
            'size' => 'Number',
            // Адрес графического файла для поля с изображением.
            'src' => 'URI',
            // Шаг приращения для числовых полей.
            'step' => 'Number',
            // Определяет порядок перехода между элементами с помощью клавиши Tab.
            'tabindex' => 'Number',
            // Сообщает браузеру, к какому типу относится элемент формы.
            'type' => array('text', 'number', 'password', 'checkbox', 'radio', 'submit', 'reset', 'file', 'hidden', 'image', 'button', 'email', 'url'),
            // Значение элемента.
            'value' => 'CDATA',

            // todo
            'usemap' => 'URI',
            'onfocus' => 'Script',
            'onblur' => 'Script',
            'onselect' => 'Script',
            'onchange' => 'Script',
        );

        $this->all_attrs = array_merge($this->attrs, $this->coreattrs, $this->i18n, $this->events);

        $this->type = $type !== null ? $type : $this->attrs['type'][0];
    }

    /**
     * Устанавливает значение для checkbox и radio типов,
     * которое будет сравниваться с имеющимся значением $this->value
     * и в случае, если значения равны, к элементу будет
     * добавляться аттрибут checked.
     *
     * @access public
     * @param string|int $value
     */
    public function setCheckedValue($value)
    {
        $this->checked_value = $value;

        return $this;
    }

    protected function createDocObject()
    {
        $class = __CLASS__;

        if (is_object($this->doc) && $this->doc instanceof $class) return;

        $this->doc = new DOMDocument('1.0', 'utf-8');
        $input = $this->doc->createElement('input');

        if ($this->checked_value !== null &&
            $this->checked_value == $this->value AND
            $this->type == 'checkbox' || $this->type == 'radio'
        ) {
            $this->checked = 'checked';
        }

        foreach ($this->data as $key => $value) {
            $input->setAttribute($key, (string) $value);
        }

        $this->doc->appendChild($input);
    }
}