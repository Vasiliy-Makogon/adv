<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Article\Service;

use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Article\Model\Article;
use Krugozor\Framework\Service\AbstractListService;
use Krugozor\Framework\Service\Trait\MemcacheTrait;

/**
 * Сервис, возвращающий одну статью.
 */
class FrontendSingleArticleListService extends AbstractListService
{
    use MemcacheTrait;

    /**
     * @inheritDoc
     */
    public function findList(): static
    {
        $article = $this->findByIdThroughCache(
            $this->request->getRequest('id', Request::SANITIZE_INT),
            Article::class,
            60 * 60 * 24 * 30
        );

        if ($article->getId()) {
            $this->list->append($article);
        }

        $this->paginationManager->setCount($this->list->count());

        return $this;
    }
}