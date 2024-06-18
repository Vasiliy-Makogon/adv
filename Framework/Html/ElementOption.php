<?php
namespace Krugozor\Framework\Html;

use DOMDocument;

class ElementOption extends Element
{
    // Значение option.
    private $text;

    public function __construct()
    {
        parent::__construct();

        $this->attrs = array
        (
            'selected' => array('selected'),
            'disabled' => array('disabled'),
            'label' => 'Text',
            'value' => 'CDATA'
        );

        $this->all_attrs = array_merge($this->attrs, $this->coreattrs, $this->i18n, $this->events);
    }

    public function setText($text)
    {
        $this->text = $text;

        return $this;
    }

    public function getText()
    {
        return $this->text;
    }

    protected function createDocObject()
    {
        $class = __CLASS__;

        if (is_object($this->doc) && $this->doc instanceof $class) return;

        $this->doc = new DOMDocument('1.0', 'utf-8');
        $option = $this->doc->createElement('option');
        $text = $this->doc->createTextNode($this->text);
        $option->appendChild($text);

        foreach ($this->data as $key => $value) {
            $option->setAttribute($key, $value);
        }

        $this->doc->appendChild($option);
    }

    public function exportNode()
    {
        $this->createDocObject();

        return $this->doc->firstChild;
    }
}