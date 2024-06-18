<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Model;

use Exception;
use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Validator\RussianTextsValidator;
use Krugozor\Framework\Module\Advert\Validator\StopWordsValidator;
use Krugozor\Framework\Module\Group\Mapper\GroupMapper;
use Krugozor\Framework\Module\Group\Model\Group;
use Krugozor\Framework\Module\User\Type\UserSex;
use Krugozor\Framework\Module\User\Type\UserType;
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
use Krugozor\Framework\Validator\PhoneValidator;
use Krugozor\Framework\Validator\ProfanityWordsValidator;
use Krugozor\Framework\Validator\SkypeValidator;
use Krugozor\Framework\Validator\StringLengthValidator;
use Krugozor\Framework\Validator\TelegramValidator;
use Krugozor\Framework\Validator\UrlValidator;
use Krugozor\Framework\Validator\VarEnumValidator;
use LogicException;

/**
 * @method setUniqueCookieId($getUniqueCookieId)
 * @method setSalt($salt)
 *
 * @method getActive()
 * @method setActive($active)
 *
 * @method getGroup()
 * @method setGroup($group)
 *
 * @method getLogin()
 * @method setLogin($login)
 *
 * @method Email getEmail()
 * @method setEmail($email)
 *
 * @method setPassword($password)
 * @method getPassword()
 *
 * @method DateTime getRegdate()
 * @method setRegdate($regdate)
 *
 * @method DateTime getVisitdate()
 * @method setVisitdate($visitdate)
 *
 * @method getIp()
 * @method setIp($ip)
 *
 * @method getFirstName()
 * @method setFirstName($firstName)
 *
 * @method getLastName()
 * @method setLastName($lastName)
 *
 * @method DateTime getAge()
 * @method setAge($age)
 *
 * @method UserSex getSex()
 * @method setSex($sex)
 *
 * @method getCity()
 * @method setCity($city)
 *
 * @method getRegion()
 * @method setRegion($region)
 *
 * @method getCountry()
 * @method setCountry($country)
 *
 * @method getPhone()
 * @method setPhone($phone)
 *
 * @method getTelegram()
 * @method setTelegram($telegram)
 *
 * @method Url getUrl()
 * @method setUrl($url)
 *
 * @method getSkype()
 * @method setSkype($skype)
 *
 * @method getContact()
 * @method setContact($contact)
 *
 * @method UserType getType()
 * @method setType($type)
 */
class User extends AbstractModel
{
    /**
     * @inheritdoc
     */
    protected static ?string $db_field_prefix = 'user';

    /** @var int ID системного пользователя "Гость" в таблице. */
    public const GUEST_USER_ID = -1;

    /** @var int Время жизни уникального cookie пользователя, в годах. */
    public const UNIQUE_USER_COOKIE_ID_LIFETIME_YEARS = 10;

    /** @var string Имя уникальной cookie пользователя */
    public const UNIQUE_USER_COOKIE_ID_NAME = 'unique_user_cookie_id';

    /** @var bool|null true, если пользователь принадлежит к группе "пользователи" */
    protected ?bool $is_user = null;

    /** @var bool|null true, если пользователь принадлежит к группе "администраторы" */
    protected ?bool $is_administrator = null;

