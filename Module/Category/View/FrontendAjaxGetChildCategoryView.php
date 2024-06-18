<?php

namespace Krugozor\Framework\Module\Category\View;

use Krugozor\Framework\Module\Advert\Type\AdvertType;
use Krugozor\Framework\Module\Category\Model\Category;
use Krugozor\Framework\View\AjaxView;

class FrontendAjaxGetChildCategoryView extends AjaxView
{
    /**
     * @param null|array $data
     * @return string
     */
    protected function createJson(?array $data = null): string
    {
        $result = [];
        $parentCategory = null;

        /** @var Category $category */
        foreach ($this->getStorage() as $category) {
            if ($parentCategory === null) {
                $parentCategory = $category->findParentCategory();
            }

            $result[] = array(
                'id' => $category->getId(),
                'parent_id' => $parentCategory->getId(),
                'grandparent_id' => $parentCategory->getPid(),
                'haschilds' => ($category->findChildsIds()->count() ? '1' : '0'),
                'name' => $category->getNameForOptionElement(0),
                'advert_types' => $category->getAdvertTypes()->getAdvertTypes()->map(function (AdvertType $advertType) {
                    return [
                        'key' => $advertType->getValue(),
                        'value' => $advertType->getAsText()
                    ];
                })->getDataAsArray()
            );
        }

        return parent::createJson($result);
    }
}