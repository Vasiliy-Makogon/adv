<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\View;

class FrontendAdd extends AbstractController
{
    /**
     * @return array
     */
    protected function langs(): array
    {
        return [
            'Common/FrontendGeneral',
            'Local/FrontendGeneral',
            'Advert/FrontendEditAdvert',
            $this->getRequest()->getVirtualControllerPath()
        ];
    }

    /**
     * @return View
     */
    public function run(): View
    {
        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }
}