    /**
     * @inheritdoc
     */
    protected static array $model_attributes = [
        'id' => [
            'db_element' => false,
            'default_value' => 0,
            'validators' => [
                DecimalValidator::class => ['signed' => true],
            ]
        ],

        'unique_cookie_id' => [
            'db_element' => true,
            'db_field_name' => 'user_unique_cookie_id',
            'record_once' => true,
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::MD5_MAX_LENGTH,
                    'stop' => StringLengthValidator::MD5_MAX_LENGTH
                ],
                CharPasswordValidator::class => [],
            ]
        ],

        'salt' => [
            'db_element' => true,
            'db_field_name' => 'user_salt',
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
            'db_field_name' => 'user_active',
            'default_value' => 1,
            'validators' => [
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => ['signed' => false],
                IntRangeValidator::class => [
                    'min' => IntRangeValidator::ZERO,
                    'max' => IntRangeValidator::ONE,
                ],
            ]
        ],

        'group' => [
            'db_element' => true,
            'db_field_name' => 'user_group',
            'default_value' => Group::ID_GROUP_USER,
            'validators' => [
                IsNotEmptyValidator::class => [],
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'login' => [
            'db_element' => true,
            'db_field_name' => 'user_login',
            'validators' => [
                IsNotEmptyValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH
                ],
                CharPasswordValidator::class => [],
            ]
        ],

        'email' => [
            'type' => Email::class,
            'db_element' => true,
            'default_value' => null,
            'db_field_name' => 'user_email',
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH
                ],
                EmailValidator::class => [],
            ]
        ],

        'password' => [
            'db_element' => true,
            'db_field_name' => 'user_password',
            'validators' => [
                CharPasswordValidator::class => [],
            ]
        ],

        'regdate' => [
            'type' => DateTime::class,
            'db_element' => true,
            'db_field_name' => 'user_regdate',
            'default_value' => 'now',
            'record_once' => true,
            'validators' => [
                DateCorrectValidator::class => ['format' => DateTime::FORMAT_DATETIME],
            ]
        ],

        'visitdate' => [
            'type' => DateTime::class,
            'db_element' => true,
            'db_field_name' => 'user_visitdate',
            'default_value' => null,
            'validators' => [
                DateCorrectValidator::class => ['format' => DateTime::FORMAT_DATETIME],
            ]
        ],

        'ip' => [
            'db_element' => true,
            'db_field_name' => 'user_ip',
            'default_value' => null,
            'validators' => []
        ],

        'first_name' => [
            'db_element' => true,
            'db_field_name' => 'user_first_name',
            'default_value' => null,
            'validators' => [
                HasBadUrlValidator::class => ['break' => false],
                HasBadEmailValidator::class => [],
                ProfanityWordsValidator::class => [],
                StopWordsValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 30
                ],
            ]
        ],

        'last_name' => [
            'db_element' => true,
            'db_field_name' => 'user_last_name',
            'default_value' => null,
            'validators' => [
                HasBadUrlValidator::class => ['break' => false],
                HasBadEmailValidator::class => [],
                ProfanityWordsValidator::class => [],
                StopWordsValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 30
                ],
            ]
        ],

        'age' => [
            'type' => DateTime::class,
            'db_element' => true,
            'db_field_name' => 'user_age',
            'default_value' => null,
            'validators' => [
                DateCorrectValidator::class => ['format' => DateTime::FORMAT_DATETIME],
            ]
        ],

        'sex' => [
            'type' => UserSex::class,
            'db_element' => true,
            'db_field_name' => 'user_sex',
            'default_value' => null,
            'validators' => [
                StringLengthValidator::class => ['start' => 1, 'stop' => 1],
                VarEnumValidator::class => ['enum' => [
                    UserSex::TYPE_MALE, UserSex::TYPE_FEMALE
                ]],
            ]
        ],

        'city' => [
            'db_element' => true,
            'db_field_name' => 'user_city',
            'default_value' => 0,
            'validators' => [
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'region' => [
            'db_element' => true,
            'db_field_name' => 'user_region',
            'default_value' => 0,
            'validators' => [
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'country' => [
            'db_element' => true,
            'db_field_name' => 'user_country',
            'default_value' => 0,
            'validators' => [
                DecimalValidator::class => ['signed' => false],
            ]
        ],

        'phone' => [
            'db_element' => true,
            'db_field_name' => 'user_phone',
            'default_value' => null,
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
            'db_field_name' => 'user_telegram',
            'default_value' => null,
            'validators' => [
                TelegramValidator::class => [],
            ]
        ],

        'url' => [
            'db_element' => true,
            'type' => Url::class,
            'db_field_name' => 'user_url',
            'default_value' => null,
            'validators' => [
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => StringLengthValidator::VARCHAR_MAX_LENGTH
                ],
                UrlValidator::class => []
            ]
        ],

        'skype' => [
            'db_element' => true,
            'db_field_name' => 'user_skype',
            'default_value' => null,
            'validators' => [
                SkypeValidator::class => [],
            ]
        ],

        'contact' => [
            'db_element' => true,
            'db_field_name' => 'user_contact',
            'validators' => [
                HasBadUrlValidator::class => ['break' => false],
                HasBadEmailValidator::class => [],
                ProfanityWordsValidator::class => [],
                StopWordsValidator::class => [],
                RussianTextsValidator::class => [],
                StringLengthValidator::class => [
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 500
                ],
            ]
        ],

        'type' => [
            'db_element' => true,
            'type' => UserType::class,
            'db_field_name' => 'user_type',
            'validators' => [
                VarEnumValidator::class => [
                    'enum' => [UserType::TYPE_COMPANY, UserType::TYPE_PRIVATE_PERSON]
                ],
            ]
        ],
    ];

    /**
     * Генерирует хэш для cookie аутентификации.
     *
     * @return string
     */
    public function generateAuthHash(): string
    {
        return md5(implode('', [
            $this->getLogin(), $this->getPassword(), $this->getSalt()
        ]));
    }

    /**
     * @param string $hash
     * @return bool
     */
    public function compareAuthHash(string $hash): bool
    {
        return $this->generateAuthHash() === $hash;
    }

    /**
     * Проверяет доступ пользователя к контроллеру $controller_key модуля $module_key.
     * Возвращает TRUE, если доступ разрешён и FALSE в противном случае.
     *
     * @param string $module_key
     * @param string $controller_key
     * @return bool
     */
    public function checkAccesses(string $module_key, string $controller_key): bool
    {
        /* @var $group Group */
        $group = $this->getMapperManager()->getMapper(GroupMapper::class)->findModelById($this->getGroup());

        if ($group->getActive() == 0) {
            return false;
        }

        return $group->getDenormalizedAccesses()->checkAccess($module_key, $controller_key)
            &&
            ($this->isGuest() || $this->getActive());
    }

    /**
     * @param array|CoverArray $data
     * @param array $excluded_keys
     * @return static
     */
    public function setData(iterable $data, array $excluded_keys = []): static
    {
        $object = parent::setData($data, $excluded_keys);

        if (!empty($data['age_day']) && !empty($data['age_month']) && !empty($data['age_year'])) {
            try {
                $age_day = (int) $data['age_day'];
                $age_month = (int) $data['age_month'];
                $age_year = (int) $data['age_year'];

                $age = DateTime::createDateTimeFromFormat(
                    'j-n-Y H:i:s',
                    sprintf('%s-%s-%s 00:00:00', $age_day, $age_month, $age_year)
                );
                $object->setAge($age);
            } catch (Exception) {
                $object->setAge(null);
            }
        }

        return $this;
    }

    /**
     * Возвращает true, если пользователь принадлежит к группе "Гости",
     * false в ином случае.
     *
     * @return bool
     */
    final public function isGuest(): bool
    {
        return $this->getId() == self::GUEST_USER_ID;
    }

    /**
     * Возвращает true, если пользователь принадлежит к группе "Пользователи",
     * false в ином случае.
     *
     * @return bool
     */
    final public function isUser(): bool
    {
        if ($this->is_user === null) {
            if ($this->isGuest()) {
                return false;
            }

            $this->is_user = $this->getGroup() == $this->getMapperManager()->getMapper(GroupMapper::class)
                    ->findGroupByAlias('user')
                    ->getId();
        }

        return $this->is_user;
    }

    /**
     * Возвращает true, если пользователь принадлежит к группе "Администраторы",
     * false в ином случае.
     *
     * @return bool
     */
    final public function isAdministrator(): bool
    {
        if ($this->is_administrator === null) {
            if ($this->isGuest()) {
                return false;
            }

            $this->is_administrator = $this->getGroup() == $this->getMapperManager()->getMapper(GroupMapper::class)
                    ->findGroupByAlias('administrator')
                    ->getId();
        }

        return $this->is_administrator;
    }

    /**
     * Возвращает уникальный ID пользователя.
     * Данный идентификатор ставится в cookie обозревателя пользователя при его первом заходе на сайт.
     * В дальнейшем, если пользователь регистрируется, данный идентификатор пишется в базу и при каждом процессе
     * авторизации/аутентификации достается из базы и также ставится в cookie. Таким образом пользователь всегда
     * опознается, будь он авторизован или нет.
     *
     * @return string
     */
    final public function getUniqueCookieId(): string
    {
        if (empty($this->data['unique_cookie_id'])) {
            $this->setUniqueCookieId(Strings::getUnique());
        }

        return $this->data['unique_cookie_id'];
    }

    /**
     * @return string
     */
    final public function getSalt(): string
    {
        if (empty($this->data['salt'])) {
            $this->setSalt(Strings::getUnique());
        }

        return $this->data['salt'];
    }

    /**
     * Возвращает время жизни уникального ID пользователя в cookie.
     *
     * @return int
     */
    final public function getUniqueUserCookieIdLifetime(): int
    {
        return time() + 60 * 60 * 24 * 365 * self::UNIQUE_USER_COOKIE_ID_LIFETIME_YEARS;
    }

    /**
     * Возвращает полное имя пользователя (имя фамилия).
     *
     * @return string
     */
    public function getFullName(): string
    {
        return implode(' ', array_filter([$this->getFirstName(), $this->getLastName()]));
    }

    /**
     * Возвращает полное имя пользователя или его логин.
     *
     * @return string|null
     */
    public function getFullNameOrLogin(): ?string
    {
        return $this->getFullName() ? $this->getFullName() : $this->getLogin();
    }

    /**
     * @return int|null
     */
    public function getAgeDay(): ?int
    {
        if ($this->age && $this->age instanceof DateTime) {
            return (int) $this->age->format('j');
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getAgeMonth(): ?int
    {
        if ($this->age && $this->age instanceof DateTime) {
            return (int) $this->age->format('n');
        }

        return null;
    }

    /**
     * @return int|null
     */
    public function getAgeYear(): ?int
    {
        if ($this->age && $this->age instanceof DateTime) {
            return (int) $this->age->format('Y');
        }

        return null;
    }

    /**
     * @param mixed $id
     * @return $this
     * @see AbstractModel::setId
     *
     */
    final public function setId(mixed $id): static
    {
        if (!empty($this->data['id']) && $this->data['id'] != User::GUEST_USER_ID && $this->data['id'] != $id) {
            throw new LogicException(sprintf(
                '%s: Попытка переопределить значение ID объекта модели "%s" значением "%s"',
                __METHOD__,
                get_class($this),
                $id
            ));
        }

        $this->setAttribute('id', $id);

        return $this;
    }

    /**
     * Устанавливает пароль пользователя как хэш от строки $password + salt.
     *
     * @param string $password
     * @return $this
     */
    final public function setPasswordAsMd5(string $password): self
    {
        $this->setPassword($this->passwordToHash($password));

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function save(): static
    {
        // ленивая загрузку обязательных данных
        $this->getSalt();
        $this->getUniqueCookieId();

        return parent::save();
    }

    /**
     * Равен ли переданный пароль $password тому, что сохранён у пользователя в базе.
     *
     * @param string $password
     * @return bool
     */
    final public function isPasswordsEqual(string $password): bool
    {
        return $this->passwordToHash($password) === $this->getPassword();
    }

    /**
     * @param string $password
     * @return string
     */
    final protected function passwordToHash(string $password): string
    {
        return md5(implode('', [$password, $this->getSalt()]));
    }
}