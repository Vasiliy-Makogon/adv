<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Advert\Service;

use Krugozor\Cover\CoverArray;
use Krugozor\Database\Statement;
use Krugozor\Framework\Http\Request;
use Krugozor\Framework\Mapper\AbstractMapper;
use Krugozor\Framework\Module\Advert\Service\Trait\MemcacheTrait;
use Krugozor\Framework\Pagination\Manager as PaginationManager;
use Krugozor\Framework\Service\AbstractListService;

/**
 * Сервис выборки объявлений на главной странице.
 */
class FrontendAdvertsIndexListService extends AbstractListService
{
    use MemcacheTrait;

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
        $this->default_order_options = [
            'field_name' => 'advert.id',
            'sort_order' => 'DESC'
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
                $result = new CoverArray();

                while ($data = $statement->fetchAssoc()) {
                    if ($advertData = $this->findByIdForViewThroughCache($data['id'])->getFirst()) {
                        $result->append($advertData);
                    }
                }

                return $result;
            }
        );

        return $this;
    }

    /**
     * Общие условия поиска для обоих запросов.
     */
    private function findListConditions(): void
    {
        $this->sql_what_string_buffer[] = '`advert`.`id`';
        $this->sql_from_string_buffer[] = 'advert';

        $this->sql_where_string_buffer[] = '`advert`.`advert_thumbnail_count` > 0';
        $this->sql_where_string_buffer[] = '`advert`.`advert_active` = 1';
        $this->sql_where_string_buffer[] = '`advert`.`advert_payment` = 1';

        $this->sql_order_string_buffer[$this->getFieldName()] = $this->getOrder();

        $this->sql_limit_args_buffer = [
            $this->paginationManager->getStartLimit(),
            $this->paginationManager->getStopLimit(),
        ];
    }
}