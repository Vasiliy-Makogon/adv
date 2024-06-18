<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Authorization;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Cover\Data\PostData;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Module\User\Validator\UserLoginExistsValidator;
use Krugozor\Framework\Module\User\Validator\UserMailExistsValidator;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Validator\Validator;
use Krugozor\Framework\Validator\CharPasswordValidator;
use Krugozor\Framework\View;

class FrontendEdit extends AbstractController
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
        if ($this->getCurrentUser()->isGuest()) {
            return $this->createNotification()
                ->setIsHidden(true)
                ->setRedirectUrl(
                    '/authorization/frontend-login/?referer=' . urlencode(
                        $this->getRequest()->getRequestUri()->getEscapeUriValue()
                    )
                )
                ->run();
        } else if (!$this->checkAccess()) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.forbidden_access'))
                ->setRedirectUrl('/authorization/frontend-login/')
                ->run();
        }

        // clone нельзя использовать, т.к. track будет смотреть на исходный объект getCurrentUser()
        $this->user = $this->getMapper(UserMapper::class)->createModel();
        $this->user->setData($this->getCurrentUser()->getData());

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
    protected function post(): ?Notification
    {
        $postUserData = $this->getRequest()->getPost('user', PostData::class);
        $this->user->setData($postUserData, [
            'id', 'unique_cookie_id', 'salt', 'active', 'group', 'regdate', 'visitdate', 'ip',
        ]);

        $validator = new Validator('common/general', 'user/registration', 'user/edit');
        $validator->addErrors($this->user->getValidateErrors());

        if ($this->user->getLogin()) {
            $validator->add('login',
                (new UserLoginExistsValidator($this->user))
                    ->setMapper($this->getMapper(UserMapper::class))
            );
        }

        $password_1 = $postUserData->get('password_1');

        if ($this->user->getLogin() !== $this->getCurrentUser()->getLogin()) {
            if (!$password_1) {
                $validator->addError('password_1', 'CHANGE_LOGIN_NEED_PASSWORD');
            } else if (!$this->user->isPasswordsEqual($password_1)) {
                $validator->addError('password_1', 'CHANGE_LOGIN_WRONG_PASSWORD');
            }
        }

        if ($password_1) {
            $validator->add('password_1', new CharPasswordValidator($password_1));
        }

        if ($this->user->getEmail() && $this->user->getEmail()->getValue()) {
            $validator->add('email',
                (new UserMailExistsValidator($this->user))
                    ->setMapper($this->getMapper(UserMapper::class))
            );
        }

        if ($errors = $validator->validate()->getErrors()) {
            $this->getView()->getErrors()->setData($errors);

            $notification = $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.post_errors'));
            $this->getView()->setNotification($notification);

            return null;
        } else {
            // Если требуется изменить пароль, явно указываем его для объекта.
            if ($password_1) {
                $this->user->setPasswordAsMd5($password_1);
            }

            $userDifference = $this->user->getTrack()->getDifference([
                'login', 'password', 'sex', 'age',
            ]);

            $this->user->save();

            // Данные пользователя были изменены
            if ($userDifference) {
                // Убиваем кэш всех объявлений пользователя
                $userAdvertsList = $this->getMapper(AdvertMapper::class)->findModelListByUser($this->user);
                /** @var Advert $advert */
                foreach ($userAdvertsList as $advert) {
                    $advert
                        ->setEditDateDiffToOneSecondMore()
                        ->save()
                        ->deleteCache();
                }
            }

            // Если поменяли пароль, то нужно сделать скрытую авторизацию.
            if ($password_1) {
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
            }

            return $this->createNotification()
                ->setMessage($this->getView()->getLang()->get('notification.message.data_saved'))
                ->setRedirectUrl($this->getRequest()->getCanonicalRequestUri()->getSimpleUriValue())
                ->run();
        }
    }
}