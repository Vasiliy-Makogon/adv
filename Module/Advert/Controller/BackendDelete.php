<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Controller\Trait\BackendIdValidatorTrait;
use Krugozor\Framework\Notification;

class BackendDelete extends AbstractController
{
    use BackendIdValidatorTrait;

    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return ['Common/BackendGeneral'];
    }

    /**
     * @return Notification
     * @throws MySqlException
     */
    public function run(): Notification
    {
        if (!$this->checkAccess()) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.forbidden_access'))
                ->setRedirectUrl('/advert/backend-main/')
                ->run();
        }

        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (!$this->getRequest()->getRequest('id', Request::SANITIZE_INT)) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.id_element_not_exists'))
                ->setRedirectUrl('/advert/backend-main/')
                ->run();
        }

        $this->advert->deleteCache()->delete();

        return $this->createNotification()
            ->setMessage($this->getView()->getLang()->get('notification.message.data_deleted'))
            ->setRedirectUrl(
                $this->getRequest()->getRequest('referer', Request::SANITIZE_STRING)
                    ?: '/advert/backend-main/'
            )->run();
    }
}