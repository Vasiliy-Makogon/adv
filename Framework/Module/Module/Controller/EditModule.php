<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Module\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Cover\Data\PostData;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Module\Controller\Trait\BackendModuleIdValidatorTrait;
use Krugozor\Framework\Module\Module\Mapper\ModuleMapper;
use Krugozor\Framework\Module\Module\Validator\ModuleKeyExistsValidator;
use Krugozor\Framework\Module\Module\Validator\ModuleNameExistsValidator;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Validator\Validator;
use Krugozor\Framework\View;

class EditModule extends AbstractController
{
    use BackendModuleIdValidatorTrait;

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
                ->setRedirectUrl($this->getRedirectUrl())
                ->run();
        }

        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (!$this->moduleModel) {
            $this->moduleModel = $this->getMapper(ModuleMapper::class)->createModel();
        }

        if (Request::isPost() && $notification = $this->post()) {
            return $notification;
        }

        $this->getView()->getStorage()->offsetSet('moduleModel', $this->moduleModel);

        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function post(): ?Notification
    {
        $post = $this->getRequest()->getPost('module', PostData::class);
        $this->moduleModel->setData($post);

        $validator = new Validator('common/general', 'module/edit-module');
        $validator->addErrors($this->moduleModel->getValidateErrors());

        if ($this->moduleModel->getName()) {
            $validator->add(
                'name',
                (new ModuleNameExistsValidator($this->moduleModel))
                    ->setMapper($this->getMapper(ModuleMapper::class))
            );
        }

        if ($this->moduleModel->getKey()) {
            $validator->add(
                'key',
                (new ModuleKeyExistsValidator($this->moduleModel))
                    ->setMapper($this->getMapper(ModuleMapper::class))
            );
        }

        $validator->validate();

        if ($errors = $validator->getErrors()) {
            $this->getView()->getErrors()->setData($errors);

            $notification = $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.post_errors'));
            $this->getView()->setNotification($notification);

            return null;
        } else {
            $this->getMapper(ModuleMapper::class)->saveModel($this->moduleModel);

            $notification_url =
                $this->getRequest()->getRequest('return_on_page', Request::SANITIZE_INT)
                    ? '/module/edit-module/?id=' . $this->moduleModel->getId()
                    : ($this->getRequest()->getRequest('referer', Request::SANITIZE_STRING)
                        ?: $this->getRedirectUrl());

            return $this->createNotification()
                ->setMessage($this->getView()->getLang()->get('notification.message.data_saved'))
                ->setRedirectUrl($notification_url)
                ->run();
        }
    }
}