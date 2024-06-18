<?php

namespace Krugozor\Framework\Module\Category\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Notification;
use Krugozor\Framework\View;

class BackendAddList extends AbstractController
{
    use BackendIdValidatorTrait;

    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/BackendGeneral',
            $this->getRequest()->getVirtualControllerPath()
        ];
    }

    /**
     * @return Notification|View
     * @throws MySqlException
     */
    public function run(): Notification|View
    {
        if (!$this->checkAccess()) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.forbidden_access'))
                ->setRedirectUrl('/category/backend-main/')
                ->run();
        }

        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (Request::isPost() && $result = $this->post()) {
            return $result;
        }

        $this->getView()->getStorage()->offsetSet(
            'tree',
            $this->getMapper(CategoryMapper::class)->loadTree()
        );

        return $this->getView();
    }

    /**
     * @return Notification
     * @throws MySqlException
     */
    protected function post(): Notification
    {
        if (!$newCategoriesList = $this->getRequest()->getPost('list', Request::SANITIZE_STRING_TO_ARRAY)) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.list_is_empty'))
                ->setRedirectUrl('/category/backend-main/')
                ->run();
        }

        $this->category->createChildsFromList($newCategoriesList);

        return $this->createNotification()
            ->setMessage($this->getView()->getLang()->get('notification.message.categories_added'))
            ->setRedirectUrl('/category/backend-main/#category_' . $this->category->getId())
            ->run();
    }
}