<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Advert\Service;

use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Service\Trait\MemcacheTrait;
use Krugozor\Framework\Service\AbstractListService;

/**
 * Сервис, возвращающий одно объявление, т.к. шаблон AdvertsList.phtml заточен именно
 * на объект сервиса, производного от ListAbstract.
 */
class FrontendSingleAdvertListService extends AbstractListService
{
    use MemcacheTrait;

    /**
     * @inheritDoc
     */
    public function findList(): static
    {
        $this->list = $this->findByIdForViewThroughCache(
            $this->request->getRequest('id', Request::SANITIZE_INT)
        );

        $this->paginationManager->setCount($this->list->count());

        return $this;
    }
}