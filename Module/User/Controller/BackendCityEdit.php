<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\User\Mapper\CityMapper;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Validator\Validator;
use Krugozor\Framework\View;

class BackendCityEdit extends AbstractController
{
    use BackendCityIdValidatorTrait;

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
                ->setRedirectUrl('/user/backend-city-list/')
                ->run();
        }

        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (empty($this->city)) {
            $this->city = $this->getMapper(CityMapper::class)->createModel();
        }

        $this->getView()->err = [];
        if (Request::isPost() && ($result = $this->post())) {
            return $result;
        }

        $this->getView()->city = $this->city;

        return $this->getView();
    }

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function post(): ?Notification
    {
        if (!$this->getRequest()->getPost('city')->name_en) {
            $this->getRequest()->getPost('city')->name_en = $this->getRequest()->getPost('city')->name_ru;
        }

        $this->city->setData($this->getRequest()->getPost('city')->getDataAsArray());

        $validator = new Validator('common/general');
        $validator->addErrors($this->city->getValidateErrors());
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
            $this->getMapper(CityMapper::class)->saveModel($this->city);

            $message = $this->getView()->getLang()->get('notification.message.data_saved');
            $url = $this->getRequest()->getRequest('return_on_page', Request::SANITIZE_INT)
                ? '/user/backend-city-edit/?id=' . $this->city->getId()
                : ($this->getRequest()->getRequest('referer', Request::SANITIZE_STRING) ?: '/user/backend-city-list/');

            return $notification->setMessage($message)
                ->setRedirectUrl($url)
                ->run();
        }
    }
}