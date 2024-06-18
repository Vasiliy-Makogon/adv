<?php

namespace Krugozor\Framework\Module\Category\Controller;

use Krugozor\Framework\Controller\AbstractAjaxController;
use Krugozor\Framework\Controller\DisableAuthorizationTrait;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Module\Category\View\FrontendAjaxGetChildCategoryView;
use Krugozor\Framework\View;

class FrontendAjaxGetChildCategory extends AbstractAjaxController
{
    use DisableAuthorizationTrait;

    /**
     * @return View
     */
    public function run(): View
    {
        $this->getView(
            'Ajax',
            FrontendAjaxGetChildCategoryView::class
        );

        $category_id = $this->getRequest()->getRequest('id', Request::SANITIZE_INT);
        $category = $this->getMapper(CategoryMapper::class)->findModelById($category_id);
        $this->getView()->getStorage()->clear()->setData($category->findChilds());

        return $this->getView();
    }
}