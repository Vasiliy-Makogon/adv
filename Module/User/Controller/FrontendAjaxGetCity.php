<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Framework\Controller\AbstractAjaxController;
use Krugozor\Framework\Controller\DisableAuthorizationTrait;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\User\Mapper\CityMapper;
use Krugozor\Framework\View;

class FrontendAjaxGetCity extends AbstractAjaxController
{
    use DisableAuthorizationTrait;

    /**
     * @return View
     */
    public function run(): View
    {
        $this->getView('Ajax');

        if ($id_region = $this->getRequest()->getRequest('id', Request::SANITIZE_INT)) {
            $this->getView()->getStorage()->offsetSet(
                'locations',
                $this->getMapper(CityMapper::class)->getListForSelectOptions($id_region)
            );
        }

        return $this->getView();
    }
}