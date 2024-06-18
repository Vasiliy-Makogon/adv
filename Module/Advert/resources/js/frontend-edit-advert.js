"use strict";

/**
 * Событие onclick на ссылку быстрого выбора местоположения.
 *
 * @param int country ID страны
 * @param int region ID региона
 * @param int city ID города
 * @return Boolean
 */
function simple_city_checked(country, region, city) {
    Krugozor.Location.addCheckedUserLocation(1, country);
    Krugozor.Location.addCheckedUserLocation(2, region);
    Krugozor.Location.addCheckedUserLocation(3, city);

    selectCountryObj.create(1, 0);
    selectRegionObj.create(2, country);
    selectCityObj.create(3, region);

    return false;
}

/**
 * После регистрации пользователя напоминает в notofications
 * только что установленные им логин и пароль.
 *
 * @param _this
 * @param login
 * @param password
 * @return boolean
 */
function view_login_password(_this, login, password) {
    var span = document.createElement('SPAN');
    var b = document.createElement('B');
    b.appendChild(document.createTextNode(login));
    span.appendChild(b);
    span.appendChild(document.createTextNode(' '));
    var b = document.createElement('B');
    b.appendChild(document.createTextNode(password));
    span.appendChild(b);
    _this.parentNode.replaceChild(span, _this);
    return false;
}

document.addEventListener("DOMContentLoaded", function () {

    const element = document.getElementById('js-select-category-wrapper');
    if (element) {
        Krugozor.СategorySelectListBuilder.run(
            element,
            current_category,
            category_pid,
            'advert[category]',
            current_advert_type
        );
    }

    let textareaCollections = document.getElementsByTagName('textarea');
    [...textareaCollections].forEach(function (item) {
        Krugozor.Forms.resizeTextarea(item);
    });

    let captchaCodeField = document.querySelector('input[name="captcha_code"]');
    if (captchaCodeField) {
        captchaCodeField.addEventListener('input', function (e) {
            Krugozor.Forms.filterDigit(e.target);
        });
    }

    if (document.getElementById('file')) {
        // Событие onfocus для предотвращения работы ботов - бот фокус не поставит.
        document.getElementById('file').addEventListener('focus', function (e) {
            e.target.setAttribute('data-checked', 1);
        });

        // Событие на выбор файла.
        document.getElementById('file').addEventListener('change', function (e) {
            Krugozor.thumbnail.processUpload(e.target);
        });
    }

    // Событие на кнопку подачи объявления.
    if (document.forms["main_form"]) {
        document.forms["main_form"].addEventListener('submit', function (e) {
            var thumbnails = document.getElementById('uploaded_images').getElementsByTagName('img');
            if (!thumbnails.length) {
                var confirm_text = "Вы не загрузили изображения для Вашего объявления. Наличие изображения в объявлении значительно повышает эффективность объявления.\n" +
                    (window.is_guest ? '' : "Поскольку Вы зарегистрированный пользователь, то Вы можете добавить изображения позже -- при редактировании объявления.\n") +
                    "\nOK -- разместить объявление без загрузки изображений.\nОтмена -- вернуться и загрузить изображения.";

                if (!confirm(confirm_text)) {
                    e.preventDefault();
                    document.getElementById('file').click();
                    window.scrollTo({top: 0, behavior: 'smooth'});
                    return;
                }
            }

            document.forms["main_form"].submit();
        });
    }

    if (document.getElementById('advert_category')) {
        Krugozor.UI.popup.ajaxselect.initSelect(document.getElementById('advert_category'));
    }

    Krugozor.thumbnail.setThumbnailIconPath('/svg/local/thumbnail_load_icon.svg');
    Krugozor.thumbnail.setIsRobot(window.is_guest == '1');
    Krugozor.thumbnail.setImageUploadHandlerUrl("/advert/thumbnail/");
    Krugozor.thumbnail.setUploadedImagesBlock(document.getElementById('uploaded_images'));
    Krugozor.thumbnail.setErrorsBlock(document.getElementById('thumbnail_errors'));
    Krugozor.thumbnail.setMainForm(document.getElementById('main_form'));
    Krugozor.thumbnail.setFileUploadForm(document.getElementById('file_upload_form'));
    Krugozor.thumbnail.setMaxFiles(max_upload_files);
    Krugozor.thumbnail.observer();
});