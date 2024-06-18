-- Триггер на `advert` на DELETE:

BEGIN

    DECLARE category SMALLINT(3);
    DECLARE country INT;
    DECLARE region INT;
    DECLARE city INT;
    DECLARE parent SMALLINT(3);

    SELECT SQL_NO_CACHE OLD.advert_category, OLD.advert_place_country, OLD.advert_place_region,  OLD.advert_place_city
    INTO @category, @country, @region, @city;

    -- Отвязка изображений
    UPDATE `advert-thumbnail` SET `id_advert` = null WHERE `id_advert` = OLD.id;

    -- Подсчёт кол-ва объявлений по категориям в регионах
    UPDATE `advert-country_count`
    SET `count` = IF(`count` > 0, `count` - 1, 0)
    WHERE id_country = @country and id_category = @category;

    UPDATE `advert-region_count`
    SET `count` = IF(`count` > 0, `count` - 1, 0)
    WHERE id_region = @region and id_category = @category;

    UPDATE `advert-city_count`
    SET `count` = IF(`count` > 0, `count` - 1, 0)
    WHERE id_city = @city and id_category = @category;

    -- Подсчёт общего кол-ва объявлений по регионам
    UPDATE `advert-country_count_sum`
    SET `count` = IF(`count` > 0, `count` - 1, 0)
    WHERE id_country = @country;

    UPDATE `advert-region_count_sum`
    SET `count` = IF(`count` > 0, `count` - 1, 0)
    WHERE id_region = @region;

    UPDATE `advert-city_count_sum`
    SET `count` = IF(`count` > 0, `count` - 1, 0)
    WHERE id_city = @city;

    -- Пересчёт кол-ва объявлений по регионам в родительских узлах категорий
    SELECT SQL_NO_CACHE `pid` INTO @parent FROM `category` WHERE `id` = @category LIMIT 1;

    WHILE @parent > 0 DO
        UPDATE `advert-country_count`
        SET `count` = IF(`count` > 0, `count` - 1, 0)
        WHERE id_country = @country and id_category = @parent;

        UPDATE `advert-region_count`
        SET `count` = IF(`count` > 0, `count` - 1, 0)
        WHERE id_region = @region and id_category = @parent;

        UPDATE `advert-city_count`
        SET `count` = IF(`count` > 0, `count` - 1, 0)
        WHERE id_city = @city and id_category = @parent;

        SELECT SQL_NO_CACHE `pid`
        INTO @parent
        FROM `category` WHERE `id` = @parent;
    END WHILE;

END