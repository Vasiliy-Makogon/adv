<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Module\Validator;

use Krugozor\Framework\Validator\AbstractValidator;

class ModuleNameExistsValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'MODULE_NAME_EXISTS';

    /**
     * @return bool
     */
    public function validate(): bool
    {
        $params = array(
            'where' => array('module_name = "?s"' => array($this->value->getName())),
            'what' => 'id',
        );

        if ($this->value->getId() !== null) {
            $params['where']['AND id <> ?i'] = array($this->value->getId());
        }

        if ($this->mapper->findModelByParams($params)->getId()) {
            $this->error_params = array('module_name' => $this->value->getName());

            return false;
        }

        return true;
    }
}