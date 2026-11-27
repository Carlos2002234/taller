/**
 * Microsoft Clarity: el fragmento oficial, en un archivo aparte porque la CSP no permite JS dentro del HTML.
 * El layout lo incluye SOLO en producción y fuera del panel, con el ID en data-clarity (CLARITY_ID del .env).
 */
(function (c, l, a, r, i, t, y) {
    if (!/^[a-z0-9]+$/i.test(i)) {
        return;
    }
    c[a] = c[a] || function () { (c[a].q = c[a].q || []).push(arguments); };
    t = l.createElement(r); t.async = 1; t.src = 'https://www.clarity.ms/tag/' + i;
    y = l.getElementsByTagName(r)[0]; y.parentNode.insertBefore(t, y);
})(window, document, 'clarity', 'script', (document.currentScript && document.currentScript.dataset.clarity) || '');
