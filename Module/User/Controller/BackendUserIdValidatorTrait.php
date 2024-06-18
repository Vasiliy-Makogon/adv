<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Notification;

trait BackendUserIdValidatorTrait
{
    /**
     * @var User|null
     */
    protected ?User $user = null;

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function checkIdOnValid(): ?Notification
    {
        if ($id = $this->getRequest()->getRequest('id', Request::SANITIZE_INT)) {
            $this->user = $this->getMapper(UserMapper::class)->findModelById($id);

            if (!$this->user->getId()) {
                return $this->createNotification(Notification::TYPE_ALERT)
                    ->setMessage($this->getView()->getLang()->get('notification.message.user_does_not_exist'))
                    ->addParam('id_user', $this->getRequest()->getRequest('id'))
                    ->setRedirectUrl('/user/backend-main/')
                    ->run();
            }
        }

        return null;
    }
}