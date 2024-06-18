IF (NEW.`id` <> -1 and NEW.`user_active` <> OLD.`user_active`) THEN
    UPDATE `advert`
    SET `advert`.`advert_active` = NEW.`user_active`
    WHERE `advert`.`advert_id_user` = NEW.`id`;
END IF