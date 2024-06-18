<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Http\Response;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\Advert\Service\FrontendAdvertsSimilarListService;
use Krugozor\Framework\Module\Advert\Service\FrontendSingleAdvertListService;
use Krugozor\Framework\Module\Category\Model\Category;
use Krugozor\Framework\Module\NotFound\ShowNotFountTrait;
use Krugozor\Framework\Module\User\Cover\TerritoryList;
use Krugozor\Framework\Module\User\Model\AbstractTerritory;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Pagination\Adapter;
use Krugozor\Framework\View;

class FrontendAdvertView extends AbstractController
{
    use ShowNotFountTrait;

    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/FrontendGeneral',
            'Local/FrontendGeneral',
            'Advert/FrontendCommon',
            'Advert/FrontendAdvertView'
        ];
    }

    /**
     * @return View|Response
     * @throws MySqlException
     */
    public function run(): View|Response
    {
        $singleAdvertListService = (new FrontendSingleAdvertListService(
            $this->getRequest(),
            $this->getMapper(AdvertMapper::class),
            Adapter::getManager($this->getRequest(), 1, 1)
        ))->findList();

        if (!$singleAdvertListService->getList()->count()) {
            return $this->showGonePage();
        }

        /** @var Advert $advert */
        $advert = $singleAdvertListService->getList()->getFirst()->get('advert');

        /** @var Category $currentCategory */
        $currentCategory = $singleAdvertListService->getList()->getFirst()->get('category');

        /** @var User $user */
        $user = $singleAdvertListService->getList()->getFirst()->get('user');

        if (!$this->getRequest()->getGet(Notification::NOTIFICATION_PARAM_NAME, Request::SANITIZE_INT)) {
            $this->getResponse()->unsetHeader(Response::HEADER_LAST_MODIFIED);
            $this->getResponse()->unsetHeader(Response::HEADER_EXPIRES);
            $this->getResponse()->unsetHeader(Response::HEADER_CACHE_CONTROL);

            if (!Request::IfModifiedSince($advert->getLastModifiedDate())) {
                return $this->getResponse()->setHttpStatusCode(304);
            }

            $this->getResponse()->setHeader(Response::HEADER_LAST_MODIFIED, $advert->getLastModifiedDate()->formatHttpDate());
            $this->getResponse()->setHeader(Response::HEADER_CACHE_CONTROL, 'no-cache, must-revalidate');
        }

        $territoryList = new TerritoryList();

        foreach ($singleAdvertListService->getList()->getFirst()->filter(function ($value) {
            return $value instanceof AbstractTerritory;
        }) as $territory) {
            $territoryList->setTerritory($territory);
        }

        $this->getView()->getStorage()->offsetSet('pathToCurrentCategory', $currentCategory->findPath());

        $this->getView()->getStorage()->offsetSet('territoryList', $territoryList);

        $advertsSimilarListService = (new FrontendAdvertsSimilarListService(
            $this->getRequest(),
            $this->getMapper(AdvertMapper::class),
            Adapter::getManager($this->getRequest(), 5, 1)
        ))
            ->setAdvert($advert)
            ->findList();

        if (!$user->getActive() || !$advert->getActive()) {
            $reason = match (true) {
                !$user->getActive() => $this->getView()->getLang()->get('notification.message.advert_close_user_ban'),
                $user->getActive() && !$user->isGuest() && !$advert->getActive() => $this->getView()->getLang()->get('notification.message.advert_close_user'),
                $user->isGuest() && !$advert->getActive() => $this->getView()->getLang()->get('notification.message.advert_close'),
                default => null,
            };

            if ($reason) {
                $notification = $this->createNotification(Notification::TYPE_ALERT)
                    ->setHeader($this->getView()->getLang()->get('notification.header.advert_close'))
                    ->setMessage($reason);
                $this->getView()->setNotification($notification);
            }

            return $this->showForbiddenPage();
        } // Иначе показ объявления увеличиваем на 1.
        else {
            if (
                !$this->getCurrentUser()->isGuest()
                && $advert->getIdUser() != $this->getCurrentUser()->getId()
                or $this->getCurrentUser()->isGuest()
            ) {
                $this->getMapper(AdvertMapper::class)->incrementViewCount($advert);
            }
        }

        // Если администратор просмотрел объявление, то делаем отметку для административной части.
        if ($this->getCurrentUser()->isAdministrator() && !$advert->getWasModerated()) {
            $advert->setWasModerated(1);
            $this->getMapper(AdvertMapper::class)->saveModel($advert);
        }

        $this->getView()->getStorage()->offsetSet('singleAdvertListService', $singleAdvertListService);
        $this->getView()->getStorage()->offsetSet('advertsSimilarListService', $advertsSimilarListService);
        $this->getView()->getStorage()->offsetSet('currentCategory', $currentCategory);
        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }
}
