<?php

namespace Krugozor\Framework\Module\Prodamus\Controller;

use Exception;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\PaymentActionsEnum;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Statical\ErrorLog;
use Throwable;

/**
 * Информирование об успешной оплате.
 */
class Success extends AbstractController
{
    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/FrontendGeneral',
            'Local/FrontendGeneral',
            $this->getRequest()->getVirtualControllerPath()
        ];
    }

    public function run()
    {
        try {
            $advert_id = $this->getRequest()->getGet('advert', Request::SANITIZE_INT);
            if (!$advert_id) {
                throw new Exception($this->getView()->getLang()->get('notification.message.not_found_advert_id'));
            }

            $action = $this->getRequest()->getGet('action', Request::SANITIZE_INT);
            $message = match ((int) $action) {
                PaymentActionsEnum::ACTION_ACTIVATE => $this->getView()->getLang()->get('notification.message.advert_set_payment'),
                PaymentActionsEnum::ACTION_TOP, PaymentActionsEnum::ACTION_SPECIAL => $this->getView()->getLang()->get('notification.message.advert_set_vip'),
                default => throw new Exception($this->getView()->getLang()->get('notification.message.undefined_action')),
            };

            $advert = $this->getMapper(AdvertMapper::class)->findModelById($advert_id);
            if (!$advert->getId()) {
                throw new Exception($this->getView()->getLang()->get('notification.message.not_found_advert'));
            }

            $notification = $this->createNotification()
                ->setMessage($message)
                ->addParam('id', $advert->getId())
                ->addParam('advert_header', $advert->getHeader())
                ->addParam('http_host', Registry::getInstance()->get('HOSTINFO.HOST_AS_TEXT'));
            $this->getView()->setNotification($notification);
        } catch (Throwable $t) {
            $notification = $this->createNotification(Notification::TYPE_ALERT)
                ->setHeader($this->getView()->getLang()->get('notification.header.fail'))
                ->setMessage($t->getMessage())
                ->addParam('id', $advert_id);
            $this->getView()->setNotification($notification);

            ErrorLog::write($t->getMessage());
        }

        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }
}