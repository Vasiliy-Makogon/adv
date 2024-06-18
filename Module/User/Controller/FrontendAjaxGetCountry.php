<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Framework\Controller\AbstractAjaxController;
use Krugozor\Framework\Controller\DisableAuthorizationTrait;
use Krugozor\Framework\Module\User\Mapper\CountryMapper;
use Krugozor\Framework\View;

class FrontendAjaxGetCountry extends AbstractAjaxController
{
    use DisableAuthorizationTrait;

    /**
     * @return View
     */
    public function run(): View
    {
        $this->getView('Ajax');

        $this->getView()->getStorage()->offsetSet(
            'locations',
            $this->getMapper(CountryMapper::class)->getListForSelectOptions()
        );

        return $this->getView();
    }
}