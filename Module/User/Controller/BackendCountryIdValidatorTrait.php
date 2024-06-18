<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Module\User\Mapper\CountryMapper;
use Krugozor\Framework\Module\User\Model\Country;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Statical\Numeric;

trait BackendCountryIdValidatorTrait
{
    /**
     * @var Country|null
     */
    protected ?Country $country;

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
                    ->setRedirectUrl('/user/backend-country-list/')
                    ->run();
            }

            $this->country = $this->getMapper(CountryMapper::class)->findModelById(
                $this->getRequest()->getRequest('id')
            );

            if (!$this->country->getId()) {
                $message = $this->getView()->getLang()->get('notification.message.element_does_not_exist');
                return $this->createNotification(Notification::TYPE_ALERT)
                    ->setMessage($message)
                    ->setRedirectUrl('/user/backend-country-list/')
                    ->run();
            }
        }

        return null;
    }
}