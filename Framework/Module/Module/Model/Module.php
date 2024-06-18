<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Module\Model;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Module\Module\Mapper\ControllerMapper;
use Krugozor\Framework\Validator\CharPasswordValidator;
use Krugozor\Framework\Validator\DecimalValidator;
use Krugozor\Framework\Validator\IsNotEmptyValidator;
use Krugozor\Framework\Validator\StringLengthValidator;

/**
 * @method getName()
 * @method setName($name)
 *
 * @method getKey()
 * @method setKey($key)
 */
class Module extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected static ?string $db_field_prefix = 'module';

    /**
     * @inheritdoc
     */
    protected static array $model_attributes = [
        'id' => [
            'db_element' => false,
            'default_value' => 0,
            'validators' => [
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'name' => [
            'db_element' => true,
            'db_field_name' => 'module_name',
            'validators' => [
                IsNotEmptyValidator::class => [],
                StringLengthValidator::class => ['start' => 0, 'stop' => 50],
            ]
        ],

        'key' => [
            'db_element' => true,
            'db_field_name' => 'module_key',
            'validators' => [
                IsNotEmptyValidator::class => [],
                CharPasswordValidator::class => [],
                StringLengthValidator::class => ['start' => 0, 'stop' => 30],
            ]
        ],
    ];

    /**
     * Коллекция контроллеров модуля.
     *
     * @var CoverArray|null
     */
    protected ?CoverArray $controllersList = null;

    /**
     * Возвращает коллекцию, содержащую все контроллеры, принадлежащие данному модулю.
     * Lazy Load.
     *
     * @return CoverArray
     */
    public function getControllersList(): CoverArray
    {
        if (!$this->controllersList) {
            $this->controllersList = $this->getMapperManager()
                ->getMapper(ControllerMapper::class)
                ->findControllerModelListByModule($this);
        }

        return $this->controllersList;
    }
}