<?php

declare(strict_types=1);

namespace Krugozor\Framework\Helper;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Html\ElementInput;
use Krugozor\Framework\Html\ElementLabel;
use Krugozor\Framework\Html\ElementOptgroup;
use Krugozor\Framework\Html\ElementOption;
use Krugozor\Framework\Html\ElementSelect;
use Krugozor\Framework\Html\ElementTextarea;
use Krugozor\Framework\Statical\Strings;
use RuntimeException;

/**
 * Класс-хэлпер для генерации элементов форм
 * и полей, выводящих ошибки валидации.
 */
class Form
{
    /**
     * @var Form|null
     */
    private static ?Form $instance = null;

    /**
     * Загруженный шаблон вывода ошибки.
     *
     * @var string
     */
    private string $error_template;

    /**
     * @return static
     */
    public static function getInstance(): static
    {
        if (!self::$instance) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * Устанавливает шаблон для HTML-кода вывода ошибок.
     *
     * @param string $template путь к шаблону
     */
    public function setFieldErrorTemplate(string $template)
    {
        if (!file_exists($template)) {
            throw new RuntimeException(sprintf(
                'Не найден шаблон вывода ошибок указанный по адресу: `%s`', $template
            ));
        }

        $this->error_template = file_get_contents($template);
    }

    /**
     * Возвращает объект
     *
     * @param string $name имя элемента
     * @param int|string|float $value значение
     * @param int|string|float|null $checked_value значение сравнения - если $value и $checked_value равны, то то
     *     checkbox is checked
     * @param array $params дополнительные параметры
     * @return ElementInput
     */
    public static function inputCheckbox(
        string $name,
        int|string|float $value,
        int|string|float|null $checked_value = null,
        array $params = array()
    ): ElementInput
    {
        $object = new ElementInput('checkbox');
        $object->name = $name;
        $object->value = $value;
        $object->setCheckedValue($checked_value);
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementInput типа radio.
     *
     * @param string $name имя элемента
     * @param int|string|float $value значение
     * @param int|string|float|null $checked_value значение сравнения - если $value и $checked_value равны, то radio is
     *     checked
     * @param array $params дополнительные параметры
     * @return ElementInput
     */
    public static function inputRadio(
        string $name,
        int|string|float $value,
        int|string|float|null $checked_value = null,
        array $params = array()
    ): ElementInput
    {
        $object = new ElementInput('radio');
        $object->name = $name;
        $object->value = $value;
        $object->setCheckedValue($checked_value);
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает два html-элемента: hidden поле и checkbox.
     * Обобщённый метод получения двух взаимосвязанных элементов управления.
     *
     * @param string $name имя элемента hidden и checkbox
     * @param int|string|float $value значение checkbox
     * @param int|string|float $hidden_value значение hidden
     * @param int|string|float|null $checked_value значение сравнения - если $value и $checked_value равны, то checkbox
     *     is checked.
     * @param array $params дополнительные параметры
     * @return string
     */
    public static function inputFullCheckbox(
        string $name,
        int|string|float $value,
        int|string|float $hidden_value,
        int|string|float|null $checked_value = null,
        array $params = array()
    ): string
    {
        $checkbox = self::inputCheckbox($name, $value, $checked_value, $params);
        $hidden = self::inputHidden($name, $hidden_value);

        return $hidden->getHtml() . $checkbox->gethtml();
    }

    /**
     * Возвращает объект ElementInput типа text.
     *
     * @param string $name имя элемента
     * @param int|string|float|null $value значение
     * @param array $params дополнительные параметры
     * @return ElementInput
     */
    public static function inputText(
        string $name,
        int|string|float|null $value = null,
        array $params = array()
    ): ElementInput
    {
        $object = new ElementInput('text');
        $object->name = $name;
        $object->value = $value;
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementInput типа number.
     *
     * @param string $name имя элемента
     * @param int|string|float|null $value значение
     * @param array $params дополнительные параметры
     * @return ElementInput
     */
    public static function inputNumber(
        string $name,
        int|string|float|null $value = null,
        array $params = array()
    ): ElementInput
    {
        $object = new ElementInput('number');
        $object->name = $name;
        $object->value = $value;
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementInput типа email.
     *
     * @param string $name имя элемента
     * @param string|null $value значение
     * @param array $params дополнительные параметры
     * @return ElementInput
     */
    public static function inputEmail(
        string $name,
        ?string $value = null,
        array $params = array()
    ): ElementInput
    {
        $object = new ElementInput('email');
        $object->name = $name;
        $object->value = $value;
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementInput типа url.
     *
     * @param string $name имя элемента
     * @param string|null $value значение
     * @param array $params дополнительные параметры
     * @return ElementInput
     */
    public static function inputUrl(
        string $name,
        ?string $value = null,
        array $params = array()
    ): ElementInput
    {
        $object = new ElementInput('url');
        $object->name = $name;
        $object->value = $value;
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementTextarea.
     *
     * @param string $name имя элемента
     * @param float|int|string|null $value = null,
     * @param array $params дополнительные параметры
     * @return ElementTextarea
     */
    public static function inputTextarea(
        string $name,
        float|int|string|null $value,
        $params = array()
    ): ElementTextarea
    {
        $object = new ElementTextarea();
        $object->name = $name;
        $object->setText($value);
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementInput типа password.
     *
     * @param string $name имя элемента
     * @param string|null $value значение
     * @param array $params дополнительные параметры
     * @return ElementInput
     */
    public static function inputPassword(
        string $name,
        ?string $value,
        array $params = array()
    ): ElementInput
    {
        $object = new ElementInput('password');
        $object->name = $name;
        $object->value = $value;
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementInput типа hidden.
     *
     * @param string $name имя элемента
     * @param float|int|string|null $value значение
     * @param array $params дополнительные параметры
     * @return ElementInput
     */
    public static function inputHidden(
        string $name,
        float|int|string|null $value,
        array $params = array()
    ): ElementInput
    {
        $object = new ElementInput('hidden');
        $object->name = $name;
        $object->value = $value;
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementInput типа submit.
     *
     * @param string $name имя элемента
     * @param string $value значение
     * @param array $params дополнительные параметры
     * @return ElementInput
     */
    public static function inputSubmit(
        string $name,
        string $value,
        array $params = array()
    ): ElementInput
    {
        $object = new ElementInput('submit');
        $object->name = $name;
        $object->value = $value;
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementInput типа button.
     *
     * @param string $name имя элемента
     * @param string $value значение
     * @param array $params дополнительные параметры
     * @return ElementInput
     */
    public static function inputButton(
        string $name,
        string $value,
        array $params = array()
    ): ElementInput
    {
        $object = new ElementInput('button');
        $object->name = $name;
        $object->value = $value;
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementInput типа file.
     *
     * @param string $name имя элемента
     * @param array $params дополнительные параметры
     * @return ElementInput
     */
    public static function inputFile(
        string $name,
        array $params = array()
    ): ElementInput
    {
        $object = new ElementInput('file');
        $object->name = $name;
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementLabel.
     *
     * @param string $text текст метки
     * @param string $for ссылка на ID
     * @param array $params дополнительные параметры
     * @return ElementLabel
     */
    public static function label(
        string $text,
        string $for,
        array $params = array()
    ): ElementLabel
    {
        $object = new ElementLabel();
        $object->for = $for;
        $object->setText($text);
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementOption.
     *
     * @param string $value значение value тега option
     * @param string $text текстовой узел-значение тега option
     * @param array $params дополнительные параметры
     * @return ElementOption
     */
    public static function inputOption(
        string $value,
        string $text,
        $params = array()
    ): ElementOption
    {
        $object = new ElementOption();
        $object->value = $value;
        $object->setText($text);
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementOptgroup.
     *
     * @param string $label значение свойства label
     * @param array $params дополнительные параметры
     * @return ElementOptgroup
     */
    public static function inputOptgroup(
        string $label,
        $params = array()
    ): ElementOptgroup
    {
        $object = new ElementOptgroup();
        $object->label = $label;
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementSelect.
     *
     * @param string $name имя элемента
     * @param string|int|float|null $checked_value
     * @param array $params дополнительные параметры
     * @return ElementSelect
     */
    public static function inputSelect(
        string $name,
        string|int|float|null $checked_value = null,
        array $params = array()
    ): ElementSelect
    {
        $object = new ElementSelect();
        $object->name = $name;
        $object->setCheckedValue($checked_value);
        $object->setData($params);

        return $object;
    }

    /**
     * Возвращает объект ElementSelect наполненный options
     * значения которого идут в цифровом диапазоне $int_start - $int_stop.
     *
     * @param string $name имя элемента
     * @param string|int $int_start начальное значение
     * @param string|int $int_stop конечное значение
     * @param string|int|null $checked_value значение сравнения - если $value и $checked_value равны, то checkbox is
     *     checked.
     * @param array $params дополнительные параметры
     * @return ElementSelect
     */
    public static function inputSelectIntegerValues(
        string $name,
        string|int $int_start,
        string|int $int_stop,
        string|int|null $checked_value = null,
        array $params = array()
    ): ElementSelect
    {
        $int_start = (int) $int_start;
        $int_stop = (int) $int_stop;

        $object = new ElementSelect();
        $object->name = $name;
        $object->setCheckedValue($checked_value);
        $object->setData($params);

        $option = new ElementOption();
        $option->value = 0;
        $option->setText('Выберите');
        $object->addOption($option);

        if ($int_start < $int_stop) {
            for (; $int_start <= $int_stop; $int_start++) {
                $option = new ElementOption();
                $option->value = $int_start;
                $option->setText($int_start);

                $object->addOption($option);
            }
        } else {
            for (; $int_start >= $int_stop; $int_start--) {
                $option = new ElementOption();
                $option->value = $int_start;
                $option->setText($int_start);

                $object->addOption($option);
            }
        }

        return $object;
    }

    /**
     * Возвращает объект ElementSelect наполненный options
     * значения которого идут в цифровом диапазоне, определяемом количеством лет со $start и до $stop.
     * Если цифровые значения явно не указаны, то возвращается select с верхней точкой
     * лет равной now-15 и крайней точкой временного отсчёта равной now-80.
     *
     * @param string $name имя элемента
     * @param int|string|null $checked_value значение сравнения - если $value и $checked_value равны,
     * то checkbox is checked.
     * @param array $params дополнительные параметры
     * @param int $start начальное значение
     * @param int $end конечное значение
     * @return ElementSelect
     */
    public static function inputSelectYears(
        string $name,
        int|string|null $checked_value = null,
        array $params = [],
        int $start = 15,
        int $end = 80
    ): ElementSelect {
        $start = date('Y', time() - 60 * 60 * 24 * 360 * $start);
        $end = date('Y', time() - 60 * 60 * 24 * 360 * $end);

        $object = new ElementSelect();
        $object->name = $name;
        $object->setCheckedValue($checked_value);
        $object->setData($params);

        $option = new ElementOption();
        $option->value = 0;
        $option->setText('Выберите');
        $object->addOption($option);

        while ($start >= $end) {
            $option = new ElementOption();
            $option->value = $start;
            $option->setText($start);

            $object->addOption($option);
            $start--;
        }

        return $object;
    }

    /**
     * Принимает CoverArray содержащий одну или более ошибок конкретного поля,
     * возникших в результате валидации полей форм и возвращает
     * строку ошибки в виде HTML-кода.
     * HTML-код берётся из шаблона $this->error_template.
     *
     * @param CoverArray|null $data
     * @param string $default_class дополнительный CSS класс
     * @return string
     */
    public function getFieldError(
        ?CoverArray $data = null,
        string $default_class = 'arrow_top'
    ): string {
        if ($data === null || !$data->count()) {
            return '';
        }

        return Strings::createMessageFromParams(
            $this->error_template, [
            'error_message' => $data->count() > 1
                ? '<ul><li>' . implode('</li><li>', $data->getDataAsArray()) . '</li></ul>'
                : $data->getFirst(),
            'default_class' => $default_class
        ], false);
    }

    private function __construct()
    {}
}