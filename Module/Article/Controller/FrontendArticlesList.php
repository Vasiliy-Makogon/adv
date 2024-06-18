<?php

namespace Krugozor\Framework\Module\Article\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\Article\Mapper\ArticleMapper;
use Krugozor\Framework\Module\Article\Service\FrontendArticlesListService;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Pagination\Adapter;
use Krugozor\Framework\View;

class FrontendArticlesList extends AbstractController
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
        $articlesListService = (new FrontendArticlesListService(
            $this->getRequest(),
            $this->getMapper(ArticleMapper::class),
            Adapter::getManager($this->getRequest(), 30, 15)
        ))->findList();

        $this->getView()->getStorage()->offsetSet('articlesListService', $articlesListService);

        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }
}