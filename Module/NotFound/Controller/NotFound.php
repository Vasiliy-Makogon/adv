<?php

namespace Krugozor\Framework\Module\NotFound\Controller;

use Krugozor\Framework\Application;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Module\NotFound\ShowNotFountTrait;
use Krugozor\Framework\View;

/**
 * Не используется как самодостаточный Controller, а инстанцируется
 * в @see Application в случае запроса несуществующего адреса.
 */
class NotFound extends AbstractController
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
     */
    public function run(): View
    {
        return $this->showNotFoundPage();
    }
}