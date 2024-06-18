<?php

declare(strict_types=1);

namespace Krugozor\Framework\Service;

use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;

/**
 * Базовый сервис для отправки писем с хэшем с целью проверки
 * подлинности пользовательских операций.
 */
abstract class AbstractSendMailWithHashService
{
    /** @var User */
    protected User $user;

    /** @var MailQueue */
    protected MailQueue $mailQueue;

    /** @var UserMapper */
    protected UserMapper $userMapper;

    /** @var MailQueueMapper */
    protected MailQueueMapper $mailQueueMapper;

    public function __construct()
    {}

    /**
     * @param User $user
     * @return static
     */
    public function setUser(User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @param UserMapper $userMapper
     * @return static
     */
    public function setUserMapper(UserMapper $userMapper): static
    {
        $this->userMapper = $userMapper;

        return $this;
    }

    /**
     * @param MailQueue $mailQueue
     * @return static
     */
    public function setMailQueue(MailQueue $mailQueue): static
    {
        $this->mailQueue = $mailQueue;

        return $this;
    }

    /**
     * @param MailQueueMapper $mailQueueMapper
     * @return static
     */
    public function setMailQueueMapper(MailQueueMapper $mailQueueMapper): static
    {
        $this->mailQueueMapper = $mailQueueMapper;

        return $this;
    }

    /**
     * Отправляет письмо с уникальной ссылкой.
     */
    abstract public function sendEmailWithHash(): void;

    /**
     * Проверяет хэш $hash на валидность.
     * В случае успеха инстанцирует объект пользователя
     * и очищает таблицу учёта хэшей от записи с данными.
     *
     * @param string $hash хэш
     * @return bool
     */
    abstract public function isValidHash(string $hash): bool;
}