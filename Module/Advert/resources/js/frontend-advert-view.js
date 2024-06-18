document.addEventListener("DOMContentLoaded", function () {

    let imitationLinkOuterList = document.querySelectorAll('.js-imitation-link');
    for (let imitationLinkOuter of imitationLinkOuterList) {
        if (imitationLinkOuter) {
            imitationLinkOuter.addEventListener('click', function (e) {
                window.open(e.target.getAttribute("data-url"), '_blank');
            });
        }
    }

    Krugozor.UI.popup.image.loadImagesBySelector('.thumbnails');

    let imitationLinkPhoneList = document.querySelectorAll('.view_phone');
    for (let imitationLinkPhone of imitationLinkPhoneList) {
        if (imitationLinkPhone) {
            imitationLinkPhone.addEventListener('click', function (e) {
                const target = e.target;
                const advert_id = e.target.getAttribute('data-id');

                const img = document.createElement('IMG');
                img.src = '/img/local/system/icon/ajax-loader-small.gif';

                const parent = target.parentNode;
                parent.replaceChild(img, target.parentNode.firstChild);

                const ajax = new Krugozor.Ajax();
                ajax.setObserverState(function (ajx, xhr) {
                    const phone = this.phone || 'Не удалось получить телефон';

                    const a = document.createElement('A');
                    a.setAttribute('href', 'tel:' + phone);
                    a.appendChild(document.createTextNode(phone));

                    setTimeout(function () {
                        parent.replaceChild(a, img);
                    }, 500);
                }, true);

                ajax.get('/advert/frontend-ajax-get-phone/id/' + advert_id);
            });
        }
    }

    let imitationLinkEmailList = document.querySelectorAll('.view_email');
    for (let imitationLinkEmail of imitationLinkEmailList) {
        if (imitationLinkEmail) {
            imitationLinkEmail.addEventListener('click', function (e) {
                const target = e.target;
                const advert_id = e.target.getAttribute('data-id');
                const hash = e.target.getAttribute('data-hash');

                const img = document.createElement('IMG');
                img.src = '/img/local/system/icon/ajax-loader-small.gif';

                const parent = target.parentNode;
                parent.replaceChild(img, target.parentNode.firstChild);

                const ajax = new Krugozor.Ajax();
                ajax.setObserverState(function (ajx, xhr) {
                    const email = this.email || 'Не удалось получить email-адрес';

                    const a = document.createElement('A');
                    a.setAttribute('href', 'mailto:' + email);
                    a.appendChild(document.createTextNode(email));

                    setTimeout(function () {
                        parent.replaceChild(a, img);
                    }, 500);
                }, true);

                ajax.get('/advert/frontend-ajax-get-email/id/' + advert_id + '/hash/' + hash);
            });
        }
    }

});
