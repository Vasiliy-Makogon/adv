<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Advert\Model;

use Arispati\EmojiRemover\EmojiRemover;
use DateInterval;
use Exception;
use Krugozor\Cover\CoverArray;
use Krugozor\Database\MySqlException;
use Krugozor\Framework\Helper\Format;
use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Module\Advert\Mapper\ThumbnailMapper;
use Krugozor\Framework\Module\Advert\Type\AdvertType;
use Krugozor\Framework\Module\Advert\Type\PriceType;
use Krugozor\Framework\Validator\RussianTextsValidator;
use Krugozor\Framework\Module\Advert\Validator\StopWordsValidator;
use Krugozor\Framework\Module\Prodamus\Service\Prodamus;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Statical\Strings;
use Krugozor\Framework\Type\Date\DateTime;
use Krugozor\Framework\Type\Email;
use Krugozor\Framework\Type\Url;
use Krugozor\Framework\Validator\CharPasswordValidator;
use Krugozor\Framework\Validator\DateCorrectValidator;
use Krugozor\Framework\Validator\DecimalValidator;
use Krugozor\Framework\Validator\EmailValidator;
use Krugozor\Framework\Validator\HasBadEmailValidator;
use Krugozor\Framework\Validator\HasBadUrlValidator;
use Krugozor\Framework\Validator\IntRangeValidator;
use Krugozor\Framework\Validator\IsNotEmptyStringValidator;
use Krugozor\Framework\Validator\IsNotEmptyValidator;
use Krugozor\Framework\Validator\Md5FileNameValidator;
use Krugozor\Framework\Validator\PhoneValidator;
use Krugozor\Framework\Validator\ProfanityWordsValidator;
use Krugozor\Framework\Validator\SkypeValidator;
use Krugozor\Framework\Validator\StringLengthValidator;
use Krugozor\Framework\Validator\TelegramValidator;
use Krugozor\Framework\Validator\UrlValidator;
use Krugozor\Framework\Validator\VarEnumValidator;

/**
 * @method getIdUser() Идентификатор пользователя
 * @method setIdUser($idUser)
 *
 * @method getUniqueUserCookieId() Уникальный cookie-идентификатор объявления
 * @method setUniqueUserCookieId($uniqueUserCookieId)
 *
 * @method getActive() Активность объявления
 * @method setActive($active)
 *
 * @method AdvertType getType() Тип объявления
 * @method setType($type)
 *
 * @method getCategory() ID категории
 * @method setCategory($category)
 *
 * @method getHeader() Заголовок объявления
 * @method setHeader($header)
 *
 * @method getText() Текст объявления
 * @method setText($text)
 *
 * @method getPrice() Цена
 * @method setPrice($price)
 *
 * @method getBalance() Остаток товара на складе
 * @method setBalance($balance)
 *
 * @method getFree() Бесплатно отдам
 * @method setFree($free)
 *
 * @method PriceType getPriceType() Валюта
 * @method setPriceType($priceType)
 *
 * @method Email getEmail() Email
 * @method setEmail($email)
 *
 * @method getPhone() Телефон
 * @method setPhone($phone)
 *
 * @method getTelegram() Telegram
 * @method setTelegram($telegram)
 *
 * @method Url getUrl() Веб-сайт
 * @method setUrl($url)
 *
 * @method getSkype() Skype
 * @method setSkype($skype)
 *
 * @method getUserName() Имя пользователя
 * @method setUserName($userName)
 *
 * @method getMainEmail() Использовать email из контактов
 * @method setMainEmail($mainEmail)
 *
 * @method getMainPhone() Использовать телефон из контактов
 * @method setMainPhone($mainPhone)
 *
 * @method getMainTelegram() Использовать telegram из контактов
 * @method setMainTelegram($mainTelegram)
 *
 * @method getMainUrl() Использовать url из контактов
 * @method setMainUrl($mainUrl)
 *
 * @method getMainSkype() Использовать skype из контактов
 * @method setMainSkype($mainSkype)
 *
 * @method getMainUserName() Использовать имя из контактов
 * @method setMainUserName($mainUserName)
 *
 * @method getUseContact() Показывать в объявлении мои контактные данные
 * @method setUseContact($useContact)
 *
 * @method getPlaceCountry() ID страны
 * @method setPlaceCountry($placeCountry)
 *
 * @method getPlaceRegion() ID региона
 * @method setPlaceRegion($placeRegion)
 *
 * @method getPlaceCity() ID города
 * @method setPlaceCity($placeCity)
 *
 * @method DateTime getCreateDate() create date
 * @method setCreateDate($createDate)
 *
 * @method null|DateTime getEditDate() edit date
 * @method setEditDate($editDate)
 *
 * @method null|DateTime getVipDate() vip date
 * @method setVipDate($vipDate)
 *
 * @method null|DateTime getSpecialDate() special date
 * @method setSpecialDate($specialDate)
 *
 * @method getViewCount() кол-во просмотров объявления
 * @method setViewCount($viewCount)
 *
 * @method getWasModerated() было ли одобрено модератором
 * @method setWasModerated($wasModerated)
 *
 * @method getThumbnailCount() Количество изображений объявления. Заполняется с помощью триггера на таблице
 *     `thumbnail_advert`.
 * @method setThumbnailCount($thumbnailCount)
 *
 * @method getHash() Уникальный hash объявления
 * @method setHash($hash)
 *
 * @method getThumbnailFileName() Имя главного изображения. Заполняется с помощью триггера на таблице
 *     `thumbnail_advert`.
 * @method setThumbnailFileName($thumbnailFileName)
 *
 * @method getPayment() Оплачено объявление или нет. Сейчас не используется в принципе, при размещении всегда
 *     "оплачено", т.к. нет платных категорий
 * @method setPayment($payment)
 *
 * @method getScore() fake-свойство, заполняется из SQL (релевантность по поисковому запросу)
 * @method setScore($score)
 */
