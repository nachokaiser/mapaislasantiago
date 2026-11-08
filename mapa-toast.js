// Aviso de bienvenida: "¡Hola! Seleccioná un circuito...".
//
// - Se muestra UNA vez por sesión (sessionStorage: dura mientras la pestaña esté
//   abierta, sobrevive a recargas, y una pestaña nueva es una sesión nueva).
// - Aparece 0,5 s después de que se ve el mapa (cuando se va la portada) con un
//   fade, y se queda 10 s antes de salir con un fade tranquilo.
// - Si antes de eso la persona toca un punto o un circuito (se abre el panel), o
//   toca la X, sale enseguida con un fade rápido.
document.addEventListener('DOMContentLoaded', function () {

    const toast = document.getElementById('mapa-toast');
    if (!toast) return;

    const CLAVE      = 'toastBienvenidaVisto';
    const ESPERA     = 500;    // ms entre que se ve el mapa y aparece el toast
    const PERMANENCIA = 10000; // ms que se queda a la vista
    const FADE_ENTRA = '0.4s';
    const FADE_SALE  = '0.6s';
    const FADE_RAPIDO = '0.2s';

    // sessionStorage puede estar bloqueado (modo privado, cookies cortadas): en
    // ese caso simplemente no se recuerda y el toast sale en cada carga.
    try {
        if (sessionStorage.getItem(CLAVE)) return;
    } catch (e) {}

    let visible   = false;
    let terminado = false;
    let timerSalida = null;
    let observador  = null;

    function ocultar(fade) {
        if (terminado) return;
        terminado = true;
        clearTimeout(timerSalida);
        if (observador) observador.disconnect();
        toast.style.setProperty('--toast-fade', fade);
        toast.classList.remove('is-visible');
    }

    function mostrar() {
        if (terminado) return;
        visible = true;
        try { sessionStorage.setItem(CLAVE, '1'); } catch (e) {}

        toast.style.setProperty('--toast-fade', FADE_ENTRA);
        toast.classList.add('is-visible');

        timerSalida = setTimeout(function () { ocultar(FADE_SALE); }, PERMANENCIA);
    }

    toast.querySelector('.mapa-toast-cerrar').addEventListener('click', function () {
        ocultar(FADE_RAPIDO);
    });

    // Tocar un punto o un circuito abre el panel (en desktop y en mobile): se
    // detecta ahí en vez de cablear cada punto y cada link.
    const panel = document.getElementById('mapa-panel');
    if (panel) {
        observador = new MutationObserver(function () {
            if (panel.classList.contains('activo')) ocultar(FADE_RAPIDO);
        });
        observador.observe(panel, { attributes: true, attributeFilter: ['class'] });
    }

    // El reloj arranca cuando el mapa queda a la vista: al irse la portada (que
    // avisa con 'portada:cerrada'), o enseguida si no hay portada.
    function programar() {
        setTimeout(mostrar, ESPERA);
    }

    if (document.getElementById('portada')) {
        document.addEventListener('portada:cerrada', programar, { once: true });
    } else {
        programar();
    }
});
