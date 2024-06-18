<?php

namespace Krugozor\Framework\Module\Advert\Mapper;

use Krugozor\Database\MySqlException;
use Krugozor\Database\Statement;
use Krugozor\Framework\Mapper\CommonMapper;
use Krugozor\Framework\Module\Advert\Model\Thumbnail;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Cover\CoverArray;

class ThumbnailMapper extends CommonMapper
{
    /**
     * Связывает запись об изображении $thumbnail с объявлением $advert.
     *
     * @param Thumbnail $thumbnail
     * @param Advert $advert
     * @return bool|Statement
     * @throws MySqlException
     */
    public function link(Thumbnail $thumbnail, Advert $advert): Statement|bool
    {
        $sql = 'UPDATE ?f SET ?f = ?i WHERE id = ?i AND ?f IS NULL LIMIT 1';

        return $this->getDb()->query($sql,
            $this->getTableName(),
            Thumbnail::getPropertyFieldName('id_advert'),
            $advert->getId(),
            $thumbnail->getId(),
            Thumbnail::getPropertyFieldName('id_advert')
        );
    }

    /**
     * Разрывает связь между изображением и объявлением, к которому прикреплено изображение.
     * Далее изображение удаляет cron. См. метод @see getThumbnailsNotRelatedToAdverts()
     *
     * @param Thumbnail $thumbnail
     * @return int|bool кол-во затронутых рядов или false при ошибке
     */
    public function unlink(Thumbnail $thumbnail): int|bool
    {
        try {
            $sql = 'UPDATE ?f SET ?f = NULL WHERE `id` = ?i LIMIT 1';

            $this->getDb()->query($sql,
                $this->getTableName(),
                Thumbnail::getPropertyFieldName('id_advert'),
                $thumbnail->getId()
            );

            return $this->getDb()->getAffectedRows();
        } catch (MySqlException $e) {
            error_log($e->getMessage(), 0);
            return false;
        }
    }

    /**
     * Возвращает все изображения объявления $advert.
     *
     * @param Advert $advert
     * @return CoverArray
     * @todo: add FORCE INDEX (`__id_advert,file_date`)
     */
    public function findByAdvert(Advert $advert)
    {
        $params = array(
            'where' => array('id_advert = ?i' => array($advert->getId())),
            'order' => array('file_date' => 'ASC')
        );

        return parent::findModelListByParams($params);
    }

    /**
     * Возвращает список объектов изображений, не привязанных к объявлениям (NULL в поле `id_advert`).
     * Метод для cron.
     *
     * @param int $period_minutes период в минутах от текущей даты
     * @param int $count кол-во удаляемых изображений
     * @return CoverArray
     */
    public function getThumbnailsNotRelatedToAdverts(int $period_minutes = 60, int $count = 50): CoverArray
    {
        $sql = 'SELECT * FROM ?f
                WHERE ?f IS NULL
                AND ?f < (NOW() - INTERVAL ?i MINUTE)
                ORDER BY ?f ASC LIMIT ?i';

        return parent::findModelListBySql($sql,
            $this->getTableName(),
            Thumbnail::getPropertyFieldName('id_advert'),
            Thumbnail::getPropertyFieldName('file_date'),
            $period_minutes,
            Thumbnail::getPropertyFieldName('id'),
            $count
        );
    }

    /**
     * Возвращает список объектов изображений, которые привязаны к несуществующим объявлениям, т.е.
     * записи объявления нет, а поле `id_advert` в таблицы `advert-thumbnail` содержит значение - идентификатор
     * несуществующего объявления.
     * Эта ситуация крайне нестандартная, т.к. при удалении объявления триггер на таблице `advert`
     * ставит в null поле `id_advert` в таблице `advert-thumbnail` (см. триггер `advert-delete` на таблице `advert`)
     * Метод для cron.
     *
     * @return CoverArray
     */
    public function getThumbnailsRelatedToNonExistsAdverts()
    {
        $sql = 'SELECT t.*
                FROM `advert-thumbnail` t
                LEFT JOIN `advert` a ON a.id = t.id_advert
                WHERE a.id IS NULL and t.id_advert IS NOT NULL';

        return parent::findModelListBySql($sql);
    }
}