<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Module\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Cover\Data\PostData;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Module\Controller\Trait\BackendControllerIdValidatorTrait;
use Krugozor\Framework\Module\Module\Mapper\ControllerMapper;
use Krugozor\Framework\Module\Module\Mapper\ModuleMapper;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Validator\Validator;
use Krugozor\Framework\View;

class EditController extends AbstractController
{
    use BackendControllerIdValidatorTrait;

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

        if (!$this->controllerModel) {
            $this->controllerModel = $this->getMapper(ControllerMapper::class)
                ->createModel()
                ->setIdModule($this->getRequest()->getRequest('id_module', Request::SANITIZE_INT));
        }

        if (Request::isPost() && $notification = $this->post()) {
            return $notification;
        }

        $this->getView()->getStorage()->offsetSet(
            'modulesModelList',
            $this->getMapper(ModuleMapper::class)->findModelListByParams([])
        );

        $this->getView()->getStorage()->offsetSet('controllerModel', $this->controllerModel);

        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function post(): ?Notification
    {
        $post = $this->getRequest()->getPost('controller', PostData::class);
        $this->controllerModel->setData($post);

        $validator = new Validator('common/general');
        $validator
            ->addErrors($this->controllerModel->getValidateErrors())
            ->validate();

        if ($errors = $validator->getErrors()) {
            $this->getView()->getErrors()->setData($errors);

            $notification = $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.post_errors'));
            $this->getView()->setNotification($notification);

            return null;
        } else {
            $this->getMapper(ControllerMapper::class)->saveModel($this->controllerModel);

            $message = $this->getView()->getLang()->get('notification.message.data_saved');
            $url =
                $this->getRequest()->getRequest('return_on_page', Request::SANITIZE_INT)
                    ? sprintf(
                        '/module/edit-controller/?id=%s&id_module=%s',
                        $this->controllerModel->getId(),
                        $this->controllerModel->getIdModule()
                    )
                    : ($this->getRequest()->getRequest('referer', Request::SANITIZE_STRING)
                        ?: $this->getRedirectUrl());

            return $this->createNotification()
                ->setMessage($message)
                ->setRedirectUrl($url)
                ->run();
        }
    }
}