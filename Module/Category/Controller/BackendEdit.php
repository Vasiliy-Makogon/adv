<?php

namespace Krugozor\Framework\Module\Category\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Cover\Data\PostData;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
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

        if (!$this->category) {
            $this->category = $this->getMapper(CategoryMapper::class)->createModel();
            $this->category->setPid(
                $this->getRequest()->getRequest('pid', Request::SANITIZE_INT)
            );
        }

        if (Request::isPost() && $notification = $this->post()) {
            return $notification;
        }

        $this->getView()->getStorage()->offsetSet(
            'tree', $this->getMapper(CategoryMapper::class)->loadTree()
        );

        $this->getView()->getStorage()->offsetSet(
            'category', $this->category
        );

        return $this->getView();
    }

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function post(): ?Notification
    {
        /** @var PostData $postData */
        $postData = $this->getRequest()->getPost('category', PostData::class);
        if (!$postData->get('alias')) {
            $postData->setData(['alias' => $postData->get('name')]);
        }

        $this->category->setData($postData);

        $validator = new Validator('common/general');
        $validator->addErrors($this->category->getValidateErrors());
        $validator->validate();

        $notification = $this->createNotification();

        if ($errors = $validator->getErrors()) {
            $this->getView()->getErrors()->setData($errors);

            $notification
                ->setType(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.post_errors'));
            $this->getView()->setNotification($notification);

            return null;
        } else {
            $this->getMapper(CategoryMapper::class)->saveCategory($this->category);

            $message = $this->getView()->getLang()->get('notification.message.data_saved');
            $url = $this->getRequest()->getRequest('return_on_page', Request::SANITIZE_INT)
                ? '/category/backend-edit/?id=' . $this->category->getId()
                : '/category/backend-main/#category_' . $this->category->getId();

            return $notification
                ->setMessage($message)
                ->setRedirectUrl($url)
                ->run();
        }
    }
}