<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Service\FrontendUserAdvertsListService;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Pagination\Adapter;
use Krugozor\Framework\View;

class FrontendUserAdvertsList extends AbstractController
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

    /**
     * @return Notification|View
     * @throws MySqlException
     */
    public function run(): Notification|View
    {
        if (!$this->checkAccess()) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.forbidden_access'))
                ->setRedirectUrl(
                    '/authorization/frontend-login/?referer=' . urlencode(
                        $this->getRequest()->getRequestUri()->getEscapeUriValue()
                    )
                )
                ->run();
        }

        $this->getMapper(AdvertMapper::class)->attachGuestUserAdverts(
            $this->getCurrentUser()
        );

        // Что бы не подменили пользователя из запроса
        $this->getRequest()->getRequest()->offsetSet('user', $this->getCurrentUser()->getId());

        $advertsListService = (new FrontendUserAdvertsListService(
            $this->getRequest(),
            $this->getMapper(AdvertMapper::class),
            Adapter::getManager($this->getRequest(), 20, 15)
        ))
            ->setCategoryMapper($this->getMapper(CategoryMapper::class))
            ->findList();

        $this->getView()->getStorage()->offsetSet('advertsListService', $advertsListService);

        $this->getView()->getStorage()->offsetSet(
            'userCategories',
            $this->getMapper(CategoryMapper::class)->loadPathAllUserCategories($this->getCurrentUser()->getId())
        );

        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }
}