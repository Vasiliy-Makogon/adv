<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Group\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Group\Controller\Trait\BackendIdValidatorTrait;
use Krugozor\Framework\Notification;

class BackendDelete extends AbstractController
{
    use BackendIdValidatorTrait;

    /**
     * @return Notification
     * @throws MySqlException
     */
    public function run(): Notification
    {
        $this->getView()->getLang()->loadI18n(
            'Common/BackendGeneral',
            'Group/BackendCommon'
        );

        if (!$this->checkAccess()) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.forbidden_access'))
                ->setRedirectUrl('/group/backend-main/')
                ->run();
        }

        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (!$this->getRequest()->getRequest('id', Request::SANITIZE_INT)) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.id_element_not_exists'))
                ->setRedirectUrl('/group/backend-main/')
                ->run();
        }

        $this->groupModel->delete();

        return $this->createNotification()
            ->setMessage($this->getView()->getLang()->get('notification.message.data_deleted'))
            ->addParam('group_name', $this->groupModel->getName())
            ->setRedirectUrl($this->getRequest()->getRequest('referer', Request::SANITIZE_STRING))
            ->run();
    }
}