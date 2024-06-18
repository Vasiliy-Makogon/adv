"use strict";

document.addEventListener("DOMContentLoaded", function() {

    const list = document.querySelectorAll(".advert_special,.advert_vip");
    [...list].forEach(function (item) {

        item.addEventListener('click', function (e) {
            /* URL платежа */
            const paymentUrl = item.firstChild.getAttribute('href');

            /* id всплывающих блоков называются как имена классов ссылок, т.е. advert_special, advert_vip */
            const popup = document.getElementById(item.className);

            /* кнопки дайствий */
            const links = popup.querySelectorAll('.js-link');
            const linkPayment = links.item(0);
            const linkFail = links.item(1);

            const approvalCheckbox = popup.querySelectorAll('input[type="checkbox"]')[0];
            approvalCheckbox.style.outline = 'none';

            const linkPaymentEvent = function (e) {
                if (!approvalCheckbox.checked) {
                    alert('Перед проведением платежа вы должны ознакомиться с публичной офертой и условиями использования сайта');
                    approvalCheckbox.style.outline = '3px solid #cc0000';
                    e.preventDefault();
                }

                if (item.className == 'advert_vip') {
                    ym(52711954,'reachGoal','vipClick');
                } else if (item.className == 'advert_special') {
                    ym(52711954,'reachGoal','specialClick');
                }
            };

            const linkFailEvent = function (e) {
                linkPayment.removeEventListener('click', linkPaymentEvent);
                linkFail.removeEventListener('click', linkFailEvent);

                popup.style.visibility = 'hidden';
                e.preventDefault();
            };

            linkPayment.setAttribute('href', paymentUrl);
            linkPayment.addEventListener('click', linkPaymentEvent, false);

            popup.style.visibility = 'visible';

            linkFail.addEventListener('click', linkFailEvent, false);

            e.preventDefault();
        });
    });

    window.addEventListener('resize', function (e) {
        var list = document.querySelectorAll(".payment_popup");
        [...list].forEach(function (item) {
            item.style.height = document.body.scrollHeight + 'px';
        });
    })
});