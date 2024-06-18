<?php

namespace Krugozor\Framework\Module\Authorization\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Authorization;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Notification;

/**
 * Выход из системы.
 */
class Logout extends AbstractController
{
    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/BackendGeneral'
        ];
    }

    /**
     * @return Notification
     * @throws MySqlException
     */
    public function run(): Notification
    {
        if (!$this->getCurrentUser()->isGuest()) {
            $auth = new Authorization(
                $this->getRequest(),
                $this->getResponse(),
                $this->getMapper(UserMapper::class)
            );
            $auth->logout();
        }

        $referer = $this->getRequest()->getRequest('referer', Request::SANITIZE_STRING) ?: '/';
        return $this->createNotification()
            ->setMessage($this->getView()->getLang()->get('notification.message.outside_system'))
            ->setRedirectUrl($referer)
            ->run();
    }
}