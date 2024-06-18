<?php

namespace Krugozor\Framework\Module\Advert\Controller;

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
use Krugozor\Framework\Module\Category\Model\Category;
use Krugozor\Framework\View;
use ReflectionException;

class FrontendCategoryList extends AbstractController
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
            $this->getRequest()->getVirtualControllerPath()
        ];
    }

    /**
     * @return View
     * @throws ReflectionException
     * @throws MySqlException
     */
    public function run(): View
    {
        /** @var Category $currentCategory */
        $currentCategory = $this->getMapper(CategoryMapper::class)->findByUrl(
            $this->getRequest()->getRequest('category_url', Request::SANITIZE_STRING)
        );
        if (!$currentCategory->getId()) {
            return $this->showGonePage();
        }

        $advertsListService = (new FrontendAdvertsListService(
            $this->getRequest(),
            $this->getMapper(AdvertMapper::class),
            Adapter::getManager(
                $this->getRequest(),
                Registry::getInstance()->get('APPLICATION.MAX_ADVERTS_COUNT_ON_CATEGORY'),
                15
            )
        ))->setCategory($currentCategory);

        $country = $region = $city = null;
        $territoryList = new TerritoryList();

        if ($country_name = $this->getRequest()->getRequest('country_name_en', Request::SANITIZE_STRING)) {
            $country = $this->getMapper(CountryMapper::class)->findByNameEn($country_name);
            if (!$country->getId() || !$country->getActive()) {
                return $this->showGonePage();
            }
            $advertsListService->setTerritoryCountry($country);
            $territoryList->setTerritory($country);
        }

        if ($country && $region_name = $this->getRequest()->getRequest('region_name_en', Request::SANITIZE_STRING)) {
            $region = $this->getMapper(RegionMapper::class)->findByNameEn($region_name);
            if (!$region->getId() || !$country->getActive()) {
                return $this->showGonePage();
            }
            $advertsListService->setTerritoryRegion($region);
            $territoryList->setTerritory($region);
        }

        if ($region && $city_name = $this->getRequest()->getRequest('city_name_en', Request::SANITIZE_STRING)) {
            $city = $this->getMapper(CityMapper::class)->findByNameEnAndRegion($city_name, $region);
            if (!$city->getId()) {
                return $this->showGonePage();
            }
            $advertsListService->setTerritoryCity($city);
            $territoryList->setTerritory($city);
        }

        $this->getView()->getStorage()->offsetSet('territoryList', $territoryList);

        $advertsListService->findList();

        // Исключить из яндекса страницы регионов без объявлений
        if ($advertsListService->getList()->count() === 0 &&
            (!$this->getRequest()->getRequest('type') && !$this->getRequest()->getRequest('keyword'))
        ) {
            $this->getResponse()->setHttpStatusCode(404);
        }

        /*$num_rows = round($advertsListService->getList()->count() / 4);
        $num_rows = $num_rows > 1 ? $num_rows - 1 : 1;

        $this->getView()->getStorage()->offsetSet(
            'specialAdvertsList',
            $this->getMapper(AdvertMapper::class)
                ->findLastSpecialAdverts($num_rows)
        );*/

        // Субкатегории данного раздела.
        $this->getView()->getStorage()->offsetSet(
            'subcategories',
            $currentCategory->findChildsWithIndent(2)
        );

        $this->getView()->getStorage()->offsetSet(
            'pathToCurrentCategory',
            $currentCategory->findPath()
        );

        // Основные категории для подвала.
        if ($currentCategory->isTopCategory()) {
            $this->getView()->getStorage()->offsetSet(
                'categories',
                $this->getMapper(CategoryMapper::class)->findChilds(0)
            );
        }

        $this->getView()->setCurrentUser($this->getCurrentUser());
        $this->getView()->getStorage()->offsetSet('currentCategory', $currentCategory);
        $this->getView()->getStorage()->offsetSet('advertsListService', $advertsListService);

        return $this->getView();
    }
}