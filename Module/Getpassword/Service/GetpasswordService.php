<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Getpassword\Service;

use InvalidArgumentException;
use Krugozor\Database\MySqlException;
use Krugozor\Framework\Module\Getpassword\Model\Getpassword;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Service\AbstractSendMailWithHashService;
use Krugozor\Framework\Module\Getpassword\Mapper\GetpasswordMapper;
use Krugozor\Framework\Statical\Strings;

/**
 * Сервис отправки писем с целю восстановления пароля пользователя.
 */
class GetpasswordService extends AbstractSendMailWithHashService
{
    /** @var GetpasswordMapper */
    protected GetpasswordMapper $getpasswordMapper;

    /**
     * @param GetpasswordMapper $getpasswordMapper
     * @return static
     */
    public function setGetpasswordMapper(GetpasswordMapper $getpasswordMapper): static
    {
        $this->getpasswordMapper = $getpasswordMapper;

        return $this;
    }

    /**
     * @throws MySqlException
     */
    public function sendEmailWithHash(): void
    {
        if ($this->user->getEmail()->getValue()) {
            $hash = Strings::getUnique();

            $this->mailQueue->setToEmail($this->user->getEmail()->getValue());
            $this->mailQueue->setMailData([
                'user' => $this->user,
                'hash' => $hash,
                'hostinfo' => Registry::getInstance()->get('HOSTINFO')
            ]);
            $this->mailQueue->save();

            /** @var Getpassword $getpassword */
            $getpassword = $this->getpasswordMapper->createModel();
            $getpassword->setUserId($this->user->getId());
            $getpassword->setHash($hash);
            $getpassword->save();
        } else {
            throw new InvalidArgumentException(sprintf(
                "%s: Не смогу отправить письмо, отсутствует email-адрес пользователя '%s'",
                __METHOD__,
                $this->user->getId()
            ));
        }
    }

    /**
     * @param string $hash хэш из запроса
     * @return bool
     */
    public function isValidHash(string $hash): bool
    {
        $getpassword = $this->getpasswordMapper->findByHash($hash);

        if ($getpassword->getId()) {
            $this->user = $this->userMapper->findModelById($getpassword->getUserId());

            if ($this->user->getId()) {
                $getpassword->delete();

                return true;
            }
        }

        return false;
    }

    /**
     * Меняет пароль у пользователя, отсылает новый пароль ему на email.
     *
     * @param string|null $new_password новый пароль пользователя
     * @throws InvalidArgumentException|MySqlException
     */
    public function sendMailWithNewPassword(?string $new_password = null)
    {
        $new_password = $new_password ?: Strings::getUnique(7);

        $this->user->setPasswordAsMd5($new_password);
        $this->user->save();

        if ($this->user->getEmail()->getValue()) {
            $this->mailQueue->setToEmail($this->user->getEmail()->getValue());
            $this->mailQueue->setMailData([
                'user' => $this->user,
                'new_password' => $new_password,
                'hostinfo' => Registry::getInstance()->get('HOSTINFO')
            ]);
            $this->mailQueue->save();
        } else {
            throw new InvalidArgumentException(sprintf(
                "%s: Не смогу отправить письмо, отсутствует email-адрес пользователя '%s'",
                __METHOD__,
                $this->user->getId()
            ));
        }
    }
}