document.addEventListener("DOMContentLoaded", function () {

    let imitationLinkOuterList = document.querySelectorAll('.js-imitation-link');
    for (let imitationLinkOuter of imitationLinkOuterList) {
        if (imitationLinkOuter) {
            imitationLinkOuter.addEventListener('click', function (e) {
                window.open(e.target.getAttribute("data-url"), '_blank');
            });
        }
    }

});