class Advert extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected static ?string $db_field_prefix = 'advert';

    /**
     * Кол-во дней, после которых неоплаченные объявления (свойство payment=0) из платных категорий,
     * отмеченных как paid_tolerance=1, устанавливаются в статус "оплачено" (с помощью cron-скрипта).
     * Это сделано для того, что бы не терять контент, который жадные люди не хотят оплачивать.
     *
     * @var int
     */
    const PAID_TOLERANCE_DAYS = 15;

    /**
     * Минимальное кол-во объявлений с Special-статусом, которые должны присутствовать в системе.
     *
     * @var int
     */
    const MIN_ADVERTS_WITH_SPECIAL_STATUSES = 30;

    /**
     * Паттерн для создания md5-хэшей объявлений.
     *
     * @var string
     */
    protected static string $text_hash_pattern = '#[а-яa-z0-9]{3,}#i';

    /**
     * Минимальное кол-во объявлений для активации профиля пользователя.
     *
     * @var int
     */
    public const MIN_ADVERTS_COUNT_FOR_SHOW_PROFILE = 5;

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

        'id_user' => [
            'db_element' => true,
            'db_field_name' => 'advert_id_user',
            'default_value' => -1,
            'validators' => [
                IsNotEmptyValidator::class => [],
                DecimalValidator::class => ['signed' => true],
                IntRangeValidator::class => [
                    'min' => User::GUEST_USER_ID,
                    'max' => IntRangeValidator::INT_MAX
                ]
            ]
        ],

        'unique_user_cookie_id' => [
            'db_element' => true,
            'db_field_name' => 'advert_unique_user_cookie_id',
            'record_once' => true,
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::MD5_MAX_LENGTH,
                    'stop' => StringLengthValidator::MD5_MAX_LENGTH
                ],
                CharPasswordValidator::class => [],
            ]
        ],

        'active' => [
            'db_element' => true,
            'db_field_name' => 'advert_active',
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

        'type' => [
            'type' => AdvertType::class,
            'db_element' => true,
            'db_field_name' => 'advert_type',
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                VarEnumValidator::class => [
                    'enum' => [
                        AdvertType::TYPE_SALE,
                        AdvertType::TYPE_BUY,
                        AdvertType::TYPE_RENT_SALE,
                        AdvertType::TYPE_RENT_BUY,

                        AdvertType::TYPE_JOB_FULL,
                        AdvertType::TYPE_JOB_PART,
                        AdvertType::TYPE_JOB_PROBATION,
                        AdvertType::TYPE_JOB_PROJECT,
                        AdvertType::TYPE_JOB_VOLUNTEER,
                    ]
                ],
            ]
        ],

        'category' => [
            'db_element' => true,
            'db_field_name' => 'advert_category',
            'validators' => [
                IsNotEmptyValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => [
                    'min' => IntRangeValidator::ZERO,
                    'max' => IntRangeValidator::SMALLINT_MAX_UNSIGNED
                ],
            ]
        ],

        'header' => [
            'db_element' => true,
            'db_field_name' => 'advert_header',
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                HasBadUrlValidator::class => ['break' => false],
                HasBadEmailValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 150
                ],
                StopWordsValidator::class => [],
                ProfanityWordsValidator::class => [],
            ]
        ],

        'text' => [
            'db_element' => true,
            'db_field_name' => 'advert_text',
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                HasBadUrlValidator::class => ['break' => false],
                HasBadEmailValidator::class => [],
                StopWordsValidator::class => [],
                ProfanityWordsValidator::class => [],
                RussianTextsValidator::class => [],
                StringLengthValidator::class => [
                    'start' => 0,
                    'stop' => 5000
                ],
            ]
        ],

        'price' => [
            'db_element' => true,
            'db_field_name' => 'advert_price',
            'validators' => [
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => [
                    'min' => IntRangeValidator::ZERO,
                    'max' => IntRangeValidator::INT_MAX_UNSIGNED
                ],
            ]
        ],

        'balance' => [
            'db_element' => true,
            'db_field_name' => 'advert_balance',
            'validators' => [
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => [
                    'min' => IntRangeValidator::ZERO,
                    'max' => IntRangeValidator::SMALLINT_MAX_UNSIGNED
                ]
            ]
        ],

        'free' => [
            'db_element' => true,
            'db_field_name' => 'advert_free',
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

        'price_type' => [
            'type' => PriceType::class,
            'db_element' => true,
            'db_field_name' => 'advert_price_type',
            'default_value' => PriceType::TYPE_RUB,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                VarEnumValidator::class => [
                    'enum' => [PriceType::TYPE_RUB, PriceType::TYPE_USD, PriceType::TYPE_EUR]
                ],
            ]
        ],

        'email' => [
            'type' => Email::class,
            'db_element' => true,
            'db_field_name' => 'advert_email',
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH
                ],
                EmailValidator::class => [],
            ]
        ],

        'phone' => [
            'db_element' => true,
            'db_field_name' => 'advert_phone',
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH
                ],
                PhoneValidator::class => [],
            ]
        ],

        'telegram' => [
            'db_element' => true,
            'db_field_name' => 'advert_telegram',
            'validators' => [
                TelegramValidator::class => [],
            ]
        ],

        'url' => [
            'type' => Url::class,
            'db_element' => true,
            'db_field_name' => 'advert_url',
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH
                ],
                UrlValidator::class => [],
            ]
        ],

        'skype' => [
            'db_element' => true,
            'db_field_name' => 'advert_skype',
            'validators' => [
                SkypeValidator::class => [],
            ]
        ],

        'user_name' => [
            'db_element' => true,
            'db_field_name' => 'advert_user_name',
            'validators' => [
                HasBadUrlValidator::class => ['break' => false],
                HasBadEmailValidator::class => [],
                ProfanityWordsValidator::class => [],
                StopWordsValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 50
                ],
            ]
        ],

        'main_email' => [
            'db_element' => true,
            'db_field_name' => 'advert_main_email',
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

        'main_phone' => [
            'db_element' => true,
            'db_field_name' => 'advert_main_phone',
            'default_value' => 1,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => ['min' => IntRangeValidator::ZERO, 'max' => IntRangeValidator::ONE],
            ]
        ],

        'main_telegram' => [
            'db_element' => true,
            'db_field_name' => 'advert_main_telegram',
            'default_value' => 1,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => ['min' => IntRangeValidator::ZERO, 'max' => IntRangeValidator::ONE],
            ]
        ],

        'main_url' => [
            'db_element' => true,
            'db_field_name' => 'advert_main_url',
            'default_value' => 1,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => ['min' => IntRangeValidator::ZERO, 'max' => IntRangeValidator::ONE],
            ]
        ],

        'main_skype' => [
            'db_element' => true,
            'db_field_name' => 'advert_main_skype',
            'default_value' => 1,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => ['min' => IntRangeValidator::ZERO, 'max' => IntRangeValidator::ONE],
            ]
        ],

        'main_user_name' => [
            'db_element' => true,
            'db_field_name' => 'advert_main_user_name',
            'default_value' => 1,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => ['min' => IntRangeValidator::ZERO, 'max' => IntRangeValidator::ONE],
            ]
        ],

        'use_contact' => [
            'db_element' => true,
            'db_field_name' => 'advert_use_contact',
            'default_value' => 1,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => ['min' => IntRangeValidator::ZERO, 'max' => IntRangeValidator::ONE],
            ]
        ],

        'place_country' => [
            'db_element' => true,
            'db_field_name' => 'advert_place_country',
            'default_value' => 0,
            'validators' => [
                IsNotEmptyValidator::class => [],
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'place_region' => [
            'db_element' => true,
            'db_field_name' => 'advert_place_region',
            'default_value' => 0,
            'validators' => [
                IsNotEmptyValidator::class => [],
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'place_city' => [
            'db_element' => true,
            'db_field_name' => 'advert_place_city',
            'default_value' => 0,
            'validators' => [
                IsNotEmptyValidator::class => [],
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'create_date' => [
            'type' => DateTime::class,
            'db_element' => true,
            'db_field_name' => 'advert_create_date',
            'default_value' => 'now',
            'record_once' => true,
            'validators' => [
                DateCorrectValidator::class => ['format' => DateTime::FORMAT_DATETIME],
            ]
        ],

        'edit_date' => [
            'type' => DateTime::class,
            'db_element' => true,
            'db_field_name' => 'advert_edit_date',
            'validators' => [
                DateCorrectValidator::class => ['format' => DateTime::FORMAT_DATETIME],
            ]
        ],

        'vip_date' => [
            'type' => DateTime::class,
            'db_element' => true,
            'db_field_name' => 'advert_vip_date',
            'validators' => [
                DateCorrectValidator::class => ['format' => DateTime::FORMAT_DATETIME],
            ]
        ],

        'special_date' => [
            'type' => DateTime::class,
            'db_element' => true,
            'db_field_name' => 'advert_special_date',
            'validators' => [
                DateCorrectValidator::class => ['format' => DateTime::FORMAT_DATETIME],
            ]
        ],

        'view_count' => [
            'db_element' => true,
            'db_field_name' => 'advert_view_count',
            'default_value' => 0,
            'record_once' => true,
            'validators' => [
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'was_moderated' => [
            'db_element' => true,
            'db_field_name' => 'advert_was_moderated',
            'default_value' => 0,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => ['min' => IntRangeValidator::ZERO, 'max' => IntRangeValidator::ONE],
            ]
        ],

        'thumbnail_count' => [
            'db_element' => true,
            'db_field_name' => 'advert_thumbnail_count',
            'default_value' => 0,
            'record_once' => true,
            'validators' => [
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'hash' => [
            'db_element' => true,
            'default_excluded' => true,
            'db_field_name' => 'advert_hash',
            'validators' => []
        ],

        'thumbnail_file_name' => [
            'db_element' => true,
            'db_field_name' => 'advert_thumbnail_file_name',
            'record_once' => true,
            'validators' => [
                Md5FileNameValidator::class => []
            ]
        ],

        'payment' => [
            'db_element' => true,
            'db_field_name' => 'advert_payment',
            'default_value' => 0,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => ['min' => IntRangeValidator::ZERO, 'max' => IntRangeValidator::ONE],
            ]
        ],

        'score' => [
            'db_element' => false,
            'default_value' => 0
        ],
    ];

    /**
     * Список объектов изображений типа Thumbnail,
     * привязанных к данному объявлению.
     *
     * @var CoverArray|null
     */
    protected ?CoverArray $thumbnails = null;

    /** @var Prodamus|null */
    private ?Prodamus $prodamus = null;

    /**
     * @return bool
     */
    public function getIsVip(): bool
    {
        return !empty($this->getVipDate()) && $this->getVipDate() >= new DateTime();
    }

    /**
     * @return bool
     */
    public function getIsSpecial(): bool
    {
        return !empty($this->getSpecialDate()) && $this->getSpecialDate() >= new DateTime();
    }

    /**
     * @param string $text
     * @return string
     */
    public function _setText(string $text): string
    {
        $text = EmojiRemover::filter($text);
        $this->setHashString($text);

        return $text;
    }

    /**
     * @param string $header
     * @return string
     */
    public function _setHeader(string $header): string
    {
        $header = Format::spaceAfterPunctuation($header);
        $header = EmojiRemover::filter($header);

        return Strings::mb_ucfirst($header);
    }

    /**
     * Инвертирует активность объявления.
     *
     * @return static
     */
    public function invertActive(): static
    {
        $this->setActive((int) !$this->getActive());

        return $this;
    }

    /**
     * Возвращает объект DateInterval, указывающий сколько осталось до-
     * или уже прошло времени после- времени create_date + $hour часов.
     *
     * @param int|string $hour
     * @return DateInterval
     * @throws Exception
     */
    public function getExpireRestrictionUpdateCreateDate(int|string $hour = 1): DateInterval
    {
        $interval = new DateInterval('PT' . (int) $hour . 'H');
        $t_date = clone $this->getCreateDate();
        $t_date->add($interval);

        $now = new DateTime();
        return $now->diff($t_date);
    }

    /**
     * Устанавливает свойство create_date в значение
     * текущего времени - 1 секунда.
     *
     * @return static
     */
    public function setCurrentCreateDateDiffSecond(): static
    {
        $now = new DateTime();
        $now->setTimestamp(time() - 1);
        $this->setCreateDate($now);

        return $this;
    }

    /**
     * Устанавлитвает дату редактирования + 1 секунда, что бы обнулить кэш If-Modified-Since
     *
     * @return static
     */
    public function setEditDateDiffToOneSecondMore(): static
    {
        if (is_null($this->getEditDate())) {
            $this->setEditDate(new DateTime());

            return $this;
        }

        $editDate = clone $this->getEditDate();
        $editDate->add(new DateInterval('PT1S'));
        $this->setEditDate($editDate);

        return $this;
    }

    /**
     * Проставляет дату для VIP объявления на $days дней.
     *
     * @param int|string $days
     * @return static
     * @throws Exception
     */
    public function setVipStatus(int|string $days = 30): static
    {
        $time = new DateTime();
        $time->add(new DateInterval('P' . $days . 'D'));
        $this->setVipDate($time);

        return $this;
    }

    /**
     * Проставляет дату для Special объявления на $days дней.
     *
     * @param int|string $days
     * @return static
     * @throws Exception
     */
    public function setSpecialStatus(int|string $days = 30): static
    {
        $time = new DateTime();
        $time->add(new DateInterval('P' . $days . 'D'));
        $this->setSpecialDate($time);

        return $this;
    }

    /**
     * Возвращает true, если это объявление принадлежит пользователю $user.
     *
     * @param User $user
     * @return bool
     */
    public function belongToUser(User $user): bool
    {
        return ($user->getUniqueCookieId() == $this->getUniqueUserCookieId() or $this->belongToRegisterUser($user));
    }

    /**
     * Возвращает true, если это объявление принадлежит зарегистрированному пользователю $user.
     *
     * @param User $user
     * @return bool
     */
    public function belongToRegisterUser(User $user): bool
    {
        return (!$user->isGuest() && $user->getId() == $this->getIdUser());
    }

    /**
     * Возвращает true, если это объявление принадлежит незарегистрированному пользователю.
     *
     * @return bool
     */
    public function belongToUnregisterUser(): bool
    {
        return $this->getIdUser() == User::GUEST_USER_ID;
    }

    /**
     * Метод получения объекта CoverArray с одним элементом - объектом главного изображения,
     * которое находится на основании денормализованных данных в таблице advert (поле `advert_thumbnail_file_name`).
     * Если изображения у объявления нет, то будет возвращён объект CoverArray без элементов.
     *
     * @return CoverArray
     */
    public function getDenormalizationThumbnailsList(): CoverArray
    {
        if (!$this->thumbnails) {
            $this->thumbnails = new CoverArray();

            if ($this->getThumbnailFileName()) {
                $thumbnail = new Thumbnail();
                $thumbnail->setData(['file_name' => $this->getThumbnailFileName()]);

                $this->thumbnails->append($thumbnail);
            }
        }

        return $this->thumbnails;
    }

    /**
     * Получает и возвращает список объектов изображений, закреплённых за этим объявлением.
     * Lazy Load.
     *
     * @return CoverArray
     */
    public function getThumbnailsList(): CoverArray
    {
        if (!$this->thumbnails) {
            $this->thumbnails = $this->getId()
                ? $this->getMapperManager()->getMapper(ThumbnailMapper::class)->findByAdvert($this)
                : new CoverArray();
        }

        return $this->thumbnails;
    }

    /**
     * Возвращает список объектов изображений, загруженных на основе массива их идентификаторов.
     * Lazy Load.
     * Метод исключительно для формы добавления изображений, когда в виду ошибочного POST-запроса
     * в сценарий приходят ID's уже загруженных для этой сущности изображений.
     *
     * @param CoverArray $ids
     * @return CoverArray
     */
    public function loadThumbnailsListByIds(CoverArray $ids): CoverArray
    {
        if (!$this->thumbnails) {
            $this->thumbnails = new CoverArray();

            foreach ($ids as $id) {
                $thumbnail = $this->getMapperManager()->getMapper(ThumbnailMapper::class)->findModelById($id);

                if ($thumbnail->getId()) {
                    $this->thumbnails->append($thumbnail);
                }
            }
        }

        return $this->thumbnails;
    }

    /**
     * @return static
     * @throws MySqlException
     */
    public function save(): static
    {
        return parent::save()->saveThumbnails();
    }

    /**
     * Отвязывает все изображения объявления.
     * Не используется, т.к. отвязка идёт в триггере на таблице `advert`.
     *
     * @return static
     */
    public function deleteThumbnails(): static
    {
        foreach ($this->getThumbnailsList() as $thumbnail) {
            $thumbnail->unlink();
        }

        return $this;
    }

    /**
     * Связывает запись об изображениях $this->thumbnail с данным объявлением.
     *
     * @return static
     */
    public function saveThumbnails(): static
    {
        if (is_object($this->thumbnails) && $this->thumbnails instanceof CoverArray && $this->thumbnails->count()) {
            foreach ($this->thumbnails as $thumbnail) {
                if ($thumbnail instanceof Thumbnail) {
                    $thumbnail->link($this);
                }
            }
        }

        return $this;
    }

    /**
     * Дата последней модификации документа для протокола HTTP.
     *
     * @return DateTime
     */
    public function getLastModifiedDate(): DateTime
    {
        // Если заблокировали пользователя, тот надо изменить дату,
        // что бы "плохие" объявления не были доступны даже в кэше.
        /* @var $user User */
        /*$user = $this->getMapperManager()->getMapper(UserMapper::class)->findModelById($this->getIdUser());
        if (!$user->isGuest() && !$user->getActive()) {
            return $user->getVisitdate();
        }*/

        if ($this->getEditDate() !== null && $this->getEditDate() > $this->getCreateDate()) {
            return $this->getEditDate();
        }

        return $this->getCreateDate();
    }

    /**
     * Возвращает объект системы оплаты.
     *
     * @return Prodamus
     */
    public function getMerchant(): Prodamus
    {
        return $this->getProdamusInstance();
    }

    /**
     * Создаёт хэш объявления на основании текста объявления.
     *
     * @param $string
     * @return static
     */
    protected function setHashString($string): static
    {
        preg_match_all(self::$text_hash_pattern, $string, $matches);
        $this->data['hash'] = md5(implode('', $matches[0]));

        return $this;
    }

    /**
     * @return Prodamus
     */
    protected function getProdamusInstance(): Prodamus
    {
        if (!$this->prodamus) {
            $this->prodamus = new Prodamus();
            $this->prodamus->setAdvert($this);

            /** @var User $user */
            $user = $this->getMapperManager()->getMapper(UserMapper::class)->findModelById(
                $this->getIdUser()
            );
            $this->prodamus->setUser($user);
        }

        return $this->prodamus;
    }
}