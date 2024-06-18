<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Service\BackendAdvertsListService;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Pagination\Adapter;
use Krugozor\Framework\View;

class BackendMain extends AbstractController
{
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
    public function run(): Notification|View
    {
        if (!$this->checkAccess()) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.forbidden_access'))
                ->setRedirectUrl('/admin/')
                ->run();
        }

        $advertsListService = (new BackendAdvertsListService(
            $this->getRequest(),
            $this->getMapper(AdvertMapper::class),
            Adapter::getManager($this->getRequest(), 15)
        ))
            ->setCategoryMapper($this->getMapper(CategoryMapper::class))
            ->findList();

        $this->getView()->getStorage()->offsetSet('advertsListService', $advertsListService);

        $this->getView()->getStorage()->offsetSet(
            'currentCategory',
            $this->getMapper(CategoryMapper::class)->findModelById(
                $this->getRequest()->getRequest('category', Request::SANITIZE_INT) ?: 0
            )
        );

        return $this->getView();
    }
}