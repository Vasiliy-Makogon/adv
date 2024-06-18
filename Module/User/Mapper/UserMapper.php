<?php

namespace Krugozor\Framework\Module\User\Mapper;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Mapper\CommonMapper;
use Krugozor\Framework\Module\User\Model\User;
use Krugozor\Framework\Module\Group\Model\Group;

class UserMapper extends CommonMapper
{
    /**
     * Возвращает доменный объект находя его по логину.
     *
     * @param string $login
     * @return User
     */
    public function findByLogin(string $login): User
    {
        return parent::findModelByParams([
            'where' => ['`user_login` = "?s"' => [$login]]
        ]);
    }

    /**
     * Возвращает доменный объект находя его по email.
     *
     * @param string $email
     * @return User
     */
    public function findByEmail(string $email): User
    {
        return parent::findModelByParams([
            'where' => ['`user_email` = "?s"' => [$email]]
        ]);
    }

    /**
     * Возвращает доменный объект находя его по логину или email.
     *
     * @param string $login
     * @param string $email
     * @return User
     */
    public function findByLoginOrEmail(string $login, string $email): User
    {
        return parent::findModelByParams([
            'where' => ['`user_login` = "?s" OR `user_email` = "?s"' => [$login, $email]]
        ]);
    }

    /**
     * Возвращает доменный объект находя его по $login и $password.
     * Используется при авторизации из POST.
     *
     * @param string $login логин из POST-запроса
     * @param string $password пароль из POST-запроса
     * @return User
     */
    public function findByLoginPassword(string $login, string $password): User
    {
        $params = [
            'where' => [
                '`user_login` = "?s" AND MD5(CONCAT("?s", `user_salt`)) = `user_password`' => [$login, $password]
            ]
        ];

        return parent::findModelByParams($params);
    }

    /**
     * Устанавливает для пользователей группы с
     * идентификатором ID группу по умолчанию (user).
     *
     * @param Group $group
     * @return bool
     * @throws MySqlException
     */
    public function setDefaultGroupForUsersWithGroup(Group $group): bool
    {
        $sql = '
            UPDATE ?f 
            SET `user_group` = (SELECT `id` FROM `group` WHERE `group_alias` = "user")
            WHERE `user_group` = ?i';

        return $this->getDb()->query($sql, $this->getTableName(), $group->getId());
    }
}