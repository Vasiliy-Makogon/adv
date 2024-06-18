<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Article\Model;

use Arispati\EmojiRemover\EmojiRemover;
use Krugozor\Framework\Helper\Format;
use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Statical\Translit;
use Krugozor\Framework\Validator\RussianTextsValidator;
use Krugozor\Framework\Statical\Strings;
use Krugozor\Framework\Type\Date\DateTime;
use Krugozor\Framework\Validator\DateCorrectValidator;
use Krugozor\Framework\Validator\DecimalValidator;
use Krugozor\Framework\Validator\IntRangeValidator;
use Krugozor\Framework\Validator\IsNotEmptyStringValidator;
use Krugozor\Framework\Validator\StringLengthValidator;

/**
 * @method getActive() Активность статьи
 * @method setActive($active)
 *
 * @method getIsHtml()
 * @method setIsHtml($active)
 *
 * @method getHeader() Заголовок статьи
 * @method setHeader($header)
 *
 * @method setUrl($url)
 *
 * @method getText() Текст статьи
 * @method setText($text)
 *
 * @method DateTime getCreateDate() create date
 * @method setCreateDate($createDate)
 *
 * @method null|DateTime getEditDate() edit date
 * @method setEditDate($editDate)
 *
 * @method getScore() fake-свойство, заполняется из SQL (релевантность по поисковому запросу)
 * @method setScore($score)
 */
class Article extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected static ?string $db_field_prefix = 'article';

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

        'active' => [
            'db_element' => true,
            'db_field_name' => 'article_active',
            'default_value' => 1,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => [
                    'min' => IntRangeValidator::ZERO,
                    'max' => IntRangeValidator::ONE
                ],
            ]
        ],

        'is_html' => [
            'db_element' => true,
            'db_field_name' => 'article_is_html',
            'default_value' => 0,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => [
                    'min' => IntRangeValidator::ZERO,
                    'max' => IntRangeValidator::ONE
                ],
            ]
        ],

        'header' => [
            'db_element' => true,
            'db_field_name' => 'article_header',
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 150
                ],
            ]
        ],

        'url' => [
            'db_element' => true,
            'db_field_name' => 'article_url',
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH
                ],
            ]
        ],

        'text' => [
            'db_element' => true,
            'db_field_name' => 'article_text',
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                RussianTextsValidator::class => [],
            ]
        ],

        'create_date' => [
            'type' => DateTime::class,
            'db_element' => true,
            'db_field_name' => 'article_create_date',
            'default_value' => 'now',
            'record_once' => true,
            'validators' => [
                DateCorrectValidator::class => [
                    'format' => DateTime::FORMAT_DATETIME
                ],
            ]
        ],

        'edit_date' => [
            'type' => DateTime::class,
            'db_element' => true,
            'db_field_name' => 'article_edit_date',
            'validators' => [
                DateCorrectValidator::class => [
                    'format' => DateTime::FORMAT_DATETIME
                ],
            ]
        ],

        'score' => [
            'db_element' => false,
            'default_value' => 0
        ],
    ];

    /**
     * @param string $text
     * @return string
     */
    public function _setText(string $text): string
    {
        return EmojiRemover::filter($text);
    }

    /**
     * explicit-метод.
     *
     * @param string $header
     * @return string
     */
    public function _setHeader(string $header): string
    {
        $header = EmojiRemover::filter($header);
        $header = Format::spaceAfterPunctuation($header);

        return Strings::mb_ucfirst($header);
    }

    /**
     * explicit-метод.
     *
     * @param string|null $url
     * @return string|null
     */
    protected function _setUrl(?string $url): ?string
    {
        return is_null($url) ? $url : Translit::UrlTranslit($url);
    }

    /**
     * @param bool $httpPath
     * @return string|null
     */
    public function getUrl(bool $httpPath = false): ?string
    {
        return $httpPath
            ? sprintf('/articles/%s_%s.html', $this->getUrl(), $this->getId())
            : ($this->data['url'] ?? null);
    }

    /**
     * Дата последней модификации документа для протокола HTTP.
     *
     * @return DateTime
     */
    public function getLastModifiedDate(): DateTime
    {
        if ($this->getEditDate() !== null && $this->getEditDate() > $this->getCreateDate()) {
            return $this->getEditDate();
        }

        return $this->getCreateDate();
    }
}