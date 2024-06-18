<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\Advert\Controller\Trait\FrontendIdValidatorTrait;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Type\Date\DateTime;

class FrontendActiveAdvert extends AbstractController
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

        $this->advert->invertActive();
        $this->advert->setEditDate(new DateTime());
        $this->advert->deleteCache()->save();

        $notification_message = $this->getView()->getLang()->get(sprintf(
            'notification.message.advert_active_%s',
            (string) $this->advert->getActive())
        );

        return $this->createNotification()
            ->addParam('advert_header', $this->advert->getHeader())
            ->setMessage($notification_message)
            ->setRedirectUrl($this->getRequest()->getRequest('referrer') ?: '/advert/frontend-user-adverts-list/')
            ->run();
    }
}