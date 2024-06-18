<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Module\User\Mapper\CityMapper;
use Krugozor\Framework\Module\User\Model\City;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Statical\Numeric;

trait BackendCityIdValidatorTrait
{
    /**
     * @var City|null
     */
    protected ?City $city;

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function checkIdOnValid(): ?Notification
    {
        if ($id = $this->getRequest()->getRequest('id')) {
            if (!Numeric::isDecimal($id)) {
                $message = $this->getView()->getLang()->get('notification.message.bad_id_element');
                return $this->createNotification(Notification::TYPE_ALERT)
                    ->setMessage($message)
                    ->setRedirectUrl('/user/backend-city-list/')
                    ->run();
            }

            $this->city = $this->getMapper(CityMapper::class)->findModelById(
                $this->getRequest()->getRequest('id')
            );

            if (!$this->city->getId()) {
                $message = $this->getView()->getLang()->get('notification.message.element_does_not_exist');
                return $this->createNotification(Notification::TYPE_ALERT)
                    ->setMessage($message)
                    ->setRedirectUrl('/user/backend-city-list/')
                    ->run();
            }
        }

        return null;
    }
}