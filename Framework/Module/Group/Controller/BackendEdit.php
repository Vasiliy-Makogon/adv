<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Group\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Cover\Data\PostData;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Group\Controller\Trait\BackendIdValidatorTrait;
use Krugozor\Framework\Module\Group\Mapper\GroupMapper;
use Krugozor\Framework\Module\Module\Mapper\ModuleMapper;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Validator\Validator;
use Krugozor\Framework\View;

class BackendEdit extends AbstractController
{
    use BackendIdValidatorTrait;

    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/BackendGeneral',
            'Group/BackendCommon',
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
                ->setRedirectUrl('/group/backend-main/')
                ->run();
        }

        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (!$this->groupModel) {
            $this->groupModel = $this->getMapper(GroupMapper::class)->createModel();
        }

        if (Request::isPost() && $notification = $this->post()) {
            return $notification;
        }

        $this->getView()->getStorage()->offsetSet(
            'groupModel',
            $this->groupModel
        );

        $this->getView()->getStorage()->offsetSet(
            'modulesModelList',
            $this->getMapper(ModuleMapper::class)->findModelListByParams([])
        );

        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function post(): ?Notification
    {
        $this->groupModel->setData(
            $this->getRequest()->getPost('group', PostData::class), ['access']
        );

        $validator = new Validator('common/general');
        $validator->addErrors($this->groupModel->getValidateErrors());

        $notification = $this->createNotification();

        if ($errors = $validator->getErrors()) {
            $this->getView()->getErrors()->setData($errors);

            $notification
                ->setType(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.post_errors'));
            $this->getView()->setNotification($notification);

            return null;
        } else {
            $this->groupModel->save();

            $message = $this->getView()->getLang()->get('notification.message.data_saved');
            $url = $this->getRequest()->getRequest('return_on_page', Request::SANITIZE_INT)
                ? sprintf('/group/backend-edit/?id=%s', $this->groupModel->getId())
                : ($this->getRequest()->getRequest('referer', Request::SANITIZE_STRING)
                    ?: '/group/backend-main/');

            return $notification
                ->setMessage($message)
                ->setRedirectUrl($url)
                ->run();
        }
    }
}