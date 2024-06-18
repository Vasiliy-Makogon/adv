<?php

namespace Krugozor\Framework\Module\NotFound;

use Krugozor\Framework\Application;
use Krugozor\Framework\View;

trait ShowNotFountTrait
{
    /**
     * @return View
     */
    protected function showNotFoundPage(): View
    {
        $this->getView()->getLang()->get('title')->clear();
        $this->getView()->getLang()->loadI18n('NotFound/NotFound');
        $this->getResponse()->setHttpStatusCode(404);

        $this->getView()->setCurrentUser($this->getCurrentUser());
        $this->getView()->setTemplateFile(
            Application::getAnchor('NotFound')::getPath('/Template/NotFound.phtml')
        );

        return $this->getView();
    }

    /**
     * @return View
     */
    protected function showGonePage(): View
    {
        $this->getView()->getLang()->get('title')->clear();
        $this->getView()->getLang()->loadI18n('NotFound/Gone');
        $this->getResponse()->setHttpStatusCode(410);

        $this->getView()->setCurrentUser($this->getCurrentUser());
        $this->getView()->setTemplateFile(
            Application::getAnchor('NotFound')::getPath('/Template/Gone.phtml')
        );

        return $this->getView();
    }

    /**
     * @return View
     */
    protected function showForbiddenPage(): View
    {
        $this->getView()->getLang()->get('title')->clear();
        $this->getView()->getLang()->loadI18n('NotFound/Forbidden');
        $this->getResponse()->setHttpStatusCode(403);

        $this->getView()->setCurrentUser($this->getCurrentUser());
        $this->getView()->setTemplateFile(
            Application::getAnchor('NotFound')::getPath('/Template/Forbidden.phtml')
        );

        return $this->getView();
    }
}