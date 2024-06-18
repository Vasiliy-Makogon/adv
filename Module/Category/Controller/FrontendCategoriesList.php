<?php

namespace Krugozor\Framework\Module\Category\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Service\FrontendAdvertsListService;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Module\NotFound\ShowNotFountTrait;
use Krugozor\Framework\Module\User\Cover\TerritoryList;
use Krugozor\Framework\Module\User\Mapper\CityMapper;
use Krugozor\Framework\Module\User\Mapper\CountryMapper;
use Krugozor\Framework\Module\User\Mapper\RegionMapper;
use Krugozor\Framework\Pagination\Adapter;
use Krugozor\Framework\Registry;
use Krugozor\Framework\View;

class FrontendCategoriesList extends AbstractController
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
            $this->getRequest()->getVirtualControllerPath()
        ];
    }

    /**
     * @return View
     * @throws MySqlException
     */
    public function run(): View
    {
        $advertsListService = (new FrontendAdvertsListService(
            $this->getRequest(),
            $this->getMapper(AdvertMapper::class),
            Adapter::getManager(
                $this->getRequest(),
                Registry::getInstance()->get('APPLICATION.MAX_ADVERTS_COUNT_ON_REGION'),
                20
            )
        ));

        $country = $region = $city = null;
        $territoryList = new TerritoryList();

        if ($country_name = $this->getRequest()->getRequest('country_name_en', Request::SANITIZE_STRING)) {
            $country = $this->getMapper(CountryMapper::class)->findByNameEn($country_name);
            if (!$country->getId()) {
                return $this->showGonePage();
            }
            $advertsListService->setTerritoryCountry($country);
            $territoryList->setTerritory($country);
        }

        if ($country && $region_name = $this->getRequest()->getRequest()->region_name_en) {
            $region = $this->getMapper(RegionMapper::class)->findByNameEn($region_name);
            if (!$region->getId() || !$country->getActive()) {
                return $this->showGonePage();
            }
            $advertsListService->setTerritoryRegion($region);
            $territoryList->setTerritory($region);
        }

        if ($region && $city_name = $this->getRequest()->getRequest()->city_name_en) {
            $city = $this->getMapper(CityMapper::class)->findByNameEnAndRegion($city_name, $region);
            if (!$city->getId()) {
                return $this->showGonePage();
            }
            $advertsListService->setTerritoryCity($city);
            $territoryList->setTerritory($city);
        }

        $this->getView()->getStorage()->offsetSet('territoryList', $territoryList);

        $advertsListService->findList();

        if ($advertsListService->getList()->count() === 0) {
            $this->getResponse()->setHttpStatusCode(404);
        }

        $this->getView()->getStorage()->offsetSet(
            'categories',
            $this->getMapper(CategoryMapper::class)->loadTree(1)
        );

        /*$num_rows = round($advertsListService->getList()->count() / 4);
        $num_rows = $num_rows > 1 ? $num_rows - 1 : 1;

        $this->getView()->getStorage()->offsetSet(
            'specialAdvertsList',
            $this->getMapper(AdvertMapper::class)
                ->findLastSpecialAdverts($num_rows)
        );*/

        $this->getView()->getStorage()->offsetSet('advertsListService', $advertsListService);
        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }
}