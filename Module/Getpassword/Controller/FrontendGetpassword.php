<?php

namespace Krugozor\Framework\Module\Getpassword\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Cover\Data\PostData;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Http\Response;
use Krugozor\Framework\Module\Getpassword\Mapper\GetpasswordMapper;
use Krugozor\Framework\Module\Getpassword\Service\GetpasswordService;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Type\Date\DateTime;
use Krugozor\Framework\Validator\Validator;
use Krugozor\Framework\Validator\StringLengthValidator;
use Krugozor\Framework\Validator\CharPasswordValidator;
use Krugozor\Framework\Validator\EmailValidator;
use Krugozor\Framework\View;
use Throwable;

class FrontendGetpassword extends AbstractController
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
     * @return Notification|View|Response
     * @throws MySqlException
     */
    public function run(): Notification|View|Response
    {
        if (!$this->getCurrentUser()->isGuest()) {
            return $this->getResponse()->setHeader(
                Response::HEADER_LOCATION,
                '/authorization/frontend-login/'
            );
        }

        $this->getView()->setCurrentUser($this->getCurrentUser());

        if (Request::isPost() && $notification = $this->post()) {
            return $notification;
        }

        return $this->getView();
    }

    /**
     * @return Notification|null
     */
    private function post(): ?Notification
    {
        $validator = new Validator('common/general', 'getpassword/getpassword');

        $post = $this->getRequest()->getPost('user', PostData::class);

        if ($user_login = $post->get('login')) {
            $validator->add('user_login', new StringLengthValidator($user_login));
            $validator->add('user_login', new CharPasswordValidator($user_login));
        }
        if ($user_email = $post->get('email')) {
            $validator->add('user_email', new StringLengthValidator($user_email));
            $validator->add('user_email', new EmailValidator($user_email));
        }

        if (!$user_login && !$user_email) {
            $validator->addError('common_error', 'NON_EXIST_REG_DATA');
        }

        $validator->validate();

        if ($errors = $validator->getErrors()) {
            $this->getView()->getErrors()->setData($errors);

            $notification = $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.post_errors'));
            $this->getView()->setNotification($notification);

            return null;
        }

        /** @var User $user */
        $user = match (true) {
            !empty($user_login) && !empty($user_email)
                => $this->getMapper(UserMapper::class)->findByLoginOrEmail($user_login, $user_email),
            !empty($user_login) => $this->getMapper(UserMapper::class)->findByLogin($user_login),
            !empty($user_email) => $this->getMapper(UserMapper::class)->findByEmail($user_email),
            default => $this->getMapper(UserMapper::class)->createModel()
        };

        $notification = $this->createNotification();

        if (!$user->getId()) {
            $notification->setType(Notification::TYPE_ALERT);
            $notification->setMessage($this->getView()->getLang()->get('notification.message.user_not_exist'));
            $this->getView()->setNotification($notification);

            return null;
        } elseif (!$user->getEmail()->getValue()) {
            $notification->setType(Notification::TYPE_WARNING);
            $notification->setMessage($this->getView()->getLang()->get('notification.message.user_mail_not_exist'));
            $this->getView()->setNotification($notification);

            return null;
        } else {
            /** @var MailQueue $mailQueue */
            $mailQueue = $this->getMapper(MailQueueMapper::class)->createModel();
            $mailQueue
                ->setSendDate(new DateTime())
                ->setFromEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                ->setReplyEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                ->setHeader($this->getView()->getLang()->get('mail.header.send_mail_user'))
                ->setTemplate($this->getRealLocalTemplatePath('FrontendGetpasswordSendTest'));

            try {
                (new GetpasswordService())
                    ->setUser($user)
                    ->setUserMapper($this->getMapper(UserMapper::class))
                    ->setMailQueue($mailQueue)
                    ->setMailQueueMapper($this->getMapper(MailQueueMapper::class))
                    ->setGetpasswordMapper($this->getMapper(GetpasswordMapper::class))
                    ->sendEmailWithHash();

                return $notification
                    ->setMessage($this->getView()->getLang()->get('notification.message.test_send_ok'))
                    ->setRedirectUrl($this->getRequest()->getCanonicalRequestUri()->getSimpleUriValue())
                    ->run();
            } catch (Throwable $t) {
                $validator->addError('common_error', 'SYSTEM_ERROR');

                $this->getView()->getErrors()->setData($validator->getErrors());

                ErrorLog::write($t->getMessage());

                $notification->setType(Notification::TYPE_ALERT);
                $notification->setMessage($this->getView()->getLang()->get('notification.message.unknown_error'));
                $this->getView()->setNotification($notification);

                return null;
            }
        }
    }
}