<?php

namespace Krugozor\Framework\Module\Prodamus\Controller;

use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\Advert\PaymentActionsEnum;
use Krugozor\Framework\Module\Prodamus\Service\Prodamus;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Statical\ErrorLog;
use Krugozor\Framework\Statical\Strings;
use RuntimeException;
use Throwable;

/**
 * Предоставление услуги по факту оплаты.
 */
class Result extends AbstractController
{
    public function run()
    {
        $this->getView()->getLang()->loadI18n(
            'Prodamus/Success'
        );

        try {
            $postData = $this->getRequest()->getPost()->getDataAsArray();
            $secretKey = Registry::getInstance()->get('PRODAMUS.SECRET_KEY');
            $sign = apache_request_headers()['Sign'] ?? null;

            $action = $this->getRequest()->getPost('_param_action', Request::SANITIZE_INT);
            $advert_id = $this->getRequest()->getPost('_param_advert_id', Request::SANITIZE_INT);

            $request_as_string = json_encode($postData);

            if (!Prodamus::verify($postData, $secretKey, $sign)) {
                $message = Strings::createMessageFromParams(
                    $this->getView()->getLang()->get('content.bad_signature'),
                    ['request_data' => $request_as_string],
                    false
                );
                throw new RuntimeException($message);
            }

            if (!$advert_id) {
                $message = Strings::createMessageFromParams(
                    $this->getView()->getLang()->get('content.not_found_advert_id'),
                    ['request_data' => $request_as_string],
                    false
                );
                throw new RuntimeException($message);
            }

            /* @var $advert Advert */
            $advert = $this->getMapper(AdvertMapper::class)->findModelById($advert_id);

            if (!$advert->getId()) {
                $message = Strings::createMessageFromParams(
                    $this->getView()->getLang()->get('content.not_found_advert'),
                    ['id' => $advert_id, 'request_data' => $request_as_string],
                    false
                );
                throw new RuntimeException($message);
            }

            switch ($action) {
                case PaymentActionsEnum::ACTION_ACTIVATE:
                    $advert->setPayment(1);
                    $this->getView()->result = "OK";
                    break;

                case PaymentActionsEnum::ACTION_TOP:
                    $advert->setVipStatus();
                    $this->getView()->result = "OK";
                    break;

                case PaymentActionsEnum::ACTION_SPECIAL:
                    $advert->setSpecialStatus();
                    $this->getView()->result = "OK";
                    break;

                default:
                    $message = Strings::createMessageFromParams(
                        $this->getView()->getLang()->get('content.undefined_action'),
                        ['action' => $action, 'request_data' => $request_as_string],
                        false
                    );
                    throw new RuntimeException($message);
            }

            $advert->deleteCache()->save();

            @file_put_contents(
                Registry::getInstance()->get('PATH.MERCHANT_LOG'),
                "SUCCESS: $request_as_string\n\n",
                FILE_APPEND
            );

        } catch (Throwable $t) {
            $this->getResponse()->setHttpStatusCode(400);
            ErrorLog::write($t->getMessage());
            @file_put_contents(
                Registry::getInstance()->get('PATH.MERCHANT_LOG'),
                "ERROR: $request_as_string\n\n",
                FILE_APPEND
            );
            $this->getView()->result = $t->getMessage();
        }

        return $this->getView();
    }
}