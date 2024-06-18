<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Framework\Controller\AbstractAjaxController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Mapper\ThumbnailMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\Advert\Model\Thumbnail;
use Krugozor\Framework\View;

/**
 * Обработчик Ajax-запросов из управления изображениями.
 * Отвязывает одно изображение от объявления.
 * Далее эти изображения удаляются с помощью cron-скрипта remove_thumbnail_advert.php
 */
class ThumbnailUnlink extends AbstractAjaxController
{
    /**
     * @return View
     */
    public function run(): View
    {
        $result = false;

        /* @var Thumbnail $thumbnail */
        $thumbnail = $this->getMapper(ThumbnailMapper::class)->findModelById(
            $this->getRequest()->getRequest('id', Request::SANITIZE_INT)
        );

        if ($thumbnail->getId()) {
            /** @var Advert $advert */
            $advert = $this->getMapper(AdvertMapper::class)->findModelById($thumbnail->getIdAdvert());

            if ($advert->belongToRegisterUser($this->getCurrentUser()) || $this->getCurrentUser()->isAdministrator()) {
                $result = (bool) $thumbnail->unlink();
            }
        }

        $this->getView()->getStorage()->offsetSet('result', $result);

        return $this->getView();
    }
}