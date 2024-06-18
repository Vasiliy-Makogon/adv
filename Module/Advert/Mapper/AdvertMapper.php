<?php

namespace Krugozor\Framework\Module\Advert\Mapper;

use Krugozor\Cover\CoverArray;
use Krugozor\Database\MySqlException;
use Krugozor\Framework\Context;
use Krugozor\Framework\Mapper\CommonMapper;
use Krugozor\Framework\Mapper\MapperParamsCreator;
use Krugozor\Framework\Model\AbstractModel;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\Advert\Service\FrontendAdvertsListService;
use Krugozor\Framework\Module\User\Model\User;

class AdvertMapper extends CommonMapper
{
    /** @var string */
    protected const SQL_COMMON_SELECT_CONDITION = '
            AND `advert_active` = 1
            AND `advert`.`advert_payment` = 1';

    /**
     * Возвращает кол-во объявлений пользователя $user_id
     *
     * @param int $user_id
     * @return int
     * @throws MySqlException
     */
    public function findUserAdvertsCount(int $user_id): int
    {
        return (int) $this->getDb()->query(
            'SELECT COUNT(*) FROM `advert` WHERE `advert_id_user` = ?i', $user_id
        )->getOne();
    }

    /**
     * @param User $user
     * @return CoverArray
     */
    public function findModelListByUser(User $user): CoverArray
    {
        return parent::findModelListByParams([
            'where' => [
                '`advert_id_user` = ?i' => [$user->getId()]
            ]
        ]);
    }

    /**
     * Очищение vip-дат объявлений с истекшим сроком годности.
     * Метод для cron.
     *
     * @return int количество задействованных рядов
     * @throws MySqlException
     */
    public function cleanNonActualVipDates(): int
    {
        $this->getDb()->query('UPDATE `advert` SET `advert_vip_date` = NULL WHERE `advert_vip_date` < NOW()');

        return $this->getDb()->getAffectedRows();
    }

    /**
     * Удаляет объявления анонимных пользователей за последние $interval месяцев.
     * Метод для cron.
     *
     * @param int $interval за сколько месяцев удаляем объявления
     * @return array список заголовков удаленных объявлений
     */
    public function deleteNonActualGuestAdverts(int $interval = 12): array
    {
        $sql = '
            SELECT * 
            FROM advert 
            WHERE 
                advert_create_date < now() - INTERVAL ?i MONTH 
            AND (
                advert_edit_date IS NULL 
                OR
                advert_edit_date < now() - INTERVAL ?i MONTH
            )
            AND advert_id_user = -1
            AND advert_was_moderated = 0
            ORDER BY id ASC 
            LIMIT 30';

        $list = $this->findModelListBySql($sql, $interval, $interval);
        $titles = [];

        /** @var Advert $advert */
        foreach ($list as $advert) {
            $result = $advert->deleteCache()->delete();
            $titles[] = sprintf("%s - %s: %s", $advert->getId(), $advert->getHeader(), $result);
        }

        return $titles;
    }

    /**
     * Если пользователь перед регистрацией подавал на сайт объявления от лица гостя, и хэш-код
     * в поле advert_unique_user_cookie_id совпадает с параметром $user->unique_cookie_id, то
     * назначить всем объявлениям гостя ID данного пользователя.
     *
     * @param User $user
     * @return int
     * @throws MySqlException
     */
    public function attachGuestUserAdverts(User $user): int
    {
        $sql = 'UPDATE ?f SET ?f = ?i WHERE ?f = -1 AND ?f = "?s"';

        $this->getDb()->query($sql,
            $this->getTableName(),
            Advert::getPropertyFieldName('id_user'), $user->getId(),
            Advert::getPropertyFieldName('id_user'),
            Advert::getPropertyFieldName('unique_user_cookie_id'), $user->getUniqueCookieId()
        );

        return $this->getDb()->getAffectedRows();
    }

    /**
     * Обновляет счётчик просмотров объявления на 1.
     *
     * @param Advert $advert
     * @return int
     * @throws MySqlException
     */
    public function incrementViewCount(Advert $advert): int
    {
        $sql = 'UPDATE ?f SET ?f = ?f + 1 WHERE `id` = ?i LIMIT 1';

        $this->getDb()->query($sql,
            $this->getTableName(),
            Advert::getPropertyFieldName('view_count'),
            Advert::getPropertyFieldName('view_count'),
            $advert->getId()
        );

        return $this->getDb()->getAffectedRows();
    }

