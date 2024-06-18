<?php

namespace Krugozor\Framework\Module\Advert\Service\Trait;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Context;
use Krugozor\Framework\Module\Advert\Model\Advert;

trait MemcacheTrait
{
    /**
     * @param $id
     * @return CoverArray
     */
    public function findByIdForViewThroughCache($id): CoverArray
    {
        $memcacheKey = Advert::createModelCacheKey($id);
        if ($advertData = Context::getInstance()->getMemcache()->get($memcacheKey)) {
            return $advertData;
        }

        $advertData = $this->mapper->findByIdForView($id);

        Context::getInstance()->getMemcache()->set(
            $memcacheKey,
            $advertData,
            MEMCACHE_COMPRESSED,
            60 * 60 * 24 * 30
        );

        return $advertData;
    }
}