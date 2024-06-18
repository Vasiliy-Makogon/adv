<?php

namespace Krugozor\Framework\Module\Article\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Http\Response;
use Krugozor\Framework\Module\Article\Mapper\ArticleMapper;
use Krugozor\Framework\Module\Article\Model\Article;
use Krugozor\Framework\Module\Article\Service\FrontendSingleArticleListService;
use Krugozor\Framework\Module\NotFound\ShowNotFountTrait;
use Krugozor\Framework\Pagination\Adapter;
use Krugozor\Framework\View;

class FrontendArticleView extends AbstractController
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
            'Article/FrontendArticleView'
        ];
    }

    /**
     * @return View|Response
     * @throws MySqlException
     */
    public function run(): View|Response
    {
        $singleArticleListService = (new FrontendSingleArticleListService(
            $this->getRequest(),
            $this->getMapper(ArticleMapper::class),
            Adapter::getManager($this->getRequest(), 1, 1)
        ))->findList();

        if (!$singleArticleListService->getList()->count()) {
            return $this->showGonePage();
        }

        /** @var Article $article */
        $article = $singleArticleListService->getList()->getFirst();

        $this->getResponse()->unsetHeader(Response::HEADER_LAST_MODIFIED);
        $this->getResponse()->unsetHeader(Response::HEADER_EXPIRES);
        $this->getResponse()->unsetHeader(Response::HEADER_CACHE_CONTROL);

        if (!Request::IfModifiedSince($article->getLastModifiedDate())) {
            return $this->getResponse()->setHttpStatusCode(304);
        }

        $this->getResponse()->setHeader(Response::HEADER_LAST_MODIFIED, $article->getLastModifiedDate()->formatHttpDate());
        $this->getResponse()->setHeader(Response::HEADER_CACHE_CONTROL, 'no-cache, must-revalidate');

        $this->getView()->getStorage()->offsetSet('article', $article);
        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }
}
