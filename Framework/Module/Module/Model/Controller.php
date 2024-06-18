<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Module\Model;

use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Module\Module\Mapper\ModuleMapper;
use Krugozor\Framework\Validator\CharPasswordValidator;
use Krugozor\Framework\Validator\DecimalValidator;
use Krugozor\Framework\Validator\IsNotEmptyValidator;
use Krugozor\Framework\Validator\StringLengthValidator;

/**
 * @method getIdModule()
 * @method setIdModule($idModule)
 *
 * @method getName()
 * @method setName($name)
 *
 * @method getKey()
 * @method setKey($key)
 */
class Controller extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected static ?string $db_field_prefix = 'controller';

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

        'id_module' => [
            'db_element' => true,
            'db_field_name' => 'controller_id_module',
            'validators' => [
                IsNotEmptyValidator::class => [],
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'name' => [
            'db_element' => true,
            'db_field_name' => 'controller_name',
            'validators' => [
                IsNotEmptyValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH
                ],
            ]
        ],

        'key' => [
            'db_element' => true,
            'db_field_name' => 'controller_key',
            'validators' => [
                IsNotEmptyValidator::class => [],
                CharPasswordValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 150
                ],
            ]
        ],
    ];

    /**
     * Ссылка на модуль, к которому принадлежит данный контроллер.
     *
     * @var Module|null
     */
    protected ?Module $module = null;

    /**
     * Возвращает модуль, ассоциируемый с данным контроллером.
     * Не используется.
     *
     * @return null|Module
     */
    public function getModule(): ?Module
    {
        if (!$this->module && !$this->getValidateErrorsByKey('id_module')) {
            $this->module = $this->getMapperManager()
                ->getMapper(ModuleMapper::class)
                ->findModelById($this->getIdModule());
        }

        return $this->module;
    }
}