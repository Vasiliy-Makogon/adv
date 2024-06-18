<?php

namespace Krugozor\Framework\Module\User\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Notification;

class BackendDelete extends AbstractController
{
    use BackendUserIdValidatorTrait;

    /**
     * @return Notification
     * @throws MySqlException
     */
    public function run(): Notification
    {
        $this->getView()->getLang()->loadI18n(
            'Common/BackendGeneral',
            'User/BackendCommon'
        );

        if (!$this->checkAccess()) {
            $message = $this->getView()->getLang()->get('notification.message.forbidden_access');
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($message)
                ->setRedirectUrl('/user/backend-main/')
                ->run();
        }

        if ($notification = $this->checkIdOnValid()) {
            return $notification;
        }

        if (empty($this->getRequest()->getRequest('id', Request::SANITIZE_INT))) {
            $message = $this->getView()->getLang()->get('notification.message.id_user_not_exists');
            return $this->createNotification(Notification::TYPE_ALERT)
                ->setMessage($message)
                ->setRedirectUrl('/user/backend-main/')
                ->run();
        }

        $adverts = $this->getMapper(AdvertMapper::class)->findModelListByParams(
            ['where' => ['advert_id_user = ?i' => [$this->user->getId()]]]
        );
        /* @var $advert Advert */
        foreach ($adverts as $advert) {
            $advert->delete();
        }

        $this->getMapper(UserMapper::class)->deleteModel($this->user);

        $message = $this->getView()->getLang()->get('notification.message.user_delete');
        return $this->createNotification()
            ->setType(Notification::TYPE_NORMAL)
            ->setMessage($message)
            ->addParam('user_name', $this->user->getFullName())
            ->setRedirectUrl($this->getRequest()->getRequest('referer', Request::SANITIZE_STRING))
            ->run();
    }
}