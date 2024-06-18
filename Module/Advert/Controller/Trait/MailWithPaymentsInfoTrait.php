<?php

namespace Krugozor\Framework\Module\Advert\Controller\Trait;

use Exception;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Type\Date\DateTime;

/**
 * Отправка письма с навязываием услуг
 */
trait MailWithPaymentsInfoTrait
{
    protected function sendMailWithPaymentsInfo(): void
    {
        if ($this->advert->getIdUser() != User::GUEST_USER_ID) {
            /** @var User $user */
            $user = $this->getMapper(UserMapper::class)->findModelById($this->advert->getIdUser());

            if (!$user->getEmail()->getValue() && $this->advert->getEmail()->getValue()) {
                $user->setEmail($this->advert->getEmail());
            }
        } else {
            /** @var User $user */
            $user = $this->getMapper(UserMapper::class)->createModel();
            $user->setFirstName($this->advert->getUserName());
            $user->setEmail($this->advert->getEmail());
        }

        if ($user->getEmail()->getValue()) {
            try {
                $mailQueue = new MailQueue();
                $mailQueue
                    ->setSendDate(new DateTime())
                    ->setToEmail($user->getEmail()->getValue())
                    ->setFromEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                    ->setReplyEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                    ->setHeader($this->getView()->getLang()->get('mail.header.advert_was_saved'))
                    ->setTemplate($this->getRealLocalTemplatePath('MailWithPaymentsInfo'))
                    ->setMailData([
                        'user' => $user,
                        'advert' => $this->advert,
                        'hostinfo' => Registry::getInstance()->get('HOSTINFO'),
                        'payments' => Registry::getInstance()->get('PAYMENTS'),
                    ]);
                $this->getMapper(MailQueueMapper::class)->saveModel($mailQueue);
            } catch (Exception $e) {
                ErrorLog::write($e->getMessage());
            }
        }
    }
}