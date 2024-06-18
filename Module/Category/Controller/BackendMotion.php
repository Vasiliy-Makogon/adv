<?php

namespace Krugozor\Framework\Module\Category\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Notification;

class BackendMotion extends AbstractController
{
    use BackendIdValidatorTrait;

    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/BackendGeneral',
        ];
    }

    /**
     * @return Notification
     * @throws MySqlException
     */
    public function run(): Notification
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

        if (!$this->getRequest()->getRequest('id', Request::SANITIZE_INT)) {
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.id_element_not_exists'))
                ->setRedirectUrl('/category/backend-main/')
                ->run();
        }

        $notification = $this->createNotification();

        switch ($this->getRequest()->getRequest('tomotion', Request::SANITIZE_STRING)) {
            case 'up':
                $this->getMapper(CategoryMapper::class)->motionUp($this->category);
                $notification->setMessage($this->getView()->getLang()->get('notification.message.element_motion_up'));
                break;

            case 'down':
                $this->getMapper(CategoryMapper::class)->motionDown($this->category);
                $notification->setMessage($this->getView()->getLang()->get('notification.message.element_motion_down'));
                break;

            default:
                $notification->setType(Notification::TYPE_ALERT);
                $notification->setMessage($this->getView()->getLang()->get('notification.message.unknown_tomotion'));
                break;
        }

        return $notification
            ->setRedirectUrl('/category/backend-main/#category_' . $this->category->getId())
            ->run();
    }
}