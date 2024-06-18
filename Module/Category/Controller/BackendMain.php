<?php

namespace Krugozor\Framework\Module\Category\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Notification;
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

        $this->getView()->getStorage()->offsetSet(
            'categories',
            $this->getMapper(CategoryMapper::class)->loadTree()
        );

        return $this->getView();
    }
}