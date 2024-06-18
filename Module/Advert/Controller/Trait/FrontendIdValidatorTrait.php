<?php

namespace Krugozor\Framework\Module\Advert\Controller\Trait;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Validator\Validator;

trait FrontendIdValidatorTrait
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
                    ->setMessage($this->getView()->getLang()->get('notification.message.advert_does_not_exist'))
                    ->setRedirectUrl('/advert/frontend-user-adverts-list/')
                    ->run();
            }

            // Если ранее поданные объявления были записаны с ошибками, которые не пропускают
            // новые валидаторы, сообщаем об этом на этапе показа формы.
            if ($this->advert->getValidateErrors()) {
                $validator = new Validator('common/general', 'advert/edit');
                $errors = $validator
                    ->addErrors($this->advert->getValidateErrors())
                    ->getErrors();
                $this->getView()->getErrors()->setData($errors);
            }
        }

        return null;
    }
}