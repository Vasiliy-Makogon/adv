<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\Advert\Validator;

use Krugozor\Framework\Module\User\Model\City;
use Krugozor\Framework\Validator\AbstractValidator;

/**
 * Проверка на наличие в заголовке объявления имени города $advert->place_city.
 */
class CityNameInHeaderValidator extends AbstractValidator
{
    /**
     * @inheritdoc
     */
    protected string $error_key = 'BAD_CITY_NAME_IN_HEADER';

    /**
     * Возвращает false (факт ошибки), если найдено объявление с именем города в заголовке объявления.
     *
     * @return bool
     */
    public function validate(): bool
    {
        if (!$this->value->getPlaceCity()) {
            return true;
        }

        /** @var City $city */
        $city = $this->mapper->findModelById($this->value->getPlaceCity());
        $names = [$city->getNameRu(), $city->getNameRu2(), $city->getNameRu3()];

        foreach ($names as $name) {
            preg_match('/' . $name . '/ui', $this->value->getHeader(), $matches);

            if (!empty($matches[0])) {
                $this->error_params = array('city' => $city->getNameRu());
                return false;
            }
        }

        return true;
    }
}