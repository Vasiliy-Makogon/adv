<?php

namespace Krugozor\Framework\Module\Category\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Module\Category\Model\Category;
use Krugozor\Framework\Notification;

trait BackendIdValidatorTrait
{
    /** @var Category|null */
    protected ?Category $category = null;

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function checkIdOnValid(): ?Notification
    {
        if ($id = $this->getRequest()->getRequest('id', Request::SANITIZE_INT)) {
            $this->category = $this->getMapper(CategoryMapper::class)->findModelById($id);

            if (!$this->category->getId()) {
                return $this->createNotification(Notification::TYPE_ALERT)
                    ->setMessage($this->getView()->getLang()->get('notification.message.element_does_not_exist'))
                    ->setRedirectUrl('/category/backend-main/')
                    ->run();
            }
        }

        return null;
    }
}