<?php

namespace Krugozor\Framework\Module\Group\Mapper;

use Krugozor\Cover\CoverArray;
use Krugozor\Database\MySqlException;
use Krugozor\Framework\Mapper\CommonMapper;
use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Module\Group\Model\Group;
use Krugozor\Framework\Module\User\Mapper\UserMapper;

class GroupMapper extends CommonMapper
{
    /**
     * Сохраняет данные группы вместе с правами доступа и выполняет денормализацию
     * прав доступа с записью в поле `group_access` таблицы `group`.
     *
     * @param AbstractModel $object
     * @return AbstractModel
     * @throws MySqlException
     */
    public function saveModel(AbstractModel $object): AbstractModel
    {
        parent::saveModel($object);

        if ($object->getId()) {
            $this->getMapperManager()->getMapper(AccessMapper::class)->saveAccesses($object);
        }

        // Денормализация прав группы.
        $access = $this->getMapperManager()
            ->getMapper(AccessMapper::class)
            ->getGroupAccessByIdWithControllerNames($object->getId())
            ->getDataAsArray();

        $object->setAccess(serialize($access));

        parent::saveModel($object);

        return $object;
    }

    /**
     * Удаляет группу, её доступы и связывает пользователей,
     * закрепленных за этой группой, с группой "Пользователи".
     *
     * @param AbstractModel|int|string $objId
     * @return int
     */
    public function deleteModel(AbstractModel|int|string $objId): int
    {
        $group = is_object($objId) ? $objId : parent::findModelById($objId);
        $this->getMapperManager()->getMapper(UserMapper::class)->setDefaultGroupForUsersWithGroup($group);
        return parent::deleteModel($group);
    }

    /**
     * Находит все группы, за исключением группы гостей.
     *
     * @return CoverArray
     */
    public function findAllGroupsWithoutGuest(): CoverArray
    {
        return parent::findModelListByParams(['where' => 'group_alias <> "guest"']);
    }

    /**
     * Ищет группу по алиасу группы.
     *
     * @param string $group_alias алиас группы
     * @return Group
     */
    public function findGroupByAlias(string $group_alias): Group
    {
        $params = ['where' => ['group_alias = "?s"' => [$group_alias]]];
        return parent::findModelByParams($params);
    }
}