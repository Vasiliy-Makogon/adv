<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Service;

use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Mapper\AbstractMapper;
use Krugozor\Framework\Pagination\Manager as PaginationManager;
use Krugozor\Framework\Service\AbstractListService;

class CityListService extends AbstractListService
{
    /**
     * @param Request $request
     * @param AbstractMapper $mapper
     * @param PaginationManager $paginationManagerManager
     */
    public function __construct(
        Request $request,
        AbstractMapper $mapper,
        PaginationManager $paginationManagerManager
    ) {
        $this->order_options['id'] = 'user-city.id';
        $this->order_options['region'] = 'user-city.id_region';
        $this->order_options['name_ru'] = 'user-city.city_name_ru';
        $this->order_options['name_ru2'] = 'user-city.city_name_ru2';
        $this->order_options['name_ru3'] = 'user-city.city_name_ru3';
        $this->order_options['name_en'] = 'user-city.city_name_en';

        $this->default_order_options = array(
            'field_name' => 'weight',
            'sort_order' => 'DESC',
        );

        parent::__construct($request, $mapper, $paginationManagerManager);
    }

    /**
     * @inheritDoc
     */
    public function findList(): static
    {
        $this->processSortSearchCondition();
        $this->list = $this->mapper->findListForBackend($this->createParams());
        $this->paginationManager->setCount($this->mapper->getFoundRows());

        return $this;
    }

    /**
     * Установка параметров для условия WHERE.
     */
    private function processSortSearchCondition()
    {
        if ($id = $this->request->getRequest('id_region', Request::SANITIZE_INT)) {
            $this->sql_where_string_buffer[] = 'id_region = ?i';
            $this->sql_where_args_buffer[] = $id;
        }
    }
}