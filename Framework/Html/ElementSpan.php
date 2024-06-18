<?php

namespace Krugozor\Framework\Html;

use DOMDocument;

class ElementSpan extends Element
{
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

    protected function createDocObject()
    {
        if (is_object($this->doc) && $this->doc instanceof DOMDocument) {
            return $this;
        }

        $this->doc = new DOMDocument('1.0', 'utf-8');
        $label = $this->doc->createElement('span');

        foreach ($this->data as $key => $value) {
            $label->setAttribute($key, $value);
        }

        $this->doc->appendChild($label);
    }
}