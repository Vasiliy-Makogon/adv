"use strict";

var Krugozor = window.Krugozor || {};

/**
 * Объект, оперирующий функционалом загрузки изображений через iframe. Описание работы.
 *
 * На основной странице должны располагаться:
 *
 * 1. iframe вида:
 *
 *     <iframe style="display: none" width="0" height="0" name="iframe"></iframe>
 *
 * 2. Форма для загрузки изображений с кнопкой выбора файла:
 *
 *     <form id="file_upload_form" method="post" enctype="multipart/form-data" target="iframe">
 *         <input type="hidden" name="MAX_FILE_SIZE" value="..." />
 *         <input type="file" name="file" id="file" checked="0" />
 *     </form>
 *
 * 3. Основная форма, в скрытых полях которой будет храниться информация о загруженных изображениях
 * (именно с помощью этой формы будет связаны изображения с сущностью, к которой они прикрепляются):
 *
 *     <form method="post" action="..." id="main_form">
 *      <!--
 *         сюда будут вставляться hidden-поля вида
 *         <input name="thumbnail[]" value="177" type="hidden">
 *     -->
 *     </form>
 *
 * 4. Блок показа ошибок при загрузке изображений:
 *
 *       <div id="thumbnail_errors"></div>
 *
 * 5. Блок показа только что загруженных превью:
 *
 *     <div id="uploaded_images"></div>
 *
 *
 *  Инициализация объекта на основной странице должна происходить следующим образом,
 *  с указанием всех необходимых параметров, необходимых для корректной работы:
 *
 * document.addEventListener("DOMContentLoaded", function () {
 *     // Путь к прелоадеру изображений
 *     Krugozor.thumbnail.setThumbnailIconPath(...);
 *     // Делать ли проверку на бота
 *     Krugozor.thumbnail.setIsRobot(window.is_guest == '1');
 *     // Путь к обработчику загрузки изображений
 *     Krugozor.thumbnail.setImageUploadHandlerUrl(...);
 *     // Блок превью загруженных изображений.
 *     Krugozor.thumbnail.setUploadedImagesBlock(document.getElementById('...'));
 *     // Блок показа ошибок загрузки.
 *     Krugozor.thumbnail.setErrorsBlock(document.getElementById('...'));
 *     // Главная форма.
 *     Krugozor.thumbnail.setMainForm(document.getElementById('...'));
 *     // Форма с полем загрузки файла.
 *     Krugozor.thumbnail.setFileUploadForm(document.getElementById('...'));
 *     // Макс. кол-во загружаемых файлов
 *     Krugozor.thumbnail.setMaxFiles(...);
 *     // Наблюдатель появления в теле документа новых превью
 *     Krugozor.thumbnail.observer();
 * });
 *
 *
 * Инициализация на странице в iframe:
 *
 * document.addEventListener("DOMContentLoaded", function () {
 *     Krugozor.thumbnail.setUploadedImagesBlock(window.parent.document.getElementById('...'));
 *     Krugozor.thumbnail.setErrorsBlock(window.parent.document.getElementById('...'));
 *     Krugozor.thumbnail.setMainForm(window.parent.document.getElementById('...'));
 *     Krugozor.thumbnail.setFileUploadForm(window.parent.document.getElementById('...'));
 *     Krugozor.thumbnail.setMaxFiles(...);
 *
 *     <?php if ($error): ?>
 *         Krugozor.thumbnail.uploadFail('<?=addslashes($error)?>');
 *     <?php elseif ($path_to_image): ?>
 *         Krugozor.thumbnail.uploadSuccess(<?=$thumbnail_id?>, '<?=$path_to_image?>');
 *     <?php endif; ?>
 * });
 *
 *
 * В коде основной страницы 2 события на кнопку загрузки файла:
 *
 * document.addEventListener("DOMContentLoaded", function () {
 *     if (document.getElementById('file')) {
 *         // Событие onfocus для предотвращения работы ботов - бот фокус не поставит.
 *         document.getElementById('file').addEventListener('focus', function (e) {
 *             e.target.setAttribute('data-checked', 1);
 *         });
 *
 *         // Событие на выбор файла.
 *         document.getElementById('file').addEventListener('change', function (e) {
 *             Krugozor.thumbnail.processUpload(e.target);
 *         });
 *     }
 * });
 */
