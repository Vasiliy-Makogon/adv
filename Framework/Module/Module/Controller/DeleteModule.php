<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Module\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Module\Controller\Trait\BackendModuleIdValidatorTrait;
use Krugozor\Framework\Module\Module\Mapper\ModuleMapper;
use Krugozor\Framework\Notification;

class DeleteModule extends AbstractController
{
    use BackendModuleIdValidatorTrait;

    /**
     * @return Notification
     * @throws MySqlException
     */
    public function run(): Notification
    {
        $this->getView()->getLang()->loadI18n('Common/BackendGeneral');

        if (!$this->checkAccess()) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.forbidden_access'))
                ->setRedirectUrl($this->getRedirectUrl())
                ->run();
        }

        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (!$this->getRequest()->getRequest('id', Request::SANITIZE_INT)) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.id_element_not_exists'))
                ->setRedirectUrl($this->getRedirectUrl())
                ->run();
        }

        $this->getMapper(ModuleMapper::class)->deleteModel($this->moduleModel);

        return $this->createNotification()
            ->setMessage($this->getView()->getLang()->get('notification.message.data_deleted'))
            ->setRedirectUrl(
                $this->getRequest()->getRequest('referer', Request::SANITIZE_STRING)
                ?: $this->getRedirectUrl()
            )
            ->run();
    }
}