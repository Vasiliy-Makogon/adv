<?php

namespace Krugozor\Framework\Module\Authorization\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Authorization;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Cover\Data\PostData;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Validator\Validator;
use Krugozor\Framework\Validator\IsNotEmptyStringValidator;
use Krugozor\Framework\View;

/**
 * Авторизация в административной части.
 */
class BackendLogin extends AbstractController
{
    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/BackendGeneral',
            $this->getRequest()->getVirtualControllerPath()
        ];
    }

    /**
     * @return Notification|View
     * @throws MySqlException
     */
    public function run(): Notification|View
    {
        /** @var User $userModel */
        $userModel = $this->getMapper(UserMapper::class)->createModel();

        if (Request::isPost()) {
            $userModel->setData($this->getRequest()->getRequest('user', PostData::class));

            $validator = new Validator('common/general', 'authorization/login');
            $validator->addErrors($userModel->getValidateErrors())
                ->add('password', new IsNotEmptyStringValidator($userModel->getPassword()))
                ->validate();

            $autologin = $this->getRequest()->getPost('autologin', Request::SANITIZE_INT);
            $ml_autologin = $this->getRequest()->getPost('ml_autologin', Request::SANITIZE_INT);

            if (!$errors = $validator->getErrors()) {
                $authorization = new Authorization(
                    $this->getRequest(),
                    $this->getResponse(),
                    $this->getMapper(UserMapper::class)
                );
                if ($authorization->processAuthorization(
                    $userModel->getLogin(),
                    $userModel->getPassword(),
                    $autologin ? $ml_autologin : 0
                )) {
                    $referer = $this->getRequest()->getRequest('referer', Request::SANITIZE_STRING) ?: '/admin/';

                    return $this->createNotification()
                        ->setMessage($this->getView()->getLang()->get('notification.message.inside_system'))
                        ->setRedirectUrl($referer)
                        ->run();
                } else {
                    $validator->addError('authorization', 'INCORRECT_AUTH_DATA');
                    $this->getView()->getErrors()->setData($validator->getErrors());

                    $notification = $this->createNotification(Notification::TYPE_ALERT)
                        ->setHeader($this->getView()->getLang()->get('notification.header.action_failed'))
                        ->setMessage($this->getView()->getLang()->get('notification.message.post_errors'));
                    $this->getView()->setNotification($notification);
                }
            } else {
                $this->getView()->getErrors()->setData($errors);
            }
        } else {
            $autologin = 0;
            $ml_autologin = Authorization::AUTHORIZATION_ON_YEAR;
        }

        $this->getView()->getStorage()->offsetSet('autologin', $autologin);
        $this->getView()->getStorage()->offsetSet('ml_autologin', $ml_autologin);
        $this->getView()->setCurrentUser($this->getCurrentUser());
        $this->getView()->getStorage()->offsetSet('userModel', $userModel);

        return $this->getView();
    }
}