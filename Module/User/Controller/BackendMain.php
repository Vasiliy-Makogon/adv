<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Service\UserListService;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Pagination\Adapter;
use Krugozor\Framework\View;

class BackendMain extends AbstractController
{
    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/BackendGeneral',
            'User/BackendCommon',
            $this->getRequest()->getVirtualControllerPath()
        ];
    }

    /**
     * @return Notification|View
     * @throws MySqlException
     */
    public function run(): Notification|View
    {
        if (!$this->checkAccess()) {
            $message = $this->getView()->getLang()->get('notification.message.forbidden_access');
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($message)
                ->setRedirectUrl('/admin/')
                ->run();
        }

        $userListService = (new UserListService(
            $this->getRequest(),
            $this->getMapper(UserMapper::class),
            Adapter::getManager($this->getRequest(), 20)
        ))->findList();

        $this->getView()->getStorage()->offsetSet(
            'userListService',
            $userListService
        );

        return $this->getView();
    }
}