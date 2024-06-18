<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\MailQueue\Model;

use InvalidArgumentException;
use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Type\Date\DateTime;
use Krugozor\Framework\Validator\DateCorrectValidator;
use Krugozor\Framework\Validator\DecimalValidator;
use Krugozor\Framework\Validator\EmailValidator;
use Krugozor\Framework\Validator\IntRangeValidator;
use Krugozor\Framework\Validator\IsNotEmptyStringValidator;
use Krugozor\Framework\Validator\StringLengthValidator;

/**
 * @method self setSendDate($sendDate)
 * @method DateTime getSendDate()
 *
 * @method self setTemplate($template)
 * @method getTemplate()
 *
 * @method self setToEmail($toEmail)
 * @method getToEmail()
 *
 * @method self setFromEmail($fromEmail)
 * @method getFromEmail()
 *
 * @method self setCcEmail($ccEmail)
 * @method getCcEmail()
 *
 * @method self setReplyEmail($replyEmail)
 * @method getReplyEmail()
 *
 * @method self setHeader($header)
 * @method getHeader()
 *
 * @method self setMailData($mailData)
 * @method getMailData()
 *
 * @method self setSended($sended)
 * @method getSended()
 */
class MailQueue extends AbstractModel
{
    /** @var int Письмо в очереди */
    public const STATUS_WAIT = 0;

    /** @var int Успешная отправка письма */
    public const STATUS_OK = 1;

    /** @var int Письмо не отправлено и исключено из очереди */
    public const STATUS_FAIL = -1;

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

        'send_date' => [
            'type' => DateTime::class,
            'db_element' => true,
            'default_value' => null,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DateCorrectValidator::class => [
                    'format' => DateTime::FORMAT_DATETIME
                ],
            ]
        ],

        'template' => [
            'db_element' => true,
            'default_value' => null,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH,
                ],
            ]
        ],

        'to_email' => [
            'db_element' => true,
            'default_value' => null,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 100,
                ],
                EmailValidator::class => [],
            ]
        ],

        'from_email' => [
            'db_element' => true,
            'default_value' => null,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 100,
                ],
                EmailValidator::class => [],
            ]
        ],

        'cc_email' => [
            'db_element' => true,
            'default_value' => null,
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 100,
                ],
                EmailValidator::class => [],
            ]
        ],

        'reply_email' => [
            'db_element' => true,
            'default_value' => null,
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 100,
                ],
                EmailValidator::class => [],
            ]
        ],

        'header' => [
            'db_element' => true,
            'default_value' => null,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH,
                ],
            ]
        ],

        'mail_data' => [
            'db_element' => true,
            'default_value' => null,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                // @todo: is array validator added
            ]
        ],

        'sended' => [
            'db_element' => true,
            'default_value' => self::STATUS_WAIT,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => true],
                IntRangeValidator::class => [
                    'min' => self::STATUS_FAIL, 'max' => self::STATUS_OK
                ],
            ]
        ],
    ];

    /**
     * @param array|string $data
     * @return string|array
     */
    public function _setMailData(array|string $data): string|array
    {
        if (!is_string($data) && !is_array($data)) {
            throw new InvalidArgumentException(sprintf(
                '%s: Parameter $data must be a string or a array.', __METHOD__
            ));
        }

        // Решение по сериализации + кодирование взято здесь (что бы не было ошибки "Error at offset"):
        // https://stackoverflow.com/questions/10152904/unserialize-function-unserialize-error-at-offset
        return is_array($data) ? base64_encode(serialize($data)) : unserialize(base64_decode($data));
    }
}