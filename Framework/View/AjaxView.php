<?php

declare(strict_types=1);

namespace Krugozor\Framework\View;

use Krugozor\Framework\View;

class AjaxView extends View
{
    /**
     * Возвращает JSON-представление $data или $this->data.
     *
     * @param null|array $data
     * @return string
     */
    protected function createJson(?array $data = null): string
    {
        return json_encode($data !== null ? $data : $this->data->getDataAsArray(), JSON_FORCE_OBJECT);
    }
}