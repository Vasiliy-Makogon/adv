<?php

namespace Krugozor\Framework\Module\Article\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\Article\Mapper\ArticleMapper;
use Krugozor\Framework\Module\Article\Service\BackendArticlesListService;
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

        $articlesListService = (new BackendArticlesListService(
            $this->getRequest(),
            $this->getMapper(ArticleMapper::class),
            Adapter::getManager($this->getRequest(), 15)
        ))->findList();

        $this->getView()->getStorage()->offsetSet('articlesListService', $articlesListService);

        return $this->getView();
    }
}