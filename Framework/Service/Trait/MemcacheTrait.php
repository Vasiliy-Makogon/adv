<?php

namespace Krugozor\Framework\Service\Trait;

use Krugozor\Framework\Context;
use Krugozor\Framework\Model\AbstractModel;

trait MemcacheTrait
{
    /**
     * @param $id
     * @param string $modelClassName
     * @param int|null $expire
     * @return AbstractModel
     */
    public function findByIdThroughCache($id, string $modelClassName, int $expire = null): AbstractModel
    {
        $memcacheKey = $modelClassName::createModelCacheKey($id);
        if ($modelData = Context::getInstance()->getMemcache()->get($memcacheKey)) {
            return $modelData;
        }

        $modelData = $this->mapper->findModelById($id);

        Context::getInstance()->getMemcache()->set(
            $memcacheKey,
            $modelData,
            MEMCACHE_COMPRESSED,
            $expire
        );

        return $modelData;
    }
}