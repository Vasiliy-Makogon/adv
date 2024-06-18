<?php

namespace Krugozor\Framework\Module\User\Mapper;

use Krugozor\Database\MySqlException;
use Krugozor\Database\Statement;
use Krugozor\Framework\Mapper\CommonMapper;
use Krugozor\Framework\Module\User\Model\InviteAnonymousUser;

class InviteAnonymousUserMapper extends CommonMapper
{
    /**
     * @param InviteAnonymousUser $object
     * @return bool|Statement
     * @throws MySqlException
     */
    public function insert(InviteAnonymousUser $object): Statement|bool
    {
        $sql = 'INSERT INTO ?f
                SET `unique_cookie_id` = "?s", `send_date` = now()
                ON DUPLICATE KEY UPDATE `send_date` = now()';

        return $this->getDb()->query($sql, $this->getTableName(), $object->getUniqueCookieId());
    }

    /**
     * Удаляет из таблицы запись по unique_cookie_id
     *
     * @param string $unique_cookie_id
     * @return static
     */
    public function deleteByUniqueCookieId(string $unique_cookie_id): static
    {
        $this->getMapperManager()->getMapper(InviteAnonymousUserMapper::class)->deleteByParams([
            'where' => ['unique_cookie_id = "?s"' => [$unique_cookie_id]]
        ]);

        return $this;
    }
}