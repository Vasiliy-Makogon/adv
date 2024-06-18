<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\Advert\Controller\Trait\FrontendIdValidatorTrait;
use Krugozor\Framework\Notification;
use Krugozor\Framework\View;

/**
 * На данный контроллер идет location, если необходима оплата активации объявления,
 * т.е. объявление размещается в платную категорию.
 * @see FrontendEditAdvert
 */
class Payment extends AbstractController
{
    use FrontendIdValidatorTrait;

    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/FrontendGeneral',
            'Local/FrontendGeneral',
            'Advert/FrontendCommon'
        ];
    }

    /**
     * @return Notification|View
     * @throws MySqlException
     */
    public function run(): Notification|View
    {
        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        return $this->getView();
    }
}