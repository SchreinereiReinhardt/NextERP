document.addEventListener('DOMContentLoaded', function () {
    const card = document.getElementById('betrio-android-app-card');
    const dismiss = document.getElementById('betrio-android-app-dismiss');
    if (!card || !dismiss) return;

    const key = 'betrio.androidAppNotice.dismissed';
    try {
        if (window.localStorage.getItem(key) === '1') {
            card.classList.add('erp-android-app-hidden');
            return;
        }
    } catch (e) {}

    dismiss.addEventListener('click', function (event) {
        event.preventDefault();
        event.stopPropagation();
        card.classList.add('erp-android-app-hidden');
        try { window.localStorage.setItem(key, '1'); } catch (e) {}
    });
});
