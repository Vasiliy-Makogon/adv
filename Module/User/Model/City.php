<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Model;

use Krugozor\Framework\Validator\DecimalValidator;
use Krugozor\Framework\Validator\IntRangeValidator;
use Krugozor\Framework\Validator\IsNotEmptyStringValidator;
use Krugozor\Framework\Validator\StringLengthValidator;

/**
 * @method getWeight()
 * @method setWeight($weight)
 *
 * @method getHasMetro()
 * @method setHasMetro($hasMetro)
 *
 * @method getIdRegion()
 * @method setIdRegion($idRegion)
 *
 * @method getIdCountry()
 * @method setIdCountry($idCountry)
 *
 * @method getNameRu()
 * @method setNameRu($nameRu)
 *
 * @method getNameRu2()
 * @method setNameRu2($nameRu2)
 *
 * @method getNameRu3()
 * @method setNameRu3($nameRu3)
 *
 * @method getNameEn()
 * @method setNameEn($nameEn)
 *
 * @method getAdvertCount() fake-свойство, заполняется из SQL (кол-во объявлений в этой территории)
 * @method setAdvertCount($advertCount)
 */
class City extends AbstractTerritory
{
    /**
     * @inheritdoc
     */
    protected static ?string $db_field_prefix = 'city';

    /**
     * @inheritdoc
     */
    protected string $countable_field_name = 'id_city';

    /**
     * @inheritdoc
     */
    protected string $countable_table_name = 'advert-city_count';

    /**
     * @inheritdoc
     */
    protected string $countable_sum_table_name = 'advert-city_count_sum';

    /**
     * @inheritdoc
     */
    protected static array $model_attributes = array
    (
        'id' => array(
            'db_element' => false,
            'default_value' => 0
        ),

        'weight' => array(
            'db_element' => true,
            'default_value' => 0,
            'db_field_name' => 'weight',
            'validators' => array(
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => array('signed' => false),
            )
        ),

        'has_metro' => array(
            'db_element' => true,
            'db_field_name' => 'has_metro',
            'default_value' => 1,
            'validators' => array(
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => array('signed' => false),
                IntRangeValidator::class => array(
                    'min' => IntRangeValidator::ZERO,
                    'max' => IntRangeValidator::ONE,
                ),
            )
        ),

        'id_region' => array(
            'db_element' => true,
            'default_value' => 0,
            'db_field_name' => 'id_region',
            'validators' => array(
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => array('signed' => false),
            )
        ),

        'id_country' => array(
            'db_element' => true,
            'default_value' => 0,
            'db_field_name' => 'id_country',
            'validators' => array(
                IsNotEmptyStringValidator::class => [],
                DecimalValidator::class => array('signed' => false),
            )
        ),

        'name_ru' => array(
            'db_element' => true,
            'db_field_name' => 'city_name_ru',
            'validators' => array(
                IsNotEmptyStringValidator::class => [],
                StringLengthValidator::class => array(
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 50
                ),
            ),
        ),

        'name_ru2' => array(
            'db_element' => true,
            'db_field_name' => 'city_name_ru2',
            'validators' => array(
                IsNotEmptyStringValidator::class => [],
                StringLengthValidator::class => array(
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 50
                ),
            ),
        ),

        'name_ru3' => array(
            'db_element' => true,
            'db_field_name' => 'city_name_ru3',
            'validators' => array(
                IsNotEmptyStringValidator::class => [],
                StringLengthValidator::class => array(
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 50
                ),
            ),
        ),

        'name_en' => array(
            'db_element' => true,
            'db_field_name' => 'city_name_en',
            'validators' => array(
                IsNotEmptyStringValidator::class => [],
                StringLengthValidator::class => array(
                    'start' => StringLengthValidator::ZERO_LENGTH,
                    'stop' => 50
                ),
            ),
        ),

        'advert_count' => array(
            'db_element' => false,
            'default_value' => null
        ),
    );
}