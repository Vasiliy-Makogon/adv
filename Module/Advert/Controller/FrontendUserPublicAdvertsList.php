<?php

namespace Krugozor\Framework\Module\Advert\Controller;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Controller\AbstractController;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Mapper\AdvertMapper;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\Advert\Service\FrontendUserAdvertsListService;
use Krugozor\Framework\Module\Category\Mapper\CategoryMapper;
use Krugozor\Framework\Module\NotFound\ShowNotFountTrait;
use Krugozor\Framework\Module\User\Mapper\UserMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Notification;
use Krugozor\Framework\Pagination\Adapter;
use Krugozor\Framework\View;

class FrontendUserPublicAdvertsList extends AbstractController
{
    use ShowNotFountTrait;

    /**
     * @return string[]
     */
    protected function langs(): array
    {
        return [
            'Common/FrontendGeneral',
            'Local/FrontendGeneral',
            $this->getRequest()->getVirtualControllerPath()
        ];
    }

    /**
     * @return Notification|View
     * @throws MySqlException
     */
    public function run(): Notification|View
    {
        $user_id = $this->getRequest()->getRequest('user', Request::SANITIZE_INT);
        /** @var User $user */
        $user = $this->getMapper(UserMapper::class)->findModelById($user_id);

        if (!$user->getId()) {
            return $this->showNotFoundPage();
        } else if (!$user->getActive()) {
            return $this->showForbiddenPage();
        }

        $userAdvertsCount = $this->getMapper(AdvertMapper::class)->findUserAdvertsCount($user->getId());
        if ($userAdvertsCount < Advert::MIN_ADVERTS_COUNT_FOR_SHOW_PROFILE) {
            return $this->showNotFoundPage();
        }

        $advertsListService = (new FrontendUserAdvertsListService(
            $this->getRequest(),
            $this->getMapper(AdvertMapper::class),
            Adapter::getManager($this->getRequest(), 50, 15)
        ))
            ->setCategoryMapper($this->getMapper(CategoryMapper::class))
            ->findList();

        $this->getView()->getStorage()->offsetSet('advertsListService', $advertsListService);

        $this->getView()->getStorage()->offsetSet(
            'userCategories',
            $this->getMapper(CategoryMapper::class)->loadPathAllUserCategories($user->getId())
        );

        $this->getView()->getStorage()->offsetSet('user', $user);
        $this->getView()->setCurrentUser($this->getCurrentUser());

        return $this->getView();
    }
}