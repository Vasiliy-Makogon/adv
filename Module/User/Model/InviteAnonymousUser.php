<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Model;

use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Type\Date\DateTime;
use Krugozor\Framework\Validator\CharPasswordValidator;
use Krugozor\Framework\Validator\DateCorrectValidator;
use Krugozor\Framework\Validator\DecimalValidator;
use Krugozor\Framework\Validator\StringLengthValidator;

/**
 * @method getUniqueCookieId()
 * @method setUniqueCookieId($uniqueUserCookieId)
 *
 * @method getSendDate()
 * @method setSendDate($sendDate)
 */
class InviteAnonymousUser extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected static array $model_attributes = [
        'id' => [
            'db_element' => false,
            'default_value' => 0,
            'validators' => [
                DecimalValidator::class => ['signed' => false]
            ],
        ],

        'unique_cookie_id' => [
            'db_element' => true,
            'db_field_name' => 'unique_cookie_id',
            'record_once' => true,
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::MD5_MAX_LENGTH,
                    'stop' => StringLengthValidator::MD5_MAX_LENGTH
                ],
                CharPasswordValidator::class => [],
            ]
        ],

        'send_date' => [
            'db_element' => true,
            'db_field_name' => 'send_date',
            'record_once' => true,
            'type' => DateTime::class,
            'validators' => [
                DateCorrectValidator::class => [
                    'format' => DateTime::FORMAT_DATETIME
                ]
            ],
        ],
    ];
}