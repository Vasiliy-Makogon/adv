<?php
namespace Krugozor\Framework\Html;

use InvalidArgumentException;
use Krugozor\Cover\Simple;

abstract class Element
{
    use Simple;

    /**
     * Массив данных вида имя_аттрибута => значение.
     * Данные аттрибуты будут представлены в полученном элементе управления.
     *
     * @access protected
     * @var array
     */
    protected array $data = [];

    /**
     * Массив допустимых аттрибутов конкретного элемента управления.
     *
     * @access protected
     * @var array
     */
    protected $attrs = [];

    /**
     * Массив допустимых аттрибутов типа coreattrs
     * и их default значения согласно спецификации.
     *
     * @access protected
     * @var array
     */
    protected $coreattrs = [];

    /**
     * Массив допустимых аттрибутов типа i18n
     * и их default значения согласно спецификации.
     *
     * @access protected
     * @var array
     */
    protected $i18n = [];

    /**
     * Массив допустимых аттрибутов типа events
     * и их default значения согласно спецификации.
     *
     * @access protected
     * @var array
     */
    protected $events = [];

    /**
     * Массив всех допустимых аттрибутов и их default значений
     * согласно спецификации. Массив представляет собой объединений массивов
     * $this->attrs, $this->coreattrs, $this->i18n, $this->events.
     * Объединение происходит в конструкторе конкретного класса.
     *
     * @access protected
     * @var array
     */
    protected $all_attrs = [];

    /**
     * Объект типа DOMDocument.
     *
     * @access protected
     * @var object
     */
    protected $doc;

    /**
     * Массив настроек класса.
     *
     * @access protected
     * @var array
     */
    protected $configs = [];

    /**
     * Конструктор инициализирует все массивы
     * базовых аттрибутов {@link $coreattrs}, {@link $i18n}, {@link $events},
     * а так же устанавливает некоторые настройки класса {@link $configs}
     *
     * @see https://developer.mozilla.org/en-US/docs/Web/HTML/Global_attributes/autocapitalize
     */
    public function __construct()
    {
        $this->coreattrs = array
        (
            'id' => 'ID',
            'class' => null,
            // https://html.spec.whatwg.org/multipage/interaction.html#the-accesskey-attribute
            'accesskey' => null,
            'autofocus' => ['autofocus', null],
            'contenteditable' => ['true', 'false', ''],
            'style' => null,
            'title' => null,
            'autocapitalize' => [
                'off', 'none', 'on', 'sentences', 'words', 'characters',
            ],
        );

        $this->i18n = array
        (
            'lang' => 'CDATA',
            'dir' => array('ltr', 'rtl')
        );

        $this->events = array
        (
            'onclick' => 'Script',
            'ondblclick' => 'Script',
            'onmousedown' => 'Script',
            'onmouseup' => 'Script',
            'onmouseover' => 'Script',
            'onmousemove' => 'Script',
            'onmouseout' => 'Script',
            'onkeypress' => 'Script',
            'onkeydown' => 'Script',
            'onkeyup' => 'Script'
        );

        // Строгая проверка на присаивание тегам аттрибутов.
        $this->configs['strict_mode'] = true;
    }

