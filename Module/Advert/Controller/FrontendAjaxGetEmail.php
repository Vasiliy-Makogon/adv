<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Framework\Controller\AbstractAjaxController;
use Krugozor\Framework\Controller\DisableAuthorizationTrait;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Type\Email;
use Krugozor\Framework\View;

class FrontendAjaxGetEmail extends AbstractAjaxController
{
    use DisableAuthorizationTrait;

    /**
     * @return View
     */
    public function run(): View
    {
        $this->getView('Ajax');

        $params = [
            'what' => 'id, advert_id_user, advert_email, advert_main_email',
            'where' => [
                'id = ?i' => [$this->getRequest()->getRequest('id', Request::SANITIZE_INT)]
            ]
        ];

        /** @var Advert $advert */
        $advert = $this->getMapper(AdvertMapper::class)->findModelByParams($params);

        if (!$advert->getId()) {
            $email = new Email(null);
        } else {
            if (!$advert->getEmail()->getValue() || $advert->getMainEmail()) {
                $params = array(
                    'what' => 'user_email',
                    'where' => array('id = ?i' => array($advert->getIdUser()))
                );

                /** @var User $user */
                $user = $this->getMapper(UserMapper::class)->findModelByParams($params);

                $email = $user->getEmail();
            } else {
                $email = $advert->getEmail();
            }

            if ($email->getMailHashForAccessView() !== $this->getRequest()->getRequest('hash')) {
                $email = new Email(null);
            }
        }

        $this->getView()->getStorage()->offsetSet('email', $email->getValue());

        return $this->getView();
    }
}