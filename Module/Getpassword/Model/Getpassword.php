<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Getpassword\Model;

use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Type\Date\DateTime;
use Krugozor\Framework\Validator\CharPasswordValidator;
use Krugozor\Framework\Validator\DateCorrectValidator;
use Krugozor\Framework\Validator\DecimalValidator;
use Krugozor\Framework\Validator\StringLengthValidator;

/**
 * @method getUserId()
 * @method setUserId($userId)
 *
 * @method getHash()
 * @method setHash($hash)
 *
 * @method DateTime getDate()
 * @method setDate($date)
 */
class Getpassword extends AbstractModel
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

        'user_id' => [
            'db_element' => true,
            'record_once' => true,
            'default_value' => 0,
            'validators' => [
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'hash' => [
            'db_element' => true,
            'record_once' => true,
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::MD5_MAX_LENGTH,
                    'stop' => StringLengthValidator::MD5_MAX_LENGTH
                ],
                CharPasswordValidator::class => [],
            ]
        ],

        'date' => [
            'type' => DateTime::class,
            'db_element' => true,
            'record_once' => true,
            'default_value' => 'now',
            'validators' => [
                DateCorrectValidator::class => ['format' => DateTime::FORMAT_DATETIME],
            ]
        ],
    ];
}