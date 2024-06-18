<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\User\Mapper\CountryMapper;
use Krugozor\Framework\Module\User\Mapper\RegionMapper;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Validator\Validator;
use Krugozor\Framework\View;

class BackendRegionEdit extends AbstractController
{
    use BackendRegionIdValidatorTrait;

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
                ->setRedirectUrl('/user/backend-region-list/')
                ->run();
        }

        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (empty($this->region)) {
            $this->region = $this->getMapper(RegionMapper::class)->createModel();
        }

        $this->getView()->err = [];
        if (Request::isPost() && ($result = $this->post())) {
            return $result;
        }

        $this->getView()->region = $this->region;
        $this->getView()->countryList = $this->getMapper(CountryMapper::class)->getListActiveCountry();

        return $this->getView();
    }

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function post(): ?Notification
    {
        if (!$this->getRequest()->getPost('region')->name_en) {
            $this->getRequest()->getPost('region')->name_en = $this->getRequest()->getPost('region')->name_ru;
        }

        $this->region->setData($this->getRequest()->getPost('region')->getDataAsArray());

        $validator = new Validator('common/general');
        $validator->addErrors($this->region->getValidateErrors());
        $validator->validate();

        $notification = $this->createNotification();

        if ($this->getView()->err = $validator->getErrors()) {
            $message = $this->getView()->getLang()->get('notification.message.post_errors');
            $notification
                ->setType(Notification::TYPE_ALERT)
                ->setMessage($message);
            $this->getView()->setNotification($notification);

            return null;
        } else {
            $this->getMapper(RegionMapper::class)->saveModel($this->region);

            $message = $this->getView()->getLang()->get('notification.message.data_saved');
            $url = $this->getRequest()->getRequest('return_on_page', Request::SANITIZE_INT)
                ? '/user/backend-region-edit/?id=' . $this->region->getId()
                : ($this->getRequest()->getRequest('referer', Request::SANITIZE_STRING) ?: '/user/backend-region-list/');

            return $notification->setMessage($message)
                ->setRedirectUrl($url)
                ->run();
        }
    }
}