<?php

declare(strict_types=1);

namespace Krugozor\Framework\Pagination;

use Krugozor\Framework\Http\Request;

class Adapter
{
    /**
     * @param Request $request
     * @param int|string $limit
     * @param int|string $link_count
     * @param string $page_var_name
     * @param string $separator_var_name
     * @return Manager
     */
    public static function getManager(
        Request $request,
        int|string $limit = 10,
        int|string $link_count = 10,
        string $page_var_name = 'page',
        string $separator_var_name = 'sep'
    ): Manager {
        return new Manager(
            $limit, $link_count, $request, $page_var_name, $separator_var_name
        );
    }
}