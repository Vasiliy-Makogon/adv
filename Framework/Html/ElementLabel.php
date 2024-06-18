<?php

namespace Krugozor\Framework\Html;

use DOMDocument;

class ElementLabel extends Element
{
    // Значение label.
    private $text;

    public function __construct()
    {
        parent::__construct();

        $this->attrs = array
        (
            'for' => 'IDREF',
            'accesskey' => 'Character',
            'onfocus' => 'Script',
            'onblur' => 'Script'
        );

        $this->all_attrs = array_merge($this->attrs, $this->coreattrs, $this->i18n, $this->events);
    }

    public function setText($value)
    {
        $this->text = $value;
        return $this;
    }

    protected function createDocObject()
    {
        $class = __CLASS__;
        if (is_object($this->doc) && $this->doc instanceof $class) return;

        $this->doc = new DOMDocument('1.0', 'utf-8');
        $label = $this->doc->createElement('label');
        $text = $this->doc->createTextNode($this->text);
        $label->appendChild($text);

        foreach ($this->data as $key => $value) {
            $label->setAttribute($key, $value);
        }

        $this->doc->appendChild($label);
    }
}