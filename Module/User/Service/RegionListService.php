<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Service;

use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Mapper\AbstractMapper;
use Krugozor\Framework\Pagination\Manager as PaginationManager;
use Krugozor\Framework\Service\AbstractListService;

class RegionListService extends AbstractListService
{
    /**
     * @param Request $request
     * @param AbstractMapper $mapper
     * @param PaginationManager $paginationManager
     */
    public function __construct(
        Request $request,
        AbstractMapper $mapper,
        PaginationManager $paginationManager
    ) {
        $this->order_options['id'] = 'user-region.id';
        $this->order_options['country'] = 'user-region.id_country';
        $this->order_options['weight'] = 'user-region.weight';
        $this->order_options['name_ru'] = 'user-region.region_name_ru';
        $this->order_options['name_ru2'] = 'user-region.region_name_ru2';
        $this->order_options['name_en'] = 'user-region.region_name_en';

        $this->default_order_options = array(
            'field_name' => 'weight',
            'sort_order' => 'DESC',
        );

        parent::__construct($request, $mapper, $paginationManager);
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
        if ($id = $this->request->getRequest('id_country', Request::SANITIZE_INT)) {
            $this->sql_where_string_buffer[] = 'id_country = ?i';
            $this->sql_where_args_buffer[] = $id;
        }
        if ($id = $this->request->getRequest('id', Request::SANITIZE_INT)) {
            $this->sql_where_string_buffer[] = 'id = ?i';
            $this->sql_where_args_buffer[] = $id;
        }
    }
}