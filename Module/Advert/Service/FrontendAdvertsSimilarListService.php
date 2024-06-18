<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Advert\Service;

use Krugozor\Cover\CoverArray;
use Krugozor\Database\Statement;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\Advert\Service\Trait\MemcacheTrait;
use Krugozor\Framework\Service\AbstractListService;

/**
 * Сервис выборки "похожих" объявлений.
 */
class FrontendAdvertsSimilarListService extends AbstractListService
{
    use MemcacheTrait;

    /** @var Advert|null */
    protected ?Advert $advert = null;

    /**
     * @param Advert $advert
     * @return static
     */
    public function setAdvert(Advert $advert): static
    {
        $this->advert = $advert;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function findList(): static
    {
        $this->list = $this->mapper->callableExecuteBySql(
            function (Statement $statement) {
                $result = new CoverArray();

                while ($data = $statement->fetchAssoc()) {
                    if ($advertData = $this->findByIdForViewThroughCache($data['id'])->getFirst()) {
                        $result->append($advertData);
                    }
                }

                return $result;
            },
            static::getSql(),
            [
                $this->advert->getCategory(),
                $this->advert->getPlaceCountry(),
                $this->advert->getPlaceRegion(),
                $this->advert->getPlaceCity(),
                $this->advert->getId(),
                $this->advert->getCategory(),
                $this->advert->getPlaceCountry(),
                $this->advert->getPlaceRegion(),
                $this->advert->getPlaceCity(),
                $this->advert->getId()
            ]
        );

        return $this;
    }

    /**
     * @return string
     */
    protected static function getSql(): string
    {
        return '
            SELECT * 
            FROM ((
                SELECT `id` FROM `advert`
                WHERE `advert_active` = 1 AND `advert_payment` = 1 AND `advert_category` = ?i
                AND `advert_place_country` = ?i AND `advert_place_region` = ?i AND `advert_place_city` = ?i AND `id` > ?i
                ORDER BY `id` DESC LIMIT 5
            ) UNION (
                SELECT `id` FROM `advert` 
                WHERE `advert_active` = 1 AND `advert_payment` = 1 AND `advert_category` = ?i
                AND `advert_place_country` = ?i AND `advert_place_region` = ?i AND `advert_place_city` = ?i AND `id` < ?i
                ORDER BY `id` DESC LIMIT 5
            )) `t` LIMIT 5';
    }
}