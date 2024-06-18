<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Module\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\Module\Mapper\ModuleMapper;
use Krugozor\Framework\Module\Module\Service\ModuleListService;
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

        $moduleListService = (new ModuleListService(
            $this->getRequest(),
            $this->getMapper(ModuleMapper::class),
            Adapter::getManager($this->getRequest(), 15)
        ))->findList();

        $this->getView()->getStorage()->offsetSet('moduleListService', $moduleListService);

        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }
}