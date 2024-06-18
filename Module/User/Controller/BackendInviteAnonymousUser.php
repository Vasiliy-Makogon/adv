<?php

namespace Krugozor\Framework\Module\User\Controller;

use Exception;
use Krugozor\Framework\Controller\AbstractAjaxController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\MailQueue\Mapper\MailQueueMapper;
use Krugozor\Framework\Module\MailQueue\Model\MailQueue;
use Krugozor\Framework\Module\User\Mapper\InviteAnonymousUserMapper;
use Krugozor\Framework\Module\User\Model\InviteAnonymousUser;
use Krugozor\Framework\Registry;
use Krugozor\Framework\Type\Date\DateTime;

class BackendInviteAnonymousUser extends AbstractAjaxController
{
    public function run()
    {
        $this->getView('Ajax')->getLang()->loadI18n('Common/BackendGeneral');

        $data = ['message' => ''];

        try {
            if (!$this->checkAccess()) {
                throw new Exception(strip_tags(
                    $this->getView()->getLang()->get('notification.message.forbidden_access')
                ));
            }

            $id = $this->getRequest()->getRequest('advert', Request::SANITIZE_INT);

            if (!$id) {
                throw new Exception(strip_tags(
                    $this->getView()->getLang()->get('notification.message.id_element_not_exists')
                ));
            }

            /** @var Advert $advert */
            $advert = $this->getMapper(AdvertMapper::class)->findModelById($id);

            if (!$advert->getId()) {
                throw new Exception(strip_tags(
                    $this->getView()->getLang()->get('notification.message.element_does_not_exist')
                ));
            } else if (!$advert->getEmail()->getValue()) {
                throw new Exception(strip_tags(
                    $this->getView()->getLang()->get('notification.message.missing_email')
                ));
            }

            $mailQueue = new MailQueue();
            $mailQueue
                ->setSendDate(new DateTime())
                ->setToEmail($advert->getEmail()->getValue())
                ->setFromEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                ->setReplyEmail(Registry::getInstance()->get('EMAIL.NOREPLY'))
                ->setHeader('Приглашение на сайт ' . Registry::getInstance()->get('HOSTINFO.DOMAIN_AS_TEXT'))
                ->setTemplate($this->getRealLocalTemplatePath('BackendInviteAnonymousUser'))
                ->setMailData([
                    'advert' => $advert,
                    'hostinfo' => Registry::getInstance()->get('HOSTINFO'),
                ]);
            $this->getMapper(MailQueueMapper::class)->saveModel($mailQueue);

            $data['message'] = $this->getView()->getLang()->get('notification.header.action_complete');

            /** @var InviteAnonymousUser $model */
            $model = $this->getMapper(InviteAnonymousUserMapper::class)->createModel();
            $model->setUniqueCookieId($advert->getUniqueUserCookieId());
            $this->getMapper(InviteAnonymousUserMapper::class)->insert($model);
        } catch (Exception $e) {
            $data['message'] = $e->getMessage();
        }

        $this->getView()->getStorage()->clear()->setData($data);

        return $this->getView();
    }
}
