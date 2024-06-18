<?php

namespace Krugozor\Framework\Module\Advert\Service;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Mapper\MapperManager;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Statical\Strings;

// Создание пользователя на базе существующего анонимного или нового объявления.
// @todo: если в системе уже есть пользователь с таким UUCID, то это не правильное поведение,
// @todo: т.к. будет два пользователя с одним и тем же уникальным идентификатором. Исправить.
class CreateUserFromAdvertService
{
    /** @var string $password пароль, который сгенерировали для пользователя */
    protected string $password;

    /** @var User $user созданный пользователь */
    protected User $user;

    /**
     * @param Advert $advert
     * @param MapperManager $mapperManager
     */
    public function __construct(
        protected Advert $advert,
        protected MapperManager $mapperManager
    ){}

    /**
     * @return static
     * @throws MySqlException
     */
    public function createUser(): static
    {
        if (!$this->advert->belongToUnregisterUser()) {
            throw new \RuntimeException(sprintf(
                '%s: Попытка создать пользователя на объявлении, закреплённым за зарегистрированным пользователем `%s`',
                __CLASS__,
                $this->advert->getIdUser()
            ));
        }

        $this->user = $this->createUserModelFromAdvert();
        $this->user->save();

        $this->mapperManager
            ->getMapper(AdvertMapper::class)
            ->attachGuestUserAdverts($this->user);

        // Убиваем кэш всех объявлений пользователя
        $userAdvertsList = $this->mapperManager
            ->getMapper(AdvertMapper::class)
            ->findModelListByUser($this->user);
        /** @var Advert $advert */
        foreach ($userAdvertsList as $advert) {
            $advert
                ->setEditDateDiffToOneSecondMore()
                ->save()
                ->deleteCache();
        }

        return $this;
    }

    /**
     * @return User
     */
    public function getUser(): User
    {
        return $this->user;
    }

    /**
     * @return string
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    /**
     * @return User
     */
    private function createUserModelFromAdvert(): User
    {
        /* @var $user User */
        $user = $this->mapperManager->getMapper(UserMapper::class)->createModel();

        $login = 'tmp_' . Strings::getUnique(10);
        $this->password = Strings::getUnique(7);

        $user->setLogin($login);
        $user->setPasswordAsMd5($this->password);
        $user->setUniqueCookieId($this->advert->getUniqueUserCookieId());

        $user->setFirstName($this->advert->getUserName());
        $user->setEmail($this->advert->getEmail());
        $user->setPhone($this->advert->getPhone());
        $user->setTelegram($this->advert->getTelegram());
        $user->setUrl($this->advert->getUrl());
        $user->setSkype($this->advert->getSkype());

        $user->setCountry($this->advert->getPlaceCountry());
        $user->setRegion($this->advert->getPlaceRegion());
        $user->setCity($this->advert->getPlaceCity());

        return $user;
    }
}