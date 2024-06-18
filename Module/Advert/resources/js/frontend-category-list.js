document.addEventListener("DOMContentLoaded", function () {

    /**
     * "Подать объявление бесплатно и без регистрации в раздел" - имитация ссылок
     * для пользователей, что бы параметры в строке запроса не учитывались ПС.
     */
    let o = document.getElementById('js_add_advert');
    if (o) {
        o.addEventListener('click', function (e) {
            e.preventDefault();

            const set = event.currentTarget.getElementsByTagName('div')[0].dataset;
            let uri = '';
            for (let i in set) {
                if (!set[i]) {
                    continue;
                }

                uri += i + '=' + set[i] + '&';
            }

            let link = event.currentTarget.getElementsByTagName('div')[0].getElementsByTagName('a')[0].getAttribute('href');
            window.location.href = link + (uri ? '?' + uri : '');
        });
    }

    for (const resetButton of document.getElementsByClassName('js_reset_button')) {
        resetButton.addEventListener('click', function (e) {
            (Krugozor.Forms.Checker(resetButton.form)).clear();
            resetButton.form.submit();
        });
    }

});