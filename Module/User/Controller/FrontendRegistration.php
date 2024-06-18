<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Authorization;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Cover\Data\PostData;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\Captcha\Validator\CaptchaValidator;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Module\User\Mapper\InviteAnonymousUserMapper;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Module\User\Validator\TermsOfPrivacyValidator;
use Krugozor\Framework\Module\User\Validator\UserLoginExistsValidator;
use Krugozor\Framework\Module\User\Validator\UserPasswordsCompareValidator;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Session;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Type\Date\DateTime;
use Krugozor\Framework\Validator\Validator;
use Krugozor\Framework\Validator\IsNotEmptyStringValidator;
use Krugozor\Framework\Validator\CharPasswordValidator;
use Krugozor\Framework\Module\User\Validator\UserMailExistsValidator;
use Krugozor\Framework\Validator\IsNotEmptyValidator;
use Krugozor\Framework\View;
use Throwable;
use Krugozor\Framework\Module\User\Validator\TermsOfServiceValidator;

class FrontendRegistration extends AbstractController
{
    /**
     * @var User
     */
    private User $user;

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
        if (!$this->getCurrentUser()->isGuest()) {
            return $this->createNotification()
                ->setIsHidden(true)
                ->setRedirectUrl('/authorization/frontend-login/')
                ->run();
        }

        $this->getView()->getStorage()->offsetSet(
            'session_name',
            Session::getInstance('CAPTCHASID',null, [
                'cookie_secure' => Registry::getInstance()->get('SECURITY.USE_HTTPS'),
                'cookie_httponly' => session_get_cookie_params()['httponly']
            ])->getSessionName()
        );
        $this->getView()->getStorage()->offsetSet(
            'session_id',
            Session::getInstance()->getSessionId()
        );

        $this->user = $this->getMapper(UserMapper::class)->createModel();

        if (Request::isPost() && $notification = $this->post()) {
            return $notification;
        }

        $this->getView()->getStorage()->offsetSet('user', $this->user);
        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    private function post(): ?Notification
    {
        $postUserData = $this->getRequest()->getPost('user', PostData::class);
        $this->user->setData($postUserData, [
            'id', 'unique_cookie_id', 'salt', 'active', 'group', 'regdate', 'visitdate', 'ip',
        ]);

        $validator = new Validator(
            'common/general',
            'user/common',
            'user/registration',
            'captcha/common',
            'user/edit'
        );
        $validator->addModelErrors($this->user);

        $validator->add('captcha', new CaptchaValidator([
            $this->getRequest()->getPost('captcha_code', Request::SANITIZE_INT),
            Session::getInstance()->code
        ]));

        $validator->add('terms_of_service', new TermsOfServiceValidator(
            $this->getRequest()->getPost('terms_of_service', Request::SANITIZE_INT)
        ));
        $validator->add('terms_of_privacy', new TermsOfPrivacyValidator(
            $this->getRequest()->getPost('terms_of_privacy', Request::SANITIZE_INT)
        ));

        if ($this->user->getLogin()) {
            $validator->add('login',
                (new UserLoginExistsValidator($this->user))
                    ->setMapper($this->getMapper(UserMapper::class))
            );
        }

        $password_1 = $this->getRequest()->getPost('user.password_1', Request::SANITIZE_STRING);
        $password_2 = $this->getRequest()->getPost('user.password_2', Request::SANITIZE_STRING);

        $validator->add('password_1', new IsNotEmptyStringValidator($password_1));
        $validator->add('password_1', new CharPasswordValidator($password_1));

        $validator->add('password_2', new IsNotEmptyStringValidator($password_2));
        $validator->add('password_2', new CharPasswordValidator($password_2));

        if ($password_1 && $password_2) {
            $validator->add('password',
                new UserPasswordsCompareValidator([$password_1, $password_2])
            );
        }

        $validator->add('email', new IsNotEmptyValidator(
            $this->user->getEmail()->getValue()
        ));

        if ($this->user->getEmail()->getValue()) {
            $validator->add('email',
                (new UserMailExistsValidator($this->user))
                    ->setMapper($this->getMapper(UserMapper::class))
            );
        }

        if ($errors = $validator->validate()->getErrors()) {
            $this->getView()->getErrors()->setData($errors);

            $notification = $this->createNotification(Notification::TYPE_ALERT)
                ->setHeader($this->getView()->getLang()->get('notification.action_failed'))
                ->setMessage($this->getView()->getLang()->get('notification.message.post_errors'));
            $this->getView()->setNotification($notification);

            $this->getView()->getStorage()->offsetSet('password_1', $password_1);
            $this->getView()->getStorage()->offsetSet('password_2', $password_2);

            return null;
        } else {
            $this->user->setUniqueCookieId($this->getCurrentUser()->getUniqueCookieId());
            $this->user->setPasswordAsMd5($password_1);
            $this->user->setIp($_SERVER['REMOTE_ADDR']);
            $this->user->setRegdate(new DateTime());

            $this->user->save();

            $this->getMapper(InviteAnonymousUserMapper::class)->deleteByUniqueCookieId(
                $this->getCurrentUser()->getUniqueCookieId()
            );

            $this->getMapper(AdvertMapper::class)->attachGuestUserAdverts($this->user);

            // Убиваем кэш всех объявлений пользователя
            $userAdvertsList = $this->getMapper(AdvertMapper::class)->findModelListByUser($this->user);
            /** @var Advert $advert */
            foreach ($userAdvertsList as $advert) {
                $advert
                    ->setEditDateDiffToOneSecondMore()
                    ->save()
                    ->deleteCache();
            }

            Session::getInstance()->destroy();

            try {
                if ($this->user->getEmail()->getValue()) {
                    $mailQueue = new MailQueue();
                    $mailQueue
                        ->setSendDate(new DateTime())
                        ->setToEmail($this->user->getEmail()->getValue())
                        ->setFromEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                        ->setReplyEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                        ->setHeader($this->getView()->getLang()->get('mail.header.send_mail_user_header'))
                        ->setTemplate($this->getRealLocalTemplatePath('FrontendRegistrationSendData'))
                        ->setMailData([
                            'user' => $this->user,
                            'user_password' => $password_1,
                            'hostinfo' => Registry::getInstance()->get('HOSTINFO'),
                        ]);
                    $this->getMapper(MailQueueMapper::class)->saveModel($mailQueue);
                }
            } catch (Throwable $t) {
                ErrorLog::write($t->getMessage());
            }

            (new Authorization(
                $this->getRequest(),
                $this->getResponse(),
                $this->getMapper(UserMapper::class)
            ))
                ->processAuthorization(
                    $this->user->getLogin(),
                    $password_1,
                    Authorization::AUTHORIZATION_ON_YEAR
                );

            $message = $this->user->getEmail()->getValue()
                ? $this->getView()->getLang()->get('notification.message.you_registration_with_email')
                : $this->getView()->getLang()->get('notification.message.you_registration_without_email');

            return $this->createNotification()
                ->setHeader($this->getView()->getLang()->get('notification.header.you_registration_ok'))
                ->setMessage($message)
                ->addParam('login', $this->user->getLogin())
                ->addParam('password', $password_1)
                ->setRedirectUrl('/advert/frontend-edit-advert/?from_registration=1')
                ->run();
        }
    }
}