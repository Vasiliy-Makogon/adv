<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Group\Service;

use Krugozor\Database\Statement;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Mapper\AbstractMapper;
use Krugozor\Framework\Service\AbstractListService;
use Krugozor\Framework\Pagination\Manager as PaginationManager;

class GroupListService extends AbstractListService
{
    /**
     * @param Request $request
     * @param AbstractMapper $mapper
     * @param PaginationManager $paginationManager
     */
    public function __construct(
        protected Request $request,
        protected AbstractMapper $mapper,
        protected PaginationManager $paginationManager
    ) {
        $this->order_options = [
            'name' => 'group_name',
            'active' => 'group_active'
        ];

        parent::__construct($request, $mapper, $paginationManager);
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
                return $this->mapper->result2objects($statement);
            }
        );

        $this->findListConditions(true);

        $totalRows = $this->mapper->callableExecuteByParams(
            $this->createParams(),
            function (Statement $statement) {
                return (int) $statement->getOne();
            }
        );

        $this->paginationManager->setCount($totalRows);

        return $this;
    }

    /**
     * @param bool $queryGetTotalRows
     */
    private function findListConditions(bool $queryGetTotalRows = false): void
    {
        $this->sql_order_string_buffer[$this->getFieldName()] = $this->getOrder();
        $this->sql_what_string_buffer[] = $queryGetTotalRows ? 'COUNT(*)' : '`group`.*';
        $this->sql_from_string_buffer[] = '`group`';

        if (!$queryGetTotalRows) {
            $this->sql_limit_args_buffer = [
                $this->paginationManager->getStartLimit(),
                $this->paginationManager->getStopLimit(),
            ];
        }
    }
}