<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Framework\Controller\AbstractAjaxController;
use Krugozor\Framework\Controller\DisableAuthorizationTrait;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\User\Mapper\RegionMapper;
use Krugozor\Framework\View;

class FrontendAjaxGetRegion extends AbstractAjaxController
{
    use DisableAuthorizationTrait;

    /**
     * @return View
     */
    public function run(): View
    {
        $this->getView('Ajax');

        if ($id_country = $this->getRequest()->getRequest('id', Request::SANITIZE_INT)) {
            $this->getView()->getStorage()->offsetSet(
                'locations',
                $this->getMapper(RegionMapper::class)->getListForSelectOptions($id_country)
            );
        }

        return $this->getView();
    }
}