<?php

namespace Krugozor\Framework\Html;

use DOMDocument;

class ElementTextarea extends Element
{
    // Значение textarea.
    private $text;

    public function __construct()
    {
        parent::__construct();

        $this->attrs = array
        (
            'name' => 'CDATA',
            'rows' => 'Number',
            'cols' => 'Number',
            'disabled' => array('disabled'),
            'readonly' => array('readonly'),
            'tabindex' => 'Number',
            'accesskey' => 'Character',
            'onfocus' => 'Script',
            'onblur' => 'Script',
            'onselect' => 'Script',
            'onchange' => 'Script',

            // html 5
            'required' => array('required')
        );

        $this->all_attrs = array_merge($this->attrs, $this->coreattrs, $this->i18n, $this->events);
    }

    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    protected function createDocObject()
    {
        $class = __CLASS__;
        if (is_object($this->doc) && $this->doc instanceof $class) return;

        $this->doc = new DOMDocument('1.0', 'utf-8');
        $textarea = $this->doc->createElement('textarea');
        $text = $this->doc->createTextNode((string) $this->text);
        $textarea->appendChild($text);

        foreach ($this->data as $key => $value) {
            $textarea->setAttribute($key, $value);
        }

        $this->doc->appendChild($textarea);
    }
}