-- Загрузка изображений.
-- Информация об изображении записывается в таблицу thumbnail-advert.
-- Если объявление сохраняется, то PHP-код в маппере thumbnail-advert обновляет таблицу thumbnail-advert,
-- проставляя в поле id_advert ID объявления. Срабатывает триггер:

IF (NEW.id_advert IS NOT NULL) THEN
    UPDATE `advert`
    SET `advert_thumbnail_count` = `advert_thumbnail_count` +1
    WHERE `id` = NEW.id_advert;

    UPDATE `advert`
    SET `advert_thumbnail_file_name` = NEW.file_name
    WHERE `id` = NEW.id_advert
    AND `advert_thumbnail_file_name` IS NULL;
ELSEIF (NEW.id_advert IS NULL AND EXISTS (SELECT * FROM `advert` WHERE id = OLD.id_advert)) THEN
    UPDATE `advert`
    SET `advert_thumbnail_count` = `advert_thumbnail_count` - 1
    WHERE `id` = OLD.id_advert;

    UPDATE `advert`
    SET `advert_thumbnail_file_name` = (
        SELECT `file_name` FROM `advert-thumbnail` WHERE `id_advert` = OLD.id_advert ORDER BY `id` ASC LIMIT 1
    )
    WHERE `id` = OLD.id_advert;
END IF
