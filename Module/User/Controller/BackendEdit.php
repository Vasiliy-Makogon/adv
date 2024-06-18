<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Cover\Data\PostData;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\Group\Mapper\GroupMapper;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Validator\UserLoginExistsValidator;
use Krugozor\Framework\Module\User\Validator\UserMailExistsValidator;
use Krugozor\Framework\Module\User\Validator\UserPasswordsCompareValidator;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Validator\CharPasswordValidator;
use Krugozor\Framework\Validator\IsNotEmptyStringValidator;
use Krugozor\Framework\Validator\Validator;
use Krugozor\Framework\View;

class BackendEdit extends AbstractController
{
    use BackendUserIdValidatorTrait;

    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/BackendGeneral',
            'User/BackendCommon'
        ];
    }

    /**
     * @return Notification|View
     * @throws MySqlException
     */
    public function run(): Notification|View
    {
        if (!$this->checkAccess()) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.forbidden_access'))
                ->setRedirectUrl('/user/backend-main/')
                ->run();
        }

        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (!$this->user) {
            $this->user = $this->getMapper(UserMapper::class)->createModel();
        }

        if (Request::isPost() && $notification = $this->post()) {
            return $notification;
        }

        $this->getView()->getStorage()->offsetSet('user', $this->user);
        $this->getView()->getStorage()->offsetSet(
            'groupsList',
            $this->getMapper(GroupMapper::class)->findAllGroupsWithoutGuest()
        );

        return $this->getView();
    }

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function post(): ?Notification
    {
        /** @var PostData $postUserData */
        $postUserData = $this->getRequest()->getPost('user', PostData::class);
        $this->user->setData($postUserData, [
            'id', 'unique_cookie_id', 'salt', 'regdate', 'visitdate',
        ]);

        $validator = new Validator('common/general', 'user/registration');
        $validator->addErrors($this->user->getValidateErrors());

        if ($this->user->getLogin()) {
            $validator->add(
                'login',
                (new UserLoginExistsValidator($this->user))
                    ->setMapper($this->getMapper(UserMapper::class))
            );
        }

        $password_1 = $this->getRequest()->getPost('user.password_1', Request::SANITIZE_STRING);
        $password_2 = $this->getRequest()->getPost('user.password_2', Request::SANITIZE_STRING);

        if (!$this->user->getId()) {
            $validator->add('password_1', new IsNotEmptyStringValidator($password_1));
            $validator->add('password_1', new CharPasswordValidator($password_1));

            $validator->add('password_2', new IsNotEmptyStringValidator($password_2));
            $validator->add('password_2', new CharPasswordValidator($password_2));
        }

        if ($password_1 && $password_2) {
            $validator->add(
                'password',
                new UserPasswordsCompareValidator([$password_1, $password_2])
            );
        }

        if ($this->user->getEmail()->getValue()) {
            $validator->add(
                'email',
                (new UserMailExistsValidator($this->user))
                    ->setMapper($this->getMapper(UserMapper::class))
            );
        }

        if ($errors = $validator->validate()->getErrors()) {
            $this->getView()->getErrors()->setData($errors);

            $notification = $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.post_errors'));
            $this->getView()->setNotification($notification);

            $this->getView()->getStorage()->offsetSet('password_1', $password_1);
            $this->getView()->getStorage()->offsetSet('password_2', $password_2);

            return null;
        } else {
            // переписать это
            if ($password_1 && $password_2) {
                $this->user->setPasswordAsMd5($password_1);
            }

            $userDifference = $this->user->getId() && $this->user->getTrack()->getDifference();

            // Если пользователь будет заблокирован - триггер на таблице объявлений
            // проставит активность всех объявлений пользователя = 0
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

            $url = $this->getRequest()->getRequest('return_on_page', Request::SANITIZE_INT)
                ? '/user/backend-edit/?id=' . $this->user->getId()
                : ($this->getRequest()->getRequest('referer', Request::SANITIZE_STRING) ?: '/user/backend-main/');

            return $this->createNotification()
                ->setMessage($this->getView()->getLang()->get('notification.message.data_saved'))
                ->setRedirectUrl($url)
                ->run();
        }
    }
}