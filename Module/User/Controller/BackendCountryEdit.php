<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\User\Mapper\CountryMapper;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Validator\Validator;
use Krugozor\Framework\View;

class BackendCountryEdit extends AbstractController
{
    use BackendCountryIdValidatorTrait;

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
    public function run()
    {
        if (!$this->checkAccess()) {
            $message = $this->getView()->getLang()->get('notification.message.forbidden_access');
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($message)
                ->setRedirectUrl('/user/backend-country-list/')
                ->run();
        }

        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (empty($this->country)) {
            $this->country = $this->getMapper(CountryMapper::class)->createModel();
        }

        $this->getView()->err = [];
        if (Request::isPost() && ($result = $this->post())) {
            return $result;
        }

        $this->getView()->country = $this->country;

        return $this->getView();
    }

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function post(): ?Notification
    {
        if (!$this->getRequest()->getPost('country')->name_en) {
            $this->getRequest()->getPost('country')->name_en = $this->getRequest()->getPost('country')->name_ru;
        }

        $this->country->setData($this->getRequest()->getPost('country')->getDataAsArray());

        $validator = new Validator('common/general');
        $validator->addErrors($this->country->getValidateErrors());
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
            $this->getMapper(CountryMapper::class)->saveModel($this->country);

            $message = $this->getView()->getLang()->get('notification.message.data_saved');
            $url = $this->getRequest()->getRequest('return_on_page', Request::SANITIZE_INT)
                ? '/user/backend-country-edit/?id=' . $this->country->getId()
                : ($this->getRequest()->getRequest('referer', Request::SANITIZE_STRING) ?: '/user/backend-country-list/');

            return $notification->setMessage($message)
                ->setRedirectUrl($url)
                ->run();
        }
    }
}