-- Триггер на `advert` на INSERT:

BEGIN

    DECLARE category SMALLINT(3);
    DECLARE country INT;
    DECLARE region INT;
    DECLARE city INT;
    DECLARE parent SMALLINT(3);

    SELECT SQL_NO_CACHE advert_category, advert_place_country, advert_place_region,  advert_place_city
    INTO @category, @country, @region, @city
    FROM `advert` WHERE `id` = NEW.id
    LIMIT 1;

    -- Подсчёт кол-ва объявлений по категориям в регионах
    INSERT INTO `advert-country_count`
    VALUES (@country, @category, 1)
    ON DUPLICATE KEY UPDATE `count`=`count`+1;

    INSERT INTO `advert-region_count`
    VALUES (@region, @category, 1)
    ON DUPLICATE KEY UPDATE `count`=`count`+1;

    INSERT INTO `advert-city_count`
    VALUES (@city, @category, 1)
    ON DUPLICATE KEY UPDATE `count`=`count`+1;

    -- Подсчёт общего кол-ва объявлений по регионам
    INSERT INTO `advert-country_count_sum`
    VALUES (@country, 1)
    ON DUPLICATE KEY UPDATE `count`=`count`+1;

    INSERT INTO `advert-region_count_sum`
    VALUES (@region, 1)
    ON DUPLICATE KEY UPDATE `count`=`count`+1;

    INSERT INTO `advert-city_count_sum`
    VALUES (@city, 1)
    ON DUPLICATE KEY UPDATE `count`=`count`+1;

    -- Пересчёт кол-ва объявлений по регионам в родительских узлах категорий
    SELECT SQL_NO_CACHE `pid` INTO @parent FROM `category` WHERE `id` = @category LIMIT 1;

    WHILE @parent > 0 DO
        INSERT INTO `advert-country_count`
        VALUES (@country, @parent, 1)
        ON DUPLICATE KEY UPDATE `count`=`count`+1;

        INSERT INTO `advert-region_count`
        VALUES (@region, @parent, 1)
        ON DUPLICATE KEY UPDATE `count`=`count`+1;

        INSERT INTO `advert-city_count`
        VALUES (@city, @parent, 1)
        ON DUPLICATE KEY UPDATE `count`=`count`+1;

        SELECT SQL_NO_CACHE `pid`
        INTO @parent
        FROM `category` WHERE `id` = @parent;
    END WHILE;

END