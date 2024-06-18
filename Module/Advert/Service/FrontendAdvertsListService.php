<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Advert\Service;

use Krugozor\Cover\CoverArray;
use Krugozor\Database\Statement;
use Krugozor\Framework\Module\Advert\Service\Trait\MemcacheTrait;
use Krugozor\Framework\Module\Advert\Type\AdvertType;
use Krugozor\Framework\Module\Category\Model\Category;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Module\Advert\Model\Advert;
use Krugozor\Framework\Module\User\Model\City;
use Krugozor\Framework\Module\User\Model\Country;
use Krugozor\Framework\Module\User\Model\Region;
use Krugozor\Framework\Module\User\Model\AbstractTerritory;
use Krugozor\Framework\Service\AbstractListService;

/**
 * Универсальный сервис выборки объявлений на основании регионов, категорий и поисков.
 */
class FrontendAdvertsListService extends AbstractListService
{
    use MemcacheTrait;

    /**
     * true, если для подсчёта строк нужно использовать выражение COUNT(*),
     * а не брать данные по кол-ву объявлений из таблиц региональной статистики.
     *
     * @var bool
     */
    protected bool $search_is_used = false;

    /**
     * @var Category|null
     */
    protected ?Category $category = null;

    /**
     * @var Country|null
     */
    protected ?Country $territoryCountry = null;

    /**
     * @var Region|null
     */
    protected ?Region $territoryRegion = null;

    /**
     * @var City|null
     */
    protected ?City $territoryCity = null;

    /**
     * Указатель на текущую территорию.
     *
     * @var AbstractTerritory|null
     */
    protected ?AbstractTerritory $currentTerritory = null;

