<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Module\Validator;

use Krugozor\Framework\Validator\AbstractValidator;

class ModuleKeyExistsValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'MODULE_KEY_EXISTS';

    /**
     * @return bool
     */
    public function validate(): bool
    {
        $params = array (
            'where' => array('module_key = "?s"' => array($this->value->getKey())),
            'what' => 'id',
        );

        if ($this->value->getId() !== null) {
            $params['where']['AND id <> ?i'] = array($this->value->getId());
        }

        if ($this->mapper->findModelByParams($params)->getId()) {
            $this->error_params = array('module_key' => $this->value->getKey());

            return false;
        }

        return true;
    }
}