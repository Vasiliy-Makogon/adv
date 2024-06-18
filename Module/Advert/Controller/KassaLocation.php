<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Http\Response;
use Krugozor\Framework\Module\Advert\Controller\Trait\FrontendIdValidatorTrait;
use Krugozor\Framework\Module\Prodamus\Service\Prodamus;
use Krugozor\Framework\Notification;

/**
 * Переадресация на платежную систему.
 * Ссылка формируется в @see Prodamus::getShortMerchantUrl() и предоставляется пользователю
 * в письме, в коротком, удобном виде.
 */
class KassaLocation extends AbstractController
{
    use FrontendIdValidatorTrait;

    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Advert/FrontendCommon'
        ];
    }

    /**
     * @return Notification|Response
     * @throws MySqlException
     */
    public function run(): Notification|Response
    {
        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        $action = $this->getRequest()->getRequest('action', Request::SANITIZE_INT);

        return $this->getResponse()->setHeader(
            Response::HEADER_LOCATION,
            $this->advert->getMerchant()->getMerchantUrl($action)
        );
    }
}