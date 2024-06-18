<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Module\User\Mapper\RegionMapper;
use Krugozor\Framework\Module\User\Model\Region;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Statical\Numeric;

trait BackendRegionIdValidatorTrait
{
    /**
     * @var Region|null
     */
    protected ?Region $region;

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
                    ->setRedirectUrl('/user/backend-region-list/')
                    ->run();
            }

            $this->region = $this->getMapper(RegionMapper::class)->findModelById(
                $this->getRequest()->getRequest('id')
            );

            if (!$this->region->getId()) {
                $message = $this->getView()->getLang()->get('notification.message.element_does_not_exist');
                return $this->createNotification(Notification::TYPE_ALERT)
                    ->setMessage($message)
                    ->setRedirectUrl('/user/backend-region-list/')
                    ->run();
            }
        }

        return null;
    }
}