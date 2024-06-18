<?php

namespace Krugozor\Framework\Module\User\Cover;

use Krugozor\Cover\CoverArray;
use Krugozor\Framework\Module\User\Model\City;
use Krugozor\Framework\Module\User\Model\Country;
use Krugozor\Framework\Module\User\Model\Region;
use Krugozor\Framework\Module\User\Model\AbstractTerritory;

class TerritoryList extends CoverArray
{
    /**
     * @param AbstractTerritory $territory
     * @return static
     */
    public function setTerritory(AbstractTerritory $territory): static
    {
        $url = ($this->getLastTerritory() ? $this->getLastTerritory()->getUrl() : '') . '/' . $territory->getNameEn();

        $territory->setUrl($url);

        $this->setData(match (true) {
            $territory instanceof City => ['city' => $territory],
            $territory instanceof Region => ['region' => $territory],
            $territory instanceof Country => ['country' => $territory]
        });

        return $this;
    }

    /**
     * @return AbstractTerritory|null
     */
    public function getLastTerritory(): ?AbstractTerritory
    {
        return $this->get('city') ?? $this->get('region') ?? $this->get('country');
    }

    /**
     * @return bool
     */
    public function isCountry(): bool
    {
        return $this->count() == 1 && $this->get('country');
    }

    /**
     * @return bool
     */
    public function isRegion(): bool
    {
        return $this->count() == 2 && $this->get('country') && $this->get('region');
    }

    /**
     * @return bool
     */
    public function isCity(): bool
    {
        return $this->count() == 3 && $this->get('country') && $this->get('region') && $this->get('city');
    }
}