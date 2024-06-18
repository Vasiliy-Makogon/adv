<?php

namespace Krugozor\Framework\Module\Advert\Controller\Trait;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Notification;

trait BackendIdValidatorTrait
{
    /** @var Advert|null */
    protected ?Advert $advert = null;

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function checkIdOnValid(): ?Notification
    {
        if ($id = $this->getRequest()->getRequest('id', Request::SANITIZE_INT)) {
            $this->advert = $this->getMapper(AdvertMapper::class)->findModelById($id);

            if (!$this->advert->getId()) {
                return $this->createNotification(Notification::TYPE_ALERT)
                    ->setMessage($this->getView()->getLang()->get('notification.message.element_does_not_exist'))
                    ->setRedirectUrl('/advert/backend-main/')
                    ->run();
            }
        }

        return null;
    }
}