<?php

declare(strict_types=1);

namespace Krugozor\Framework\Type;

/**
 * Тип `email`
 */
class Email implements TypeInterface
{
    /** @var string|null */
    protected ?string $email;

    /**
     * @param string|null $email
     */
    public function __construct(?string $email)
    {
        $this->email = $email;
    }

    /**
     * @return string|null
     */
    public function getValue(): ?string
    {
        return $this->email;
    }

    /**
     * Возвращает md5 хэш email-адреса + соль.
     *
     * Что бы предотвратить сбор email-адресов пользователей, с помощью ajax формируется запрос типа
     * /advert/frontend-ajax-get-email/id/79305/hash/75342c398ef420d61a8defe4291332dd
     * где id - ID пользователя, а хэш - результат работы этого метода.
     * Контроллер frontend-ajax-get-email находит пользователя по ID и сравнивает хэш из запроса
     * с хэшем, возвращенным этим методом. Если строки совпадают, то контроллер в качестве ответа
     * отдаёт email адрес пользователя.
     *
     * @return string
     */
    public function getMailHashForAccessView(): string
    {
        return md5($this->getValue() . base64_encode($this->getValue()));
    }
}