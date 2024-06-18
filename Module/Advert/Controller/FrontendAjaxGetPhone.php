<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Framework\Controller\AbstractAjaxController;
use Krugozor\Framework\Controller\DisableAuthorizationTrait;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\View;

class FrontendAjaxGetPhone extends AbstractAjaxController
{
    use DisableAuthorizationTrait;

    /**
     * @return View
     */
    public function run(): View
    {
        $this->getView('Ajax');

        $params = [
            'what' => 'id, advert_id_user, advert_phone, advert_main_phone',
            'where' => [
                'id = ?i' => [$this->getRequest()->getRequest('id', Request::SANITIZE_INT)]
            ]
        ];

        /** @var Advert $advert */
        $advert = $this->getMapper(AdvertMapper::class)->findModelByParams($params);

        if (!$advert->getId()) {
            $phone = null;
        } else {
            if (!$advert->getPhone() || $advert->getMainPhone()) {
                $params = array(
                    'what' => 'user_phone',
                    'where' => array('id = ?i' => array($advert->getIdUser()))
                );

                /** @var User $user */
                $user = $this->getMapper(UserMapper::class)->findModelByParams($params);

                $phone = $user->getPhone();
            } else {
                $phone = $advert->getPhone();
            }
        }

        $this->getView()->getStorage()->offsetSet('phone', $phone);

        return $this->getView();
    }
}