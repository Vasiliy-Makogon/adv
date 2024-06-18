<?php

namespace Krugozor\Framework\Module\Group\Mapper;

use Krugozor\Cover\CoverArray;
use Krugozor\Database\MySqlException;
use Krugozor\Database\Statement;
use Krugozor\Framework\Mapper\CommonMapper;
use Krugozor\Framework\Module\Group\Model\Group;

class AccessMapper extends CommonMapper
{
    /**
     * Возвращает коллекцию объектов доступа группы $group.
     *
     * @param Group $group
     * @return CoverArray
     */
    public function findByGroup(Group $group): CoverArray
    {
        $params = [
            'where' => ['id_group = ?i' => [$group->getId()]]
        ];

        return parent::findModelListByParams($params);
    }

    /**
     * @param Group $group
     * @return bool|Statement
     * @throws MySqlException
     */
    public function clearByGroup(Group $group): Statement|bool
    {
        return $this->getDb()->query(
            'DELETE FROM ?f WHERE `id_group` = ?i',
            $this->getTableName(),
            $group->getId()
        );
    }

    /**
     * Сохраняет доступы группы $group.
     *
     * @param Group $group
     * @return bool
     * @throws MySqlException
     */
    public function saveAccesses(Group $group): bool
    {
        if (!$group->getAccesses()->count()) {
            return false;
        }

        $this->clearByGroup($group);

        $sql = 'REPLACE INTO ?f (id_group, id_controller, access) VALUES ';
        $args = [];
        $args[] = $this->getTableName();

        foreach ($group->getAccesses() as $access) {
            $sql .= '(?a[?i, ?i, ?i]),';
            $args[] = [$group->getId(), $access->getIdController(), $access->getAccess()];
        }

        $sql = rtrim($sql, ', ');

        return $this->getDb()->queryArguments($sql, $args);
    }

    /**
     * Возвращает объект CoverArray, где индекс первого уровня вложенности - ключ модуля
     * а значение - объект CoverArray, ключ которого - ключ контроллера,
     * а значение - значение доступа группы $id_group к данному контроллеру - 1 или 0.
     *
     * Пример:
     * Array
     * (
     *     [User] => Array
     *         (
     *             [BackendMain] => 1
     *             [BackendEdit] => 1
     *             [BackendDelete] => 1
     *             [FrontendEdit] => 1
     *     ...
     * )
     *
     * @param int $id_group
     * @return CoverArray
     * @throws MySqlException
     */
    public function getGroupAccessByIdWithControllerNames(int $id_group): CoverArray
    {
        $sql = '
             SELECT ?f.`access`, `module`.`module_key`, `module-controller`.`controller_key`
             FROM `module`
             INNER JOIN `module-controller` ON `module`.`id` = `module-controller`.`controller_id_module`
             INNER JOIN ?f ON ?f.`id_controller` = `module-controller`.`id`
             INNER JOIN `group` ON `group`.`id` = ?f.`id_group`
             WHERE `group`.`id` = ?i';

        $res = $this->getDb()->query(
            $sql,
            $this->getTableName(),
            $this->getTableName(),
            $this->getTableName(),
            $this->getTableName(),
            $id_group
        );

        $accesses = new CoverArray();

        while ($data = $res->fetchAssoc()) {
            if (!isset($accesses[$data['module_key']])) {
                $accesses[$data['module_key']] = [];
            }

            $accesses[$data['module_key']][$data['controller_key']] = $data['access'];
        }

        return $accesses;
    }
}