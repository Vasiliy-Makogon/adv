<?php

namespace Krugozor\Framework\Module\Article\Controller\Trait;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Article\Mapper\ArticleMapper;
use Krugozor\Framework\Module\Article\Model\Article;
use Krugozor\Framework\Notification;

trait BackendIdValidatorTrait
{
    /** @var Article|null */
    protected ?Article $article = null;

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function checkIdOnValid(): ?Notification
    {
        if ($id = $this->getRequest()->getRequest('id', Request::SANITIZE_INT)) {
            $this->article = $this->getMapper(ArticleMapper::class)->findModelById($id);

            if (!$this->article->getId()) {
                return $this->createNotification(Notification::TYPE_ALERT)
                    ->setMessage($this->getView()->getLang()->get('notification.message.element_does_not_exist'))
                    ->setRedirectUrl('/article/backend-main/')
                    ->run();
            }
        }

        return null;
    }
}