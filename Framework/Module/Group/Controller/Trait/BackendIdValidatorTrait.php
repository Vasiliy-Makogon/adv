<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Group\Controller\Trait;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Group\Mapper\GroupMapper;
use Krugozor\Framework\Module\Group\Model\Group;
use Krugozor\Framework\Notification;

trait BackendIdValidatorTrait
{
    /** @var Group|null */
    protected ?Group $groupModel = null;

    /**
     * @return Notification|null
     * @throws MySqlException
     */
    protected function checkIdOnValid(): ?Notification
    {
        if ($id = $this->getRequest()->getRequest('id', Request::SANITIZE_INT)) {
            $this->groupModel = $this->getMapper(GroupMapper::class)->findModelById($id);

            if (!$this->groupModel->getId()) {
                return $this->createNotification(Notification::TYPE_ALERT)
                    ->setMessage($this->getView()->getLang()->get('notification.message.element_does_not_exist'))
                    ->addParam('id', $this->getRequest()->getRequest('id'))
                    ->setRedirectUrl('/group/backend-main/')
                    ->run();
            }
        }

        return null;
    }
}