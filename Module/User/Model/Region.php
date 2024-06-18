<?php

declare(strict_types=1);

namespace Krugozor\Framework\Module\User\Model;

use Krugozor\Database\MySqlException;
use Krugozor\Framework\Module\User\Mapper\RegionMapper;
use Krugozor\Framework\Validator\DecimalValidator;
use Krugozor\Framework\Validator\IntRangeValidator;
use Krugozor\Framework\Validator\IsNotEmptyStringValidator;
use Krugozor\Framework\Validator\StringLengthValidator;

/**
 * @method getWeight()
 * @method setWeight($weight)
 *
 * @method getIsImportantRegion()
 * @method setIsImportantRegion($isImportantRegion)
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
class Region extends AbstractTerritory
{
    /**
     * @inheritdoc
     */
    protected static ?string $db_field_prefix = 'region';

    /**
     * @inheritdoc
     */
    protected string $countable_field_name = 'id_region';

    /**
     * @inheritdoc
     */
    protected string $countable_table_name = 'advert-region_count';

    /**
     * @inheritdoc
     */
    protected string $countable_sum_table_name = 'advert-region_count_sum';

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

        'is_important_region' => array(
            'db_element' => true,
            'db_field_name' => 'is_important_region',
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
            'db_field_name' => 'region_name_ru',
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
            'db_field_name' => 'region_name_ru2',
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
            'db_field_name' => 'region_name_ru3',
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
            'db_field_name' => 'region_name_en',
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