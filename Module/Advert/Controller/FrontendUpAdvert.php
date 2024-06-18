<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\Advert\Controller\Trait\FrontendIdValidatorTrait;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Notification;

class FrontendUpAdvert extends AbstractController
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

        $notification = $this->createNotification();
        $notification->addParam('advert_header', $this->advert->getHeader());

        if ($this->getMapper(AdvertMapper::class)->updateDateCreate($this->advert)) {
            $notification_message = $this->getView()->getLang()->get('notification.message.advert_date_create_update');
        } else {
            $notification_message = $this->getView()->getLang()->get('notification.message.advert_date_create_not_update');
            $notification->setType(Notification::TYPE_WARNING);
            $notification->addParam('date', $this->advert->getExpireRestrictionUpdateCreateDate()->format('%H:%I'));
        }
        $notification->setMessage($notification_message);
        $notification->setRedirectUrl($this->getRequest()->getRequest('referrer') ?: '/advert/frontend-user-adverts-list/');

        return $notification->run();
    }
}