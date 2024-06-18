<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Module\Controller\Trait;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Module\Mapper\ControllerMapper;
use Krugozor\Framework\Module\Module\Model\Controller;
use Krugozor\Framework\Notification;

trait BackendControllerIdValidatorTrait
{
    /** @var Controller|null */
    protected ?Controller $controllerModel = null;

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function checkIdOnValid(): ?Notification
    {
        if ($id = $this->getRequest()->getRequest('id', Request::SANITIZE_INT)) {
            $this->controllerModel = $this->getMapper(ControllerMapper::class)->findModelById($id);

            if (!$this->controllerModel->getId()) {
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
        return
            $this->getRequest()->getRequest('return_on_page', Request::SANITIZE_INT)
            ?: sprintf(
                '/module/edit-module/?id=%s',
                $this->getRequest()->getRequest('id_module', Request::SANITIZE_INT)
            );
    }
}