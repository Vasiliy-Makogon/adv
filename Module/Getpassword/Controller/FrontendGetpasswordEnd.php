<?php

namespace Krugozor\Framework\Module\Getpassword\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Getpassword\Mapper\GetpasswordMapper;
use Krugozor\Framework\Module\Getpassword\Service\GetpasswordService;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Type\Date\DateTime;
use Krugozor\Framework\Validator\Validator;
use Krugozor\Framework\View;
use Throwable;

class FrontendGetpasswordEnd extends AbstractController
{
    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/FrontendGeneral',
            'Local/FrontendGeneral',
            $this->getRequest()->getVirtualControllerPath()
        ];
    }

    /**
     * @return Notification|View
     * @throws MySqlException
     */
    public function run(): Notification|View
    {
        $this->getView()->setCurrentUser($this->getCurrentUser());

        try {
            $service = new GetpasswordService();
            $service
                ->setGetpasswordMapper($this->getMapper(GetpasswordMapper::class))
                ->setMailQueueMapper($this->getMapper(MailQueueMapper::class))
                ->setUserMapper($this->getMapper(UserMapper::class));

            $hash = $this->getRequest()->getRequest('hash', Request::SANITIZE_STRING);
            if (!$service->isValidHash($hash)) {
                return $this->createNotification(Notification::TYPE_WARNING)
                    ->setHeader($this->getView()->getLang()->get('notification.header.bad_hash'))
                    ->setMessage($this->getView()->getLang()->get('notification.message.bad_hash'))
                    ->setRedirectUrl('/authorization/frontend-login/')
                    ->run();
            }

            $mailQueue = $this->getMapper(MailQueueMapper::class)->createModel();
            $mailQueue
                ->setSendDate(new DateTime())
                ->setFromEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                ->setReplyEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                ->setHeader($this->getView()->getLang()->get('mail.header.send_mail_user'))
                ->setTemplate($this->getRealLocalTemplatePath('FrontendGetpasswordSendPassword'));

            $service
                ->setMailQueue($mailQueue)
                ->sendMailWithNewPassword();

            return $this->createNotification()
                ->setMessage($this->getView()->getLang()->get('notification.message.getpassword_send_message'))
                ->setRedirectUrl('/authorization/frontend-login/')
                ->run();
        } catch (Throwable $t) {
            $validator = new Validator('common/general');
            $validator->addError('common_error', 'SYSTEM_ERROR');

            $this->getView()->getErrors()->setData($validator->getErrors());

            ErrorLog::write($t->getMessage());

            $notification = $this->createNotification(Notification::TYPE_ALERT);
            $notification->setMessage($this->getView()->getLang()->get('notification.message.unknown_error'));
            $this->getView()->setNotification($notification);
        }

        return $this->getView();
    }
}