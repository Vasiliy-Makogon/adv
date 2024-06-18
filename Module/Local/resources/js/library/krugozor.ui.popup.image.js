"use strict";

var Krugozor = window.Krugozor || {
    UI: {
        popup: {}
    }
};

Krugozor.UI.popup.image = {

    // Основная flex-обертка
    CSS_COMMON_WRAPPER: {
        display: 'flex',
        flexDirection: 'column',
        alignItems: 'center',
        justifyContent: 'center',
        visibility: 'hidden',
        width: '100vw',
        height: '100vh',
        position: 'fixed',
        top: 0,
        left: 0,
        zIndex: '1000',
        background: 'rgba(0,0,0,0.5)'
    },

    // Обертка над фото
    CSS_IMAGE_WRAPPER: {
        display: 'block',
        padding: '15px',
        backgroundColor: '#fff',
        boxShadow: '0 0 10px #CCC',
        maxWidth: '95vw',
        maxHeight: '95vh',
        position: 'relative',
        borderRadius: 'var(--border-radius-common)'
    },

    CSS_IMAGE: {
        cursor: 'pointer',
        display: 'block',
        // Масштабирование картинки. Хер знает, как это работает.
        maxWidth: '100%',
        maxHeight: '100%',
        borderRadius: 'var(--border-radius-common)'
    },

    // CSS стили блока с кнопкой "закрыть".
    CSS_DIV_CLOSE: {
        fontSize: '12px',
        color: '#000',
        fontWeight: 'bold',
        fontFamily: 'Arial, Verdana, sans-serif',
        textAlign: 'right',
        position: 'absolute',
        top: 0,
        right: 0,
        padding: '10px',
        backgroundColor: '#fff',
        cursor: 'pointer',
        borderRadius: 'var(--border-radius-common)'
    },

    // CSS стили блока кнопки "закрыть".
    CSS_SPAN_CLOSE: {
        letterSpacing: '1.1px'
    },

    images: [],

    wrapper: null,

    /**
     * Загружает скрытые изображения по селектору, находя пути к ним в следующих тегах/аттрибутах:
     * A:href
     * IMG:src
     *
     * @param {string} selector селектор для поиска
     * @returns {Window.Krugozor.UI.popup.image}
     */
    loadImagesBySelector: function (selector) {
        const elementsNodeList = document.querySelectorAll(selector);

        for (let i = 0; i < elementsNodeList.length; i++) {
            if (elementsNodeList[i].tagName.toUpperCase() === 'IMG') {
                this.loadImage(elementsNodeList[i].getAttribute('src'));
            } else if (elementsNodeList[i].tagName.toUpperCase() === 'A') {
                this.loadImage(elementsNodeList[i].getAttribute('href'));
            }

            const _this = this;

            elementsNodeList[i].addEventListener("click", function (e) {
                // Вытаскиваем путь к изображению, которое нужно отобразить.
                // Оно должно быть относительным путём!
                const source = e.currentTarget.getAttribute('href')
                    || e.currentTarget.getAttribute('src')
                    || null;

                if (source) {
                    _this.showImage(source);
                    e.preventDefault();
                }
            });
        }

        return this;
    },

    /**
     * Показывает изображение по адресу source
     *
     * @param source
     * @return void
     */
    showImage: function (source) {
        let img_node;
        const _this = this;
        let currentImageData = null;

        for (let i = 0; i < _this.images.length; i++) {
            if (source === _this.images[i].src && _this.images[i].img.loaded) {
                currentImageData = _this.images[i];
            }
        }

        if (!currentImageData) {
            alert('Извините, изображение ещё не загрузилось, попробуйте ещё раз');
            return;
        }

        if (this.wrapper === null) {
            this.wrapper = document.createElement("DIV");
            Krugozor.Helper.attachCss(this.wrapper, this.CSS_COMMON_WRAPPER);
            document.body.appendChild(this.wrapper);

            // Обрамляющий изображение div
            const image_wrapper = document.createElement("DIV");
            Krugozor.Helper.attachCss(image_wrapper, this.CSS_IMAGE_WRAPPER);
            this.wrapper.appendChild(image_wrapper);

            // div закрытия окна
            const div_close_node = document.createElement("DIV");
            Krugozor.Helper.attachCss(div_close_node, this.CSS_DIV_CLOSE);
            const span_close_node = document.createElement("SPAN");
            span_close_node.appendChild(document.createTextNode('[закрыть изображение]'));
            Krugozor.Helper.attachCss(span_close_node, this.CSS_SPAN_CLOSE);
            div_close_node.appendChild(span_close_node);
            image_wrapper.appendChild(div_close_node);

            // Клик по wrapper - закрываем окно
            this.wrapper.addEventListener("click", function (e) {
                if (e.target.tagName.toUpperCase() !== 'IMG') {
                    e.preventDefault();
                    _this.wrapper.style.visibility = "hidden";
                }
            });
            // и событие на кнопку "закрыть"
            span_close_node.addEventListener("click", function () {
                _this.wrapper.style.visibility = "hidden";
            });

            // Изображение
            img_node = document.createElement('IMG');
            Krugozor.Helper.attachCss(img_node, this.CSS_IMAGE);
            image_wrapper.appendChild(img_node);

            img_node.addEventListener("click", function (e) {
                e.preventDefault();

                for (const i in _this.images) {
                    const currentSource = e.target.getAttribute('href')
                        || e.target.getAttribute('src')
                        || null;

                    if (currentSource && _this.images[i].src === currentSource) {
                        let index = parseInt(i) + 1;
                        index = _this.images[index] !== undefined ? index : 0;

                        _this.showImage(_this.images[index].src);
                        break;
                    }
                }
            });
        } else {
            img_node = this.wrapper.firstChild.lastChild;
        }

        img_node.setAttribute('src', currentImageData.src);

        this.wrapper.style.visibility = "visible";
    },

    /**
     * Загружает изображение по адресу source и сохраняет его объект в хранилище {@link images}
     *
     * @param source URL-адрес к изображению
     * @returns {Window.Krugozor.UI.popup.image}
     */
    loadImage: function (source) {
        const img = new Image();
        img.loaded = false;
        img.onload = function () {
            this.loaded = true;
        };
        img.src = source;

        // Есть разница - между прописаным аттрибутом src изображения и свойством src объекта изображения
        // img, в последнем - полный путь к файлу. Что бы определять, какое изображение показывать, всегда оперируем
        // значением source, которое является относительным пктём к файлу.
        this.images.push({
            'src': source,
            'img': img
        });

        return this;
    }
};