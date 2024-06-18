<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Group\Model;

use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Validator\DecimalValidator;
use Krugozor\Framework\Validator\IntRangeValidator;
use Krugozor\Framework\Validator\IsNotEmptyValidator;

/**
 * @method getIdGroup()
 * @method setIdGroup($idGroup)
 *
 * @method getIdController()
 * @method setIdController($idController)
 *
 * @method getAccess()
 * @method setAccess($access)
 */
class Access extends AbstractModel
{
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

        'id_group' => [
            'db_element' => true,
            'db_field_name' => 'id_group',
            'validators' => [
                IsNotEmptyValidator::class => [],
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'id_controller' => [
            'db_element' => true,
            'db_field_name' => 'id_controller',
            'validators' => [
                IsNotEmptyValidator::class => [],
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'access' => [
            'db_element' => true,
            'db_field_name' => 'access',
            'validators' => [
                IsNotEmptyValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => [
                    'min' => IntRangeValidator::ZERO,
                    'max' => IntRangeValidator::ONE
                ],
            ]
        ],
    ];
}