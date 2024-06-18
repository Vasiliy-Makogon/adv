<?php

declare(strict_types=1);

namespace Krugozor\Framework\Http\Cover\Data;

use Krugozor\Cover\CoverArray;

/**
 * Оболочка над GPCR массивами.
 */
abstract class AbstractGPCRData extends CoverArray
{
    /**
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        parent::__construct(self::clearData($data));
    }

    /**
     * Очищает значения массива от концевых пробелов в значениях.
     *
     * @param array $in
     * @return array
     */
    private static function clearData(array &$in): array
    {
        if ($in && is_array($in)) {
            foreach ($in as $key => $value) {
                if (is_array($value)) {
                    self::clearData($in[$key]);
                } else {
                    $in[$key] = trim($value);
                }
            }
        }

        return $in;
    }
}