-- Триггер на `advert` на UPDATE:

BEGIN

    DECLARE category SMALLINT(3);
    DECLARE country INT;
    DECLARE region INT;
    DECLARE city INT;
    DECLARE parent SMALLINT(3);

    DECLARE category_old SMALLINT(3);
    DECLARE country_old INT;
    DECLARE region_old INT;
    DECLARE city_old INT;
    DECLARE parent_old SMALLINT(3);

    SELECT SQL_NO_CACHE advert_category, advert_place_country, advert_place_region, advert_place_city
    INTO @category, @country, @region, @city
    FROM `advert` WHERE `id` = NEW.id
    LIMIT 1;

    SELECT SQL_NO_CACHE OLD.advert_category, OLD.advert_place_country, OLD.advert_place_region, OLD.advert_place_city
    INTO @category_old, @country_old, @region_old, @city_old;

    -- Подсчёт кол-ва объявлений по категориям в стране
    INSERT INTO `advert-country_count`
    VALUES (@country, @category, 1)
    ON DUPLICATE KEY UPDATE `count`=`count`+1;

    UPDATE `advert-country_count`
    SET `count` = IF(`count` > 0, `count` - 1, 0)
    WHERE id_country = @country_old and id_category = @category_old;

    -- Подсчёт общего кол-ва объявлений в стране
    IF (@country <> @country_old) THEN
        INSERT INTO `advert-country_count_sum`
        VALUES (@country, 1)
        ON DUPLICATE KEY UPDATE `count`=`count`+1;

        UPDATE `advert-country_count_sum`
        SET `count` = IF(`count` > 0, `count` - 1, 0)
        WHERE id_country = @country_old;
    END IF;

    -- Подсчёт кол-ва объявлений по категориям в регионе
    INSERT INTO `advert-region_count`
    VALUES (@region, @category, 1)
    ON DUPLICATE KEY UPDATE `count`=`count`+1;

    UPDATE `advert-region_count`
    SET `count` = IF(`count` > 0, `count` - 1, 0)
    WHERE id_region = @region_old and id_category = @category_old;

    -- Подсчёт общего кол-ва объявлений в регионе
    IF (@region_old <> @region) THEN
        INSERT INTO `advert-region_count_sum`
        VALUES (@region, 1)
        ON DUPLICATE KEY UPDATE `count`=`count`+1;

        UPDATE `advert-region_count_sum`
        SET `count` = IF(`count` > 0, `count` - 1, 0)
        WHERE id_region = @region_old;
    END IF;

    -- Подсчёт кол-ва объявлений по категориям в городе
    INSERT INTO `advert-city_count`
    VALUES (@city, @category, 1)
    ON DUPLICATE KEY UPDATE `count`=`count`+1;

    UPDATE `advert-city_count`
    SET `count` = IF(`count` > 0, `count` - 1, 0)
    WHERE id_city = @city_old and id_category = @category_old;

    -- Подсчёт общего кол-ва объявлений в городе
    IF (@city_old <> @city) THEN
        INSERT INTO `advert-city_count_sum`
        VALUES (@city, 1)
        ON DUPLICATE KEY UPDATE `count`=`count`+1;

        UPDATE `advert-city_count_sum`
        SET `count` = IF(`count` > 0, `count` - 1, 0)
        WHERE id_city = @city_old;
    END IF;


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


    SELECT SQL_NO_CACHE `pid` INTO @parent_old FROM `category` WHERE `id` = @category_old LIMIT 1;

    WHILE @parent_old > 0 DO
        UPDATE `advert-country_count`
        SET `count` = IF(`count` > 0, `count` - 1, 0)
        WHERE id_country = @country_old and id_category = @parent_old;

        UPDATE `advert-region_count`
        SET `count` = IF(`count` > 0, `count` - 1, 0)
        WHERE id_region = @region_old and id_category = @parent_old;

        UPDATE `advert-city_count`
        SET `count` = IF(`count` > 0, `count` - 1, 0)
        WHERE id_city = @city_old and id_category = @parent_old;

        SELECT SQL_NO_CACHE `pid`
        INTO @parent_old
        FROM `category` WHERE `id` = @parent_old;
    END WHILE;

END