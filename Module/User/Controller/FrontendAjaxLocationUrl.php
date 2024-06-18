<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Framework\Controller\AbstractAjaxController;
use Krugozor\Framework\Controller\DisableAuthorizationTrait;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\User\Mapper\CityMapper;
use Krugozor\Framework\Module\User\Mapper\RegionMapper;
use Krugozor\Framework\View;

class FrontendAjaxLocationUrl extends AbstractAjaxController
{
    use DisableAuthorizationTrait;

    /**
     * @return View
     */
    public function run(): View
    {
        $this->getView('Ajax');

        $location = $this->getRequest()->getRequest('location', Request::SANITIZE_STRING);
        $category = $this->getRequest()->getRequest('category', Request::SANITIZE_INT);
        $id = $this->getRequest()->getRequest('id', Request::SANITIZE_INT);

        if (!($location && in_array($location, ['region', 'city']) && $id)) {
            return $this->getView();
        }

        $this->getView()->getStorage()->offsetSet(
            'locations',
            match ($location) {
                'region' => $this->getMapper(RegionMapper::class)
                    ->getListForSelectOptionsWithsUrl($id, $category),
                'city' => $this->getMapper(CityMapper::class)
                    ->getListForSelectOptionsWithsUrl($id, $category),
            }
        );

        return $this->getView();
    }
}