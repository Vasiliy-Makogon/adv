<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\User\Mapper\CountryMapper;
use Krugozor\Framework\Module\User\Service\CountryListService;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Pagination\Adapter;
use Krugozor\Framework\View;

class BackendCountryList extends AbstractController
{
    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/BackendGeneral',
            $this->getRequest()->getVirtualControllerPath()
        ];
    }

    /**
     * @return Notification|View
     * @throws MySqlException
     */
    public function run(): Notification|View
    {
        if (!$this->checkAccess()) {
            $message = $this->getView()->getLang()->get('notification.message.forbidden_access');
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($message)
                ->setRedirectUrl('/admin/')
                ->run();
        }

        $list = new CountryListService(
            $this->getRequest(),
            $this->getMapper(CountryMapper::class),
            Adapter::getManager($this->getRequest(), 20)
        );

        $this->getView()->countryList = $list->findList();

        return $this->getView();
    }
}