Krugozor.thumbnail = {

    // Делать ли проверку на бота.
    is_robot: true,

    /**
     * Устанавливает путь к прелоадеру изображений.
     *
     * @param string
     */
    setThumbnailIconPath: function (thumbnail_icon_path) {
        this.thumbnail_icon_path = thumbnail_icon_path;
    },

    /**
     * Устанавливает путь к обработчику загрузки изображений
     *
     * @param string
     */
    setImageUploadHandlerUrl: function (image_upload_handler_url) {
        this.image_upload_handler_url = image_upload_handler_url;
    },

    /**
     * Установка блока, куда вставляются изображения после загрузки.
     */
    setUploadedImagesBlock: function (uploaded_images_block) {
        this.uploaded_images_block = uploaded_images_block;
    },

    /**
     * Установка блока показа ошибок загрузки.
     */
    setErrorsBlock: function (errors_block) {
        this.errors_block = errors_block;
    },

    /**
     * Установка главной формы.
     */
    setMainForm: function (main_form) {
        this.main_form = main_form;
    },

    /**
     * Установка формы с полем загрузки файла.
     */
    setFileUploadForm: function (file_upload_form) {
        this.file_upload_form = file_upload_form;
    },

    /**
     * Устанавливает максимально-допусимое кол-во файлов, которое можно загрузить.
     *
     * @param int
     */
    setMaxFiles: function (max_files) {
        this.max_files = parseInt(max_files);
    },

    /**
     * Делать ли проверку на робота.
     *
     * @param bool
     */
    setIsRobot: function (is_robot) {
        this.is_robot = !!is_robot;
    },

    /**
     * Событие onchange на кнопку выбора изображения, которое приводит к загрузки файла, т.е. к submit формы.
     *
     * @param inputFile ссылка на элемент input, на котором произошло событие.
     */
    processUpload: function (inputFile) {
        if (!inputFile.value) {
            return;
        }

        // Если аттрибут checked в 0, значит это робот.
        if (this.is_robot && !Boolean(parseInt(inputFile.getAttribute('data-checked')))) {
            return;
        }

        this.file_upload_form.action = this.image_upload_handler_url;

        this.setUploadErrorState();

        // добавляем прелоадер
        const icon = this.createThumbnailImageIcon();
        const wrap = this.createThumbnailWrap();
        wrap.appendChild(icon);
        this.uploaded_images_block.appendChild(wrap);
        this.uploaded_images_block.style.display = "block";

        this.file_upload_form.submit();
        this.file_upload_form.elements.namedItem('file').disabled = true;
    },

    /**
     * Ошибка загрузки. Функция для содержимого iframe.
     *
     * @param string текст сообщения об ошибке.
     */
    uploadFail: function (error) {
        this.setUploadErrorState(error);

        if (this.isWrap(this.uploaded_images_block.lastChild)) {
            this.uploaded_images_block.removeChild(this.uploaded_images_block.lastChild);
        }

        this.checkNodesInImagesBlock(this.uploaded_images_block);

        const inputFile = this.file_upload_form.elements.namedItem('file');
        inputFile.disabled = false;
        inputFile.value = null;
    },

    /**
     * Успешная загрузка. Функция для содержимого iframe.
     *
     * @param ID изображения
     * @param путь к изображению
     */
    uploadSuccess: function (thumbnail_id, thumbnail_path) {
        const remove_link = this.createThumbnailRemoveLink(thumbnail_id);

        // Заменяем иконку загрузки, если она есть.
        if (this.isWrap(this.uploaded_images_block.lastChild)) {
            this.uploaded_images_block.lastChild.firstChild.setAttribute('src', "/i/small" + thumbnail_path);
            this.uploaded_images_block.lastChild.appendChild(remove_link);
        } else {
            // Тут иконки прелоадера нет - ситуация когда был загружен недопустимый файл
            // и прелоадер был убран динамически в методе this.uploadFail().
            const img = createThumbnailImage("/i/small" + thumbnail_path);
            const wrap = this.createThumbnailWrap();
            wrap.appendChild(img);
            wrap.appendChild(remove_link);

            this.uploaded_images_block.appendChild(wrap);
        }

        // Добавление hidden-полей в основную форму.
        const input = document.createElement('input');
        input.setAttribute('type', 'hidden');
        input.setAttribute('name', 'thumbnail[]');
        input.setAttribute('value', thumbnail_id);
        this.main_form.appendChild(input);

        this.file_upload_form.elements.namedItem('file').value = null;
        this.checkDisabled();
    },

    /**
     * Установка на ссылки удаления изображений обработчиков событий.
     * Каждые полсекунды проверяется, имеется ли ссылка без установленного события и если таковая есть,
     * то на данную ссылку ставится обработчик.
     */
    observer: function () {
        this.checkDisabled();
        this.createThumbnailImageIcon();

        const _this = this;
        setInterval(function () {
            const wraps = _this.uploaded_images_block.getElementsByTagName('span');

            for (let wrap of wraps) {
                let a = wrap.querySelectorAll('a:last-child')[0];

                // Ссылка удаления изображения еще не подгрузилась в DOM.
                if (a === undefined) {
                    return;
                }

                // Не даем проставится событию на элемен более одного раза.
                // И не ставим событие на span обрамления изображения.
                if (a.dataset.eventIsSet) {
                    continue;
                }

                a.addEventListener('click', function (e) {
                    _this.process_remove_thumbnail(e);
                });

                // Флаг, что событие проставлено на элемент.
                a.dataset.eventIsSet = true;
            }

        }, 500);
    },

    /**
     * Обработчик нажатия кнопки удаления изображения.
     *
     * @param event
     */
    process_remove_thumbnail: function (e) {
        var target = e.target;
        var images_block = target.parentNode.parentNode;
        var id = target.dataset.id;
        var _this = this;

        // Если идет попытка удалить изображение, привязанное к объявлению - делаем Ajax запрос.
        if (id && target.dataset.advert) {
            var ajax = new Krugozor.Ajax();
            ajax.setObserverState(function (ajx, xhr) {
                var response = ajx.getJson();
                if (response.result) {
                    target.parentNode.style.transition = '0.5s';
                    target.parentNode.style.opacity = 0;

                    setTimeout(function () {
                        target.parentNode.parentNode.removeChild(target.parentNode);
                        _this.checkNodesInImagesBlock(images_block);
                    }, 650);
                } else {
                    alert('Извините, не удалось удалить изображение. Мы уже работаем над этим.');
                }
            });
            ajax.get('/advert/thumbnail-unlink/?id=' + id);
        } else {
            // В противном случае просто скрываем изображение.
            target.parentNode.style.transition = '0.5s';
            target.parentNode.style.opacity = 0;

            setTimeout(function () {
                target.parentNode.parentNode.removeChild(target.parentNode);
                _this.checkNodesInImagesBlock(images_block);
            }, 650);
        }

        // Получаем скрытые поля и удаляем поле с ID удаленного изображения.
        var hidden_thumbnail_list = document.getElementsByName('thumbnail[]');

        for (var j = 0; j < hidden_thumbnail_list.length; j++) {
            if (hidden_thumbnail_list[j].nodeType == 1 && hidden_thumbnail_list[j].value == id) {
                hidden_thumbnail_list[j].parentNode.removeChild(hidden_thumbnail_list[j]);
                break;
            }
        }

        this.checkDisabled();
        e.preventDefault();
    },

    /**
     * Ставит или снимает disabled на кнопку загрузки файла изображения, в зависимости от того,
     * достигнут лимит загруженных изображений или нет.
     */
    checkDisabled: function () {
        this.file_upload_form.elements.namedItem('file').disabled = (
            this.main_form.querySelectorAll('input[name=thumbnail\\[\\]]').length >= this.max_files
        );
    },

    /**
     * Создание элемента прелоадера и его подгрузка.
     *
     * @return HTMLImageElement
     */
    createThumbnailImageIcon: function () {
        let thumbnail_icon = new Image();
        thumbnail_icon.src = this.thumbnail_icon_path;

        return thumbnail_icon;
    },

    /**
     * Создание элемента изображения.
     *
     * @param string путь к изображению
     * @return HTMLImageElement
     */
    createThumbnailImage: function (path) {
        var img = new Image();
        img.setAttribute('alt', '');
        img.setAttribute('src', path);

        return img;
    },

    /**
     * Создание обрамления изображения.
     *
     * @return HTMLSpanElement
     */
    createThumbnailWrap: function () {
        return document.createElement('span');
    },

    /**
     * Создание ссылки на удаление изображения.
     *
     * @param int ID изображения
     * @return HTMLAnchorElement
     */
    createThumbnailRemoveLink: function (thumbnail_id) {
        let link = document.createElement('a');
        link.setAttribute('href', "#");
        link.setAttribute('title', "Удалить изображение");
        link.setAttribute('data-id', thumbnail_id);
        link.setAttribute('data-advert', '');
        return link;
    },

    /**
     * Возвращает true, если elenet - обрамляющий тег изображения.
     *
     * @param HTMLElement
     * @return true
     */
    isWrap: function (element) {
        return element.nodeType == 1 && element.tagName.toUpperCase() == 'SPAN';
    },

    /**
     * Инициализация текстового узла сообщения об ошибке и/или установка сообщения об ошибке error.
     *
     * @param string
     */
    setUploadErrorState: function (error) {
        if (this.errors_block.childNodes.length == 0) {
            this.errors_block.appendChild(document.createTextNode(''));
        }

        this.errors_block.firstChild.nodeValue = error || '';
        this.errors_block.style.display = error ? 'block' : 'none';
    },

    /**
     * Проверяет наличие детей в блоке изображений.
     * Если их нет - скрывает блок.
     *
     * @param object блок с изображениями
     */
    checkNodesInImagesBlock: function (images_block) {
        if (images_block.childNodes.length == 0) {
            images_block.style.display = 'none';
            return;
        }
        for (var i = 0; i < images_block.childNodes.length; i++) {
            if (images_block.childNodes[i].nodeType == 1) {
                images_block.style.display = 'true';
                return;
            }
        }

        images_block.style.display = 'none';
    }
}