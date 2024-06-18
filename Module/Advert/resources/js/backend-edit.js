"use strict";

document.addEventListener("DOMContentLoaded", function () {

    let textareaCollections = document.getElementsByTagName('textarea');
    [...textareaCollections].forEach(function (item) {
        Krugozor.Forms.resizeTextarea(item);
    });

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

    let setVipDateLinks = document.querySelectorAll('[data-time][data-target]');
    for (let setVipDateLink of setVipDateLinks) {
        setVipDateLink.addEventListener('click', function (e) {
            e.preventDefault();
            document.getElementById(e.target.dataset.target).value = e.target.dataset.time;
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