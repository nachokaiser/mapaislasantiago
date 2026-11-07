document.addEventListener('DOMContentLoaded', function () {

    const header  = document.getElementById('mapa-header');
    const nav     = document.getElementById('mapa-nav');
    const toggle  = header && header.querySelector('.mapa-nav-toggle');
    const links   = Array.from(document.querySelectorAll('.mapa-nav-circuito'));
    if (!header || !nav || !toggle) return;

    // Menú hamburguesa (solo se ve en pantallas angostas, ver el CSS)
    function abrirMenu() {
        header.classList.add('menu-abierto');
        toggle.setAttribute('aria-expanded', 'true');
        toggle.setAttribute('aria-label', 'Cerrar menú de circuitos');
    }

    function cerrarMenu() {
        header.classList.remove('menu-abierto');
        toggle.setAttribute('aria-expanded', 'false');
        toggle.setAttribute('aria-label', 'Abrir menú de circuitos');
    }

    toggle.addEventListener('click', function () {
        if (header.classList.contains('menu-abierto')) {
            cerrarMenu();
        } else {
            abrirMenu();
        }
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && header.classList.contains('menu-abierto')) {
            cerrarMenu();
            toggle.focus();
        }
    });

    // Un toque afuera del header cierra el menú
    document.addEventListener('click', function (e) {
        if (!header.classList.contains('menu-abierto')) return;
        if (header.contains(e.target)) return;
        cerrarMenu();
    });

    // Tocar un circuito abre su panel, sin seleccionar ningún punto
    links.forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            cerrarMenu();
            if (window.MapaCircuitos) {
                window.MapaCircuitos.abrir(Number(link.dataset.circuitoId));
            }
        });
    });

    // El link del circuito abierto queda marcado (lo avisa mapa-circuitos.js)
    document.addEventListener('mapa:circuito', function (e) {
        const abierto = e.detail && e.detail.id;
        links.forEach(function (link) {
            if (abierto && Number(link.dataset.circuitoId) === abierto) {
                link.setAttribute('aria-current', 'true');
            } else {
                link.removeAttribute('aria-current');
            }
        });
    });
});