    /**
     * @param Category $category
     * @return $this
     */
    public function setCategory(Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @param Country $territoryCountry
     * @return static
     */
    public function setTerritoryCountry(Country $territoryCountry): static
    {
        $this->territoryCountry = $territoryCountry;

        return $this;
    }

    /**
     * @param Region $territoryRegion
     * @return static
     */
    public function setTerritoryRegion(Region $territoryRegion): static
    {
        $this->territoryRegion = $territoryRegion;

        return $this;
    }

    /**
     * @param City $territoryCity
     * @return static
     */
    public function setTerritoryCity(City $territoryCity): static
    {
        $this->territoryCity = $territoryCity;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function findList(): static
    {
        $this->findListConditions();

        $this->list = $this->mapper->callableExecuteByParams(
            $this->createParams(true),
            function (Statement $statement) {
                $result = new CoverArray();

                while ($data = $statement->fetchAssoc()) {
                    if ($advertData = $this->findByIdForViewThroughCache($data['id'])->getFirst()) {
                        $advertData->get('advert')->setScore($data['advert__score'] ?? 0);
                        $result->append($advertData);
                    }
                }

                return $result;
            }
        );

        $this->findListConditions(true);

        $totalRows = $this->search_is_used
            ? $this->mapper->callableExecuteByParams(
                $this->createParams(),
                function (Statement $statement) {
                    return (int) $statement->getOne();
                }
            ) : $this->currentTerritory->getAdvertCount($this->category);

        $this->paginationManager->setCount($totalRows);

        return $this;
    }

    /**
     * Общие условия поиска для обоих запросов.
     *
     * @param bool $queryGetTotalRows
     */
    private function findListConditions(bool $queryGetTotalRows = false): void
    {
        // Категория имеющая потомков
        if ($this->category && $this->category->findChildsIds()->count()) {

            // Используется поиск
            $searchKeyword = $this->request->getRequest('keyword', Request::SANITIZE_STRING_FULLTEXT);
            $searchKeyword = $searchKeyword && mb_strlen($searchKeyword) < 50 ? $searchKeyword : '';
            if ($searchKeyword) {
                $this->search_is_used = true;
            }

            if ($queryGetTotalRows) {
                $this->sql_what_string_buffer[] = 'COUNT(*)';
            } else {
                $this->sql_what_string_buffer[] = '`advert`.`id`';
                if ($searchKeyword) {
                    $this->sql_what_string_buffer[] = 'ROUND(MATCH (`advert`.`advert_header`, `advert`.`advert_text`) AGAINST ("?s"), 2) as `advert__score`';
                    $this->sql_what_args_buffer[] = $searchKeyword;
                }
            }

            $this->sql_from_string_buffer[] = '`category-category_all_childs_with_parent` AS `c`';
            $this->sql_join_string_buffer[] = 'STRAIGHT_JOIN `advert` ON `advert`.`advert_category` = `c`.`child_id`';

            $this->sql_where_string_buffer[] = '?f = ?i';
            $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('active');
            $this->sql_where_args_buffer[] = 1;

            $this->sql_where_string_buffer[] = '?f = ?i';
            $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('payment');
            $this->sql_where_args_buffer[] = 1;

            $this->sql_where_string_buffer[] = '`category_id` = ?i';
            $this->sql_where_args_buffer[] = $this->category->getId();

            if ($this->territoryCountry) {
                $this->sql_where_string_buffer[] = '?f = ?i';
                $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('place_country');
                $this->sql_where_args_buffer[] = $this->territoryCountry->getId();
                $this->currentTerritory = $this->territoryCountry;
            }

            if ($this->territoryRegion) {
                $this->sql_where_string_buffer[] = '?f = ?i';
                $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('place_region');
                $this->sql_where_args_buffer[] = $this->territoryRegion->getId();
                $this->currentTerritory = $this->territoryRegion;
            }

            if ($this->territoryCity) {
                $this->sql_where_string_buffer[] = '?f = ?i';
                $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('place_city');
                $this->sql_where_args_buffer[] = $this->territoryCity->getId();
                $this->currentTerritory = $this->territoryCity;
            }

            // Используется поиск
            $user = $this->request->getRequest('user', Request::SANITIZE_INT);
            if ($user) {
                $this->search_is_used = true;
                $this->sql_where_string_buffer[] = '?f = ?i';
                $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('id_user');
                $this->sql_where_args_buffer[] = $user;
            }

            // Используется поиск
            $type = $this->request->getRequest('type', Request::SANITIZE_STRING);
            if ($type && in_array($type, array_keys(AdvertType::ADVERT_TYPES))) {
                $this->search_is_used = true;
                $this->sql_where_string_buffer[] = '?f = "?s"';
                $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('type');
                $this->sql_where_args_buffer[] = $type;
            }

            if ($searchKeyword) {
                $this->sql_where_string_buffer[] = 'MATCH (`advert`.`advert_header`, `advert`.`advert_text`) AGAINST ("?s")';
                $this->sql_where_args_buffer[] = $searchKeyword;
            }

            if (!$queryGetTotalRows) {
                $this->sql_limit_args_buffer = [
                    $this->paginationManager->getStartLimit(),
                    $this->paginationManager->getStopLimit(),
                ];

                if ($searchKeyword) {
                    $this->sql_order_string_buffer['advert__score'] = 'DESC';
                }
                $this->sql_order_string_buffer['advert.advert_vip_date'] = 'DESC';
                $this->sql_order_string_buffer['advert.advert_create_date'] = 'DESC';
            }

        // Конечная категория
        } else if ($this->category) {

            // Используется поиск
            $searchKeyword = $this->request->getRequest('keyword', Request::SANITIZE_STRING_FULLTEXT);
            $searchKeyword = $searchKeyword && mb_strlen($searchKeyword) < 50 ? $searchKeyword : '';
            if ($searchKeyword) {
                $this->search_is_used = true;
            }

            if ($queryGetTotalRows) {
                $this->sql_what_string_buffer[] = 'COUNT(*)';
            } else {
                $this->sql_what_string_buffer[] = '`advert`.`id`';
                if ($searchKeyword) {
                    $this->sql_what_string_buffer[] = 'ROUND(MATCH (`advert`.`advert_header`, `advert`.`advert_text`) AGAINST ("?s"), 2) as `advert__score`';
                    $this->sql_what_args_buffer[] = $searchKeyword;
                }
            }

            $this->sql_from_string_buffer[] = 'advert';

            $this->sql_where_string_buffer[] = '?f = ?i';
            $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('active');
            $this->sql_where_args_buffer[] = 1;

            $this->sql_where_string_buffer[] = '?f = ?i';
            $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('payment');
            $this->sql_where_args_buffer[] = 1;

            $this->sql_where_string_buffer[] = '?f = ?i';
            $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('category');
            $this->sql_where_args_buffer[] = $this->category->getId();

            if ($this->territoryCountry) {
                $this->sql_where_string_buffer[] = '?f = ?i';
                $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('place_country');
                $this->sql_where_args_buffer[] = $this->territoryCountry->getId();
                $this->currentTerritory = $this->territoryCountry;
            }

            if ($this->territoryRegion) {
                $this->sql_where_string_buffer[] = '?f = ?i';
                $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('place_region');
                $this->sql_where_args_buffer[] = $this->territoryRegion->getId();
                $this->currentTerritory = $this->territoryRegion;
            }

            if ($this->territoryCity) {
                $this->sql_where_string_buffer[] = '?f = ?i';
                $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('place_city');
                $this->sql_where_args_buffer[] = $this->territoryCity->getId();
                $this->currentTerritory = $this->territoryCity;
            }

            // Используется поиск
            $user = $this->request->getRequest('user', Request::SANITIZE_INT);
            if ($user) {
                $this->search_is_used = true;
                $this->sql_where_string_buffer[] = '?f = ?i';
                $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('id_user');
                $this->sql_where_args_buffer[] = $user;
            }

            // Используется поиск
            $type = $this->request->getRequest('type', Request::SANITIZE_STRING);
            if ($type && in_array($type, array_keys(AdvertType::ADVERT_TYPES))) {
                $this->search_is_used = true;
                $this->sql_where_string_buffer[] = '?f = "?s"';
                $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('type');
                $this->sql_where_args_buffer[] = $type;
            }

            if ($searchKeyword) {
                $this->sql_where_string_buffer[] = 'MATCH (`advert`.`advert_header`, `advert`.`advert_text`) AGAINST ("?s")';
                $this->sql_where_args_buffer[] = $searchKeyword;
            }

            if (!$queryGetTotalRows) {
                $this->sql_limit_args_buffer = [
                    $this->paginationManager->getStartLimit(),
                    $this->paginationManager->getStopLimit(),
                ];

                if ($searchKeyword) {
                    $this->sql_order_string_buffer['advert__score'] = 'DESC';
                }

                $this->sql_order_string_buffer['advert.advert_vip_date'] = 'DESC';
                $this->sql_order_string_buffer['advert.advert_create_date'] = 'DESC';
            }

        // Регион без категории
        } else {

            $this->sql_what_string_buffer[] = '`advert`.`id`';
            $this->sql_from_string_buffer[] = '`advert` USE INDEX(`vip_date,create_date`, `active,payment,country,region,city`)';

            $this->sql_where_string_buffer[] = '?f = ?i';
            $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('active');
            $this->sql_where_args_buffer[] = 1;

            $this->sql_where_string_buffer[] = '?f = ?i';
            $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('payment');
            $this->sql_where_args_buffer[] = 1;

            if ($this->territoryCountry) {
                $this->sql_where_string_buffer[] = '?f = ?i';
                $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('place_country');
                $this->sql_where_args_buffer[] = $this->territoryCountry->getId();
                $this->currentTerritory = $this->territoryCountry;
            }

            if ($this->territoryRegion) {
                $this->sql_where_string_buffer[] = '?f = ?i';
                $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('place_region');
                $this->sql_where_args_buffer[] = $this->territoryRegion->getId();
                $this->currentTerritory = $this->territoryRegion;
            }

            if ($this->territoryCity) {
                $this->sql_where_string_buffer[] = '?f = ?i';
                $this->sql_where_args_buffer[] = Advert::getPropertyFieldName('place_city');
                $this->sql_where_args_buffer[] = $this->territoryCity->getId();
                $this->currentTerritory = $this->territoryCity;
            }

            $this->sql_limit_args_buffer = [
                $this->paginationManager->getStartLimit(),
                $this->paginationManager->getStopLimit(),
            ];

            $this->sql_order_string_buffer['advert.advert_vip_date'] = 'DESC';
            $this->sql_order_string_buffer['advert.advert_create_date'] = 'DESC';
        }
    }
}