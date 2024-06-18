<?php

namespace Krugozor\Framework\Module\Article\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Cover\Data\PostData;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Article\Controller\Trait\BackendIdValidatorTrait;
use Krugozor\Framework\Module\Article\Mapper\ArticleMapper;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Type\Date\DateTime;
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
                ->setRedirectUrl('/article/backend-main/')
                ->run();
        }

        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (!$this->article) {
            $this->article = $this->getMapper(ArticleMapper::class)->createModel();
        }

        if (Request::isPost() && $notification = $this->post()) {
            return $notification;
        }

        $this->getView()->getStorage()->offsetSet('article', $this->article);

        return $this->getView();
    }

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function post(): ?Notification
    {
        $postData = $this->getRequest()->getPost('article', PostData::class);
        if (!$postData->get('url')) {
            $postData->setData(['url' => $postData->get('header')]);
        }
        $this->article->setData($postData);

        $validator = new Validator('common/general');
        $validator->addErrors($this->article->getValidateErrors());

        $notification = $this->createNotification();

        if ($errors = $validator->validate()->getErrors()) {
            $this->getView()->getErrors()->setData($errors);

            $notification
                ->setType(Notification::TYPE_ALERT)
                ->setMessage($this->getView()->getLang()->get('notification.message.post_errors'));
            $this->getView()->setNotification($notification);

            return null;
        } else {
            if ($this->article->getId() && $this->article->getTrack()->getDifference()) {
                $this->article->setEditDate(new DateTime());
                $this->article->deleteCache();
            }

            $this->article->save();

            $message = $this->getView()->getLang()->get('notification.message.data_saved');
            $url = $this->getRequest()->getRequest('return_on_page', Request::SANITIZE_INT)
                ? '/article/backend-edit/?id=' . $this->article->getId()
                : ($this->getRequest()->getRequest('referer', Request::SANITIZE_STRING) ?: '/article/backend-main/');

            return $notification
                ->setMessage($message)
                ->setRedirectUrl($url)
                ->run();
        }
    }
}