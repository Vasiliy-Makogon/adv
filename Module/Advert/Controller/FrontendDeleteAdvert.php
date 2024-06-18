<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\Advert\Controller\Trait\FrontendIdValidatorTrait;
use Krugozor\Framework\Notification;

class FrontendDeleteAdvert extends AbstractController
{
    use FrontendIdValidatorTrait;

    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/FrontendGeneral',
            'Local/FrontendGeneral',
            'Advert/FrontendCommon'
        ];
    }

    /**
     * @return Notification
     * @throws MySqlException
     */
    public function run(): Notification
    {
        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (!$this->checkAccess() || $this->getCurrentUser()->getId() !== $this->advert->getIdUser()) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.forbidden_access'))
                ->setRedirectUrl($this->getRequest()->getRequest('referrer') ?: '/authorization/frontend-login/')
                ->run();
        }

        $this->advert->deleteCache()->delete();

        return $this->createNotification()
            ->setMessage($this->getView()->getLang()->get('notification.message.advert_delete'))
            ->addParam('advert_header', $this->advert->getHeader())
            ->setRedirectUrl($this->getRequest()->getRequest('referrer') ?: '/advert/frontend-user-adverts-list/')
            ->run();
    }
}