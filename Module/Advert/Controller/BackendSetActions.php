<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Notification;

/**
 * Операции над множеством объявлений на основе их идентификаторов.
 */
class BackendSetActions extends AbstractController
{
    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return ['Common/BackendGeneral'];
    }

    /**
     * @return Notification
     * @throws MySqlException
     */
    public function run(): Notification
    {
        $redirectUrl = $this->getRequest()->getRequest('referer', Request::SANITIZE_STRING);

        if (!$this->checkAccess()) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.forbidden_access'))
                ->setRedirectUrl('/advert/backend-main/')
                ->run();
        }

        if (!$ids = $this->getRequest()->getRequest('ids', Request::SANITIZE_ARRAY)) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.ids_elements_not_exists'))
                ->setRedirectUrl($redirectUrl)
                ->run();
        }

        $adverts = $this->getMapper(AdvertMapper::class)->findModelListByIds($ids);

        if (!$adverts->count()) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.elements_does_not_exists'))
                ->setRedirectUrl($redirectUrl)
                ->run();
        }

        // Удаление
        if ($this->getRequest()->getRequest('delete', Request::SANITIZE_STRING)) {
            /** @var Advert $advert */
            foreach ($adverts as $advert) {
                if ($advert->getVipDate() !== null || $advert->getSpecialDate() !== null) {
                    continue;
                }

                $advert
                    ->deleteCache()
                    ->delete();
            }

            return $this->createNotification()
                ->setMessage($this->getView()->getLang()->get('notification.message.data_deleted'))
                ->setRedirectUrl($redirectUrl)
                ->run();
        // Оплачено
        } elseif ($this->getRequest()->getRequest('payment', Request::SANITIZE_STRING)) {
            /** @var Advert $advert */
            foreach ($adverts as $advert) {
                $advert->setPayment(1);
                $advert->save();
            }

            return $this->createNotification()
                ->setMessage($this->getView()->getLang()->get('notification.message.data_saved'))
                ->setRedirectUrl($redirectUrl)
                ->run();
        // Промодерировано
        } elseif ($this->getRequest()->getRequest('was_moderated', Request::SANITIZE_STRING)) {
            /** @var Advert $advert */
            foreach ($adverts as $advert) {
                $advert->setWasModerated(1);
                $advert->save();
            }

            return $this->createNotification()
                ->setMessage($this->getView()->getLang()->get('notification.message.data_saved'))
                ->setRedirectUrl($redirectUrl)
                ->run();
        // Изменение категории
        } elseif (
            $this->getRequest()->getPost('change_advert_category', Request::SANITIZE_STRING) &&
            $id_category = $this->getRequest()->getRequest('category', Request::SANITIZE_INT)
        ) {
            $category = $this->getMapper(CategoryMapper::class)->findModelById($id_category);

            if (!$category->getId()) {
                return $this->createNotification(Notification::TYPE_ALERT)
                    ->setMessage($this->getView()->getLang()->get('notification.message.category_does_not_exists'))
                    ->setRedirectUrl($redirectUrl)
                    ->run();
            }

            /** @var Advert $advert */
            foreach ($adverts as $advert) {
                $advert->setCategory($category->getId());
                $advert
                    ->setEditDateDiffToOneSecondMore()
                    ->save()
                    ->deleteCache();
            }

            return $this->createNotification()
                ->setMessage($this->getView()->getLang()->get('notification.message.data_saved'))
                ->setRedirectUrl($redirectUrl)
                ->run();
        // Ошибочный post-запрос
        } else {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.unknown_error'))
                ->setRedirectUrl($redirectUrl)
                ->run();
        }
    }
}