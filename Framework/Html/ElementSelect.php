<?php

namespace Krugozor\Framework\Html;

use DOMDocument;

class ElementSelect extends Element
{
    // Значение.
    private $checked_value;
    private $options = [];

    public function __construct()
    {
        parent::__construct();

        $this->attrs = array
        (
            'name' => 'CDATA',
            'size' => 'NUMBER',
            'multiple' => array('multiple'),
            'disabled' => array('disabled'),
            'tabindex' => 'NUMBER',
            'onfocus' => 'Script',
            'onblur' => 'Script',
            'onchange' => 'Script'
        );

        $this->all_attrs = array_merge($this->attrs, $this->coreattrs, $this->i18n, $this->events);
    }

    /**
     * Установить значение, которое будет сравниваться с имеющимися значениями
     * options и в случае совпадения будет ставиться selected соответствующему option.
     *
     * @access public
     * @param string|int $value
     */
    public function setCheckedValue($value)
    {
        $this->checked_value = $value;
        return $this;
    }

    /*
    * Добаволяет новый option или optgroup к коллекции.
    *
    * @access public
    * @param object Html_ElementOption
    * @return void
    */
    public function addOption($option)
    {
        $this->options[] = $option;
        return $this;
    }

    public function getOptions()
    {
        return $this->options;
    }

    public function getOptionById($id)
    {
        return $this->options[$id] ?? null;
    }

    protected function createDocObject()
    {
        $class = __CLASS__;

        if (is_object($this->doc) && $this->doc instanceof $class) {
            return;
        }

        $this->doc = new DOMDocument('1.0', 'utf-8');
        $select = $this->doc->createElement('select');

        foreach ($this->data as $key => $value) {
            $select->setAttribute($key, $value);
        }

        $checked_value = !is_array($this->checked_value)
            ? ($this->checked_value !== null ? array($this->checked_value) : array())
            : $this->checked_value;

        foreach ($this->options as $option) {
            if ($option instanceof ElementOptgroup) {
                // Сначала проходимся по options элемента optgroup
                foreach ($option->getOptions() as $key => $opt) {
                    if (in_array($opt->value, $checked_value)) {
                        $option->getOptionById($key)->selected = 'selected';
                    }
                }
            } else if ($option instanceof ElementOption) {
                if (in_array($option->value, $checked_value)) {
                    $option->selected = 'selected';
                }
            }

            $select->appendChild($this->doc->importNode($option->exportNode(), true));
        }

        $this->doc->appendChild($select);
    }
}