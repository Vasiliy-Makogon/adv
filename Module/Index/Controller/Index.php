<?php

namespace Krugozor\Framework\Module\Index\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Service\FrontendAdvertsIndexListService;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Module\User\Cover\TerritoryList;
use Krugozor\Framework\Module\User\Mapper\CountryMapper;
use Krugozor\Framework\Pagination\Adapter;
use Krugozor\Framework\Registry;
use Krugozor\Framework\View;

class Index extends AbstractController
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
     * @return View
     * @throws MySqlException
     */
    public function run(): View
    {
        $advertsListService = (new FrontendAdvertsIndexListService(
            $this->getRequest(),
            $this->getMapper(AdvertMapper::class),
            Adapter::getManager(
                $this->getRequest(),
                Registry::getInstance()->get('APPLICATION.MAX_ADVERTS_COUNT_ON_INDEX_PAGE'),
                0
            )
        ))->findList();

        $this->getView()->getStorage()->offsetSet('advertsListService', $advertsListService);

        $this->getView()->getStorage()->offsetSet(
            'categories',
            $this->getMapper(CategoryMapper::class)->loadTree(1)
        );

        $territoryList = new TerritoryList();
        $territoryList->setTerritory(
            $this->getMapper(CountryMapper::class)->findByNameEn('russia')
        );
        $this->getView()->getStorage()->offsetSet('territoryList', $territoryList);

        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }
}