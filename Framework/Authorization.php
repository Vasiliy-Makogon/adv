<?php

declare(strict_types=1);

namespace Krugozor\Framework;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Http\Response;
use Krugozor\Framework\Module\Group\Model\Group;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Type\Date\DateTime;

/**
 * Авторизация, Аутентификация и выход из системы пользователя.
 */
class Authorization
{
    /**
     * Авторизация на год.
     *
     * @var int
     */
    const AUTHORIZATION_ON_YEAR = 360;

    /**
     * Имя cookie с ID пользователя.
     *
     * @var string
     */
    const ID_COOKIE_NAME = 'auth_id';

    /**
     * Имя cookie с хэшем пользователя.
     *
     * @var string
     */
    const HASH_COOKIE_NAME = 'auth_hash';

    /**
     * @param Request $request
     * @param Response $response
     * @param UserMapper|null $userMapper
     */
    public function __construct(
        protected Request $request,
        protected Response $response,
        protected ?UserMapper $userMapper = null
    ) {}

    /**
     * Ищет пользователя по логину и паролю.
     * Если пользователь найден, в response выставляются куки аутентификации.
     *
     * Куки представляют собой
     * - Идентификатор пользователя в СУБД
     * - Хэш md5 от связки логин_пользователя + хэш_пароля + соль
     *
     * @param string $login логин
     * @param string $password пароль
     * @param int $days время жизни куки в днях
     * @return bool
     */
    public function processAuthorization(string $login, string $password, int $days = 0): bool
    {
        $user = $this->userMapper->findByLoginPassword($login, $password);

        if ($user->getId()) {
            $time = $days ? time() + 60 * 60 * 24 * $days : 0;

            $this->response->setCookie(
                self::ID_COOKIE_NAME,
                (string) $user->getId(),
                $time,
                '/',
                Registry::getInstance()->get('HOSTINFO.DOMAIN'),
                (bool) Registry::getInstance()->get('SECURITY.USE_HTTPS'),
                session_get_cookie_params()['httponly']
            );

            $this->response->setCookie(
                self::HASH_COOKIE_NAME,
                $user->generateAuthHash(),
                $time,
                '/',
                Registry::getInstance()->get('HOSTINFO.DOMAIN'),
                (bool) Registry::getInstance()->get('SECURITY.USE_HTTPS'),
                session_get_cookie_params()['httponly']
            );

            return true;
        }

        return false;
    }

    /**
     * Устанавливает cookie с уникальными ID для идентификации зарегистрированных и незарегистрированных пользователей.
     *
     * @param User $user
     */
    public function processSettingsUniqueCookieId(User $user): void
    {
        if (!$this->request->getCookie(User::UNIQUE_USER_COOKIE_ID_NAME, Request::SANITIZE_STRING)) {
            $this->response->setCookie(
                User::UNIQUE_USER_COOKIE_ID_NAME,
                $user->getUniqueCookieId(),
                $user->getUniqueUserCookieIdLifetime(),
                '/',
                Registry::getInstance()->get('HOSTINFO.DOMAIN'),
                (bool) Registry::getInstance()->get('SECURITY.USE_HTTPS'),
                session_get_cookie_params()['httponly']
            );
        } else {
            $user->setUniqueCookieId(
                $this->request->getCookie(User::UNIQUE_USER_COOKIE_ID_NAME, Request::SANITIZE_STRING)
            );
        }
    }

    /**
     * Аутентификация пользователя на основании данных из COOKIE.
     *
     * @return User
     * @throws MySqlException
     */
    public function processAuthentication(): User
    {
        $userCookieId = $this->request->getCookie(self::ID_COOKIE_NAME, Request::SANITIZE_STRING);
        $userCookieHash = $this->request->getCookie(self::HASH_COOKIE_NAME, Request::SANITIZE_STRING);

        if ($userCookieId && $userCookieHash) {
            /** @var User $user */
            $user = $this->userMapper->findModelById($userCookieId);

            if ($user->getId() && $user->compareAuthHash($userCookieHash)) {
                $this->response->setCookie(
                    User::UNIQUE_USER_COOKIE_ID_NAME,
                    $user->getUniqueCookieId(),
                    $user->getUniqueUserCookieIdLifetime(),
                    '/',
                    Registry::getInstance()->get('HOSTINFO.DOMAIN'),
                    (bool) Registry::getInstance()->get('SECURITY.USE_HTTPS'),
                    session_get_cookie_params()['httponly']
                );

                $user->setVisitdate(new DateTime());
                $user->setIp($_SERVER['REMOTE_ADDR']);
                $this->userMapper->saveModel($user);

                return $user;
            } else {
                $this->logout();
            }
        }

        /** @var User $user */
        $user = $this->userMapper->createModel();
        $user->setId(User::GUEST_USER_ID);
        $user->setRegdate(null);
        $user->setFirstName('Анонимный пользователь');
        $user->setGroup(Group::ID_GROUP_GUEST);
        $user->setActive(1);

        return $user;
    }

    /**
     * Уничтожает сеанс (COOKIE) текущего пользователя.
     */
    public function logout(): void
    {
        $time = time() - 60 * 60 * 24 * 31;

        $this->response->setCookie(
            self::ID_COOKIE_NAME,
            '',
            $time,
            '/',
            Registry::getInstance()->get('HOSTINFO.DOMAIN'),
            (bool) Registry::getInstance()->get('SECURITY.USE_HTTPS'),
            session_get_cookie_params()['httponly']
        );

        $this->response->setCookie(
            self::HASH_COOKIE_NAME,
            '',
            $time,
            '/',
            Registry::getInstance()->get('HOSTINFO.DOMAIN'),
            (bool) Registry::getInstance()->get('SECURITY.USE_HTTPS'),
            session_get_cookie_params()['httponly']
        );
    }
}