    /**
     * @param array $params
     * @return CoverArray
     */
    public function findByIdForView($id): CoverArray
    {
        $sql = '/* ?f */
                SELECT `advert`.*, `category`.*, `user`.*, `user-country`.*, `user-region`.*, `user-city`.*
                FROM `advert`
                INNER JOIN `category` ON `advert`.`advert_category` = `category`.`id`
                INNER JOIN `user` ON `advert`.`advert_id_user` = `user`.`id`
                INNER JOIN `user-country` ON `advert`.`advert_place_country` = `user-country`.`id`
                INNER JOIN `user-region` ON `advert`.`advert_place_region` = `user-region`.`id`
                INNER JOIN `user-city` ON `advert`.`advert_place_city` = `user-city`.`id`
                WHERE `advert`.`id` = ?i';

        $advertData = parent::result2objects(
            $this->getDb()->query($sql, __METHOD__, $id)
        );

        return $advertData;
    }

    /**
     * @param User|null $user
     * @return Advert
     * @see CommonMapper::createModel()
     */
    public function createModel(?User $user = null): Advert
    {
        /** @var Advert $advert */
        $advert = parent::createModel();

        if ($user) {
            $advert->setIdUser($user->getId());
            $advert->setPlaceCountry($user->getCountry());
            $advert->setPlaceRegion($user->getRegion());
            $advert->setPlaceCity($user->getCity());
        }

        return $advert;
    }

    /**
     * Обновляет дату создания объявления $advert на текущее время -1 секунда.
     * Обновление произойдет только в том случае, если время создания объявления не менее $hour часа назад.
     *
     * @param Advert $advert
     * @param int $hour час времени
     * @return int количество задействованных (обновленных) в запросе строк
     * @throws MySqlException
     */
    public function updateDateCreate(Advert $advert, $hour = 1): int
    {
        $sql = 'UPDATE ?f
                SET ?f = DATE_SUB(NOW(), INTERVAL 1 SECOND), ?f = now()
                WHERE `id` = ?i
                AND NOW() > DATE_ADD(?f, INTERVAL ?i HOUR)';

        $this->getDb()->query($sql,
            $this->getTableName(),
            Advert::getPropertyFieldName('create_date'),
            Advert::getPropertyFieldName('edit_date'),
            $advert->getId(),
            Advert::getPropertyFieldName('create_date'),
            $hour
        );

        $advert->deleteCache();

        return $this->getDb()->getAffectedRows();
    }

    /**
     * Возвращает предыдущее объявление от текущего.
     *
     * @param Advert $advert
     * @return Advert
     */
    public function findPrevAdvert(Advert $advert): Advert
    {
        $params = array(
            'what' => 'advert.*',
            'join' => array(array('inner join', 'user', 'user.id = advert.advert_id_user')),
            'where' => array(
                'advert.id < ?i' => array($advert->getId()),
                'AND' => [],
                'advert_category = ?i' => array($advert->getCategory()),
                'AND' => [],
                self::SQL_COMMON_SELECT_CONDITION => []
            ),
            'order' => array('advert.id' => 'DESC'),
            'limit' => array('start' => 0, 'stop' => 1)
        );

        return parent::findModelByParams($params);
    }

    /**
     * Возвращает следующее объявление от текущего.
     *
     * @param Advert $advert
     * @return Advert
     */
    public function findNextAdvert(Advert $advert): Advert
    {
        $params = array(
            'what' => 'advert.*',
            'join' => array(array('inner join', 'user', 'user.id = advert.advert_id_user')),
            'where' => array(
                'advert.id > ?i' => array($advert->getId()),
                'AND' => [],
                'advert_category = ?i' => array($advert->getCategory()),
                'AND' => [],
                self::SQL_COMMON_SELECT_CONDITION => []
            ),
            'order' => array('advert.id' => 'ASC'),
            'limit' => array('start' => 0, 'stop' => 1)
        );

        return parent::findModelByParams($params);
    }

    /**
     * Установка статуса "оплачено" для тех объявлений, которые не оплатили своё объявление
     * в последние Advert::PAID_TOLERANCE_DAYS дней.
     * Метод для cron. Не используется.
     *
     * @return int
     * @throws MySqlException
     */
    public function setPaidTolerance(): int
    {
        $sql = 'UPDATE ?f
                SET `advert_payment` = 1
                WHERE `advert_payment` = 0
                AND `advert_category` IN (SELECT `id` FROM `category` WHERE `category_paid` = 1)
                AND `advert_create_date` < NOW() - INTERVAL ?i DAY';

        $this->getDb()->query($sql, $this->getTableName(), Advert::PAID_TOLERANCE_DAYS);

        return $this->getDb()->getAffectedRows();
    }
}