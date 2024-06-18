-- Это "аварийный" триггер обновления данных на таблице `advert`,
-- он не должен использоваться на боевой среде и запускается единожды при запросе вида

-- Последовательность действий:
-- 1. Очищаем таблицы:
TRUNCATE TABLE `advert-country_count`;
TRUNCATE TABLE `advert-region_count`;
TRUNCATE TABLE `advert-city_count`;

TRUNCATE TABLE `advert-country_count_sum`;
TRUNCATE TABLE `advert-region_count_sum`;
TRUNCATE TABLE `advert-city_count_sum`;

-- 2. Меняем триггер на обновление (он ниже)
-- 3. Выполняем команду: UPDATE `advert` SET `advert_active` = `advert_active`;
-- 4. Возвращаем на место триггер из

-- Сам триггер:
BEGIN

    DECLARE category SMALLINT(3);
    DECLARE country INT;
    DECLARE region INT;
    DECLARE city INT;
    DECLARE parent SMALLINT(3);

    SELECT advert_category, advert_place_country, advert_place_region,  advert_place_city
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
    SELECT `pid` INTO @parent FROM `category` WHERE `id` = @category LIMIT 1;

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

        SELECT `pid`
        INTO @parent
        FROM `category` WHERE `id` = @parent;
    END WHILE;

END