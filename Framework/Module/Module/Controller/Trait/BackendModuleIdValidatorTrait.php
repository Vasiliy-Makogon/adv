<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Module\Controller\Trait;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Module\Mapper\ModuleMapper;
use Krugozor\Framework\Module\Module\Model\Module;
use Krugozor\Framework\Notification;

trait BackendModuleIdValidatorTrait
{
    /** @var Module|null */
    protected ?Module $moduleModel = null;

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function checkIdOnValid(): ?Notification
    {
        if ($id = $this->getRequest()->getRequest('id', Request::SANITIZE_INT)) {
            $this->moduleModel = $this->getMapper(ModuleMapper::class)->findModelById($id);

            if (!$this->moduleModel->getId()) {
                return $this->createNotification(Notification::TYPE_ALERT)
                    ->setMessage($this->getView()->getLang()->get('notification.message.element_does_not_exist'))
                    ->setRedirectUrl($this->getRedirectUrl())
                    ->run();
            }
        }

        return null;
    }

    /**
     * @return string
     */
    protected function getRedirectUrl(): string
    {
        return '/module/backend-main/';
    }
}