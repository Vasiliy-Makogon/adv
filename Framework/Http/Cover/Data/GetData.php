<?php

declare(strict_types=1);

namespace Krugozor\Framework\Http\Cover\Data;

class GetData extends AbstractGPCRData
{
    /**
     * @inheritDoc
     */
    public function setData(?iterable $data): static
    {
        parent::setData($data);

        $_GET = $this->getDataAsArray();

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function __set(string $key, mixed $value): void
    {
        parent::__set($key, $value);

        $_GET = $this->getDataAsArray();
    }

    /**
     * @inheritDoc
     */
    public function __unset(string $key): void
    {
        parent::__unset($key);

        $_GET = $this->getDataAsArray();
    }
}