    /**
     * Устанавливает аттрибут $key со значением $value для текущега элемента.
     * Расширение метода __set трейта Simple.
     *
     * @access public
     * @param string $key string имя аттрибута элемента HTML
     * @param string $value string значение аттрибута элемента HTML
     * @todo: Отрефакторить в соответствии с http://www.w3.org/TR/html5/common-microsyntaxes.html#common-microsyntaxes
     *       + переименовать в setAttribute и data переименовать в attribute
     */
    public function __set($key, $value)
    {
        // Если значение аттрибута представленно в виде :name:,
        // то это значит, что значение данного аттрибута должно быть
        // эквивалентно значению аттрибута под именем name, который _должен_
        // быть передан _перед_ ним.
        // Например, код: $object->setData(array('id' => 'myinput', 'name'=>':id:'));
        // даст результат: <input name="myinput" id="myinput" ...>
        if (preg_match('~:([a-z]+):~', (string) $value, $matches)) {
            $this->data[$key] =& $this->data[$matches[1]];

            return;
        }

        if ($this->configs['strict_mode']) {
            // неизвестный аттрибут
            if (!array_key_exists($key, $this->all_attrs)) {
                // и не data-...
                if (!preg_match('~^data-([a-z][a-z0-9\-]+)$~i', $key)) {
                    throw new InvalidArgumentException('Попытка присвоить неизвестный аттрибут ' . $key . ' тегу ' .
                        __CLASS__ . '::' . $this->type);
                }
            }

            if (array_key_exists($key, $this->all_attrs) && is_array($this->all_attrs[$key])) {
                if (!in_array($value, $this->all_attrs[$key], false)) {
                    throw new InvalidArgumentException('Попытка присвоить аттрибуту ' . $key . ' недопустимое значение');
                }
            }

            if (array_key_exists($key, $this->all_attrs)) {
                switch ($this->all_attrs[$key]) {
                    case null:
                        break;

                    case 'Script':
                    case 'ContentTypes':
                    case 'URI':
                        break;

                    case 'NMTOKENS':
                        if (!preg_match("~^[a-z0-9-_ \t]*$~i", $value)) {
                            throw new InvalidArgumentException(
                                'Попытка присвоить недопустимое значение ' . $value . ' аттрибуту ' . $key
                            );
                        }

                    case 'CDATA':
                    case 'Text':
                        break;

                    case 'Character':
                        if (empty($value) || strlen($value) !== 1 || !preg_match("~^[a-z0-9]$~i", $value)) {
                            throw new InvalidArgumentException('Попытка присвоить недопустимое значение ' . $value .
                                ' аттрибуту ' . $key . ' (ожидается один символ)');
                        }
                        break;

                    case 'ID':
                    case 'IDREF':
                        if ($value === '' || !preg_match("~^[a-z][a-z0-9-_]*$~i", $value)) {
                            throw new InvalidArgumentException(
                                'Попытка присвоить недопустимое значение ' . $value . ' аттрибуту ' . $key
                            );
                        }
                        break;

                    case 'Number':
                        if (!strlen($value) || preg_match("~^[^0-9]$~", $value)) {
                            throw new InvalidArgumentException('Попытка присвоить недопустимое значение ' . $value .
                                ' аттрибуту ' . $key . ' (ожидается цифра)');
                        }
                        break;
                }
            }
        }

        $this->data[$key] = $value;

        return $this;
    }

    /**
     * В данном методе должны быть реализованы основные действия по формированию
     * объекта $this->doc являющегося экземпляром класса DOMDocument и содержащего
     * нужный элемент управления HTML.
     *
     * @access public
     * @return string
     */
    abstract protected function createDocObject();

    /**
     * Возвращает html-код элемента управления.
     *
     * @access public
     * @return string
     */
    public function getHtml()
    {
        $this->createDocObject();
        return $this->doc->saveXML(options: LIBXML_NOXMLDECL);
    }

    /**
     * Меняет установки конфигурации.
     *
     * @access public
     * @param string $key имя ключа параметра конфигурации
     * @param mixed value новое значение
     */
    public function configSet($key, $value)
    {
        if (!isset($this->configs[$key])) {
            throw new InvalidArgumentException(
                __CLASS__ . ': Попытка изменить неизвестное свойство массива конфигурации'
            );
        }

        $this->configs[$key] = $value;
    }

    public function exportNode()
    {
        $this->createDocObject();

        return $this->doc->firstChild;
    }

    public function getDocObject()
    {
        $this->createDocObject();

        return $this->doc;
    }

    public function setAttribute($name, $value)
    {
        $this->$name = $value;

        return $this;
    }

    /**
     * Удаляет аттрибут $name.
     *
     * @param string $name имя аттрибута
     * @return $this
     */
    public function removeAttribute(string $name): static
    {
        unset($this->data[$name]);

        return $this;
    }
}