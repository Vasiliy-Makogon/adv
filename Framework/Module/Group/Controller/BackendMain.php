<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Group\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\Group\Mapper\GroupMapper;
use Krugozor\Framework\Module\Group\Service\GroupListService;
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
            'Group/BackendCommon',
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
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.forbidden_access'))
                ->setRedirectUrl('/admin/')
                ->run();
        }

        $groupListService = (new GroupListService(
            $this->getRequest(),
            $this->getMapper(GroupMapper::class),
            Adapter::getManager($this->getRequest(), 15)
        ))->findList();

        $this->getView()->getStorage()->offsetSet('groupListService', $groupListService);
        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }
}