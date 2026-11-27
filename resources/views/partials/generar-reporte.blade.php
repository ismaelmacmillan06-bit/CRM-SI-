{{--
    Overlay "Generando Reporte" + descarga de un Excel por fetch.
    Uso: <a href="{{ ruta }}" onclick="generarReporteExcel(event, this.href)">…</a>
    Incluir una sola vez por página: @include('partials.generar-reporte')
--}}
@once
<div id="gr-overlay"
     style="display:none; position:fixed; inset:0; z-index:9999;
            background:rgba(0,0,0,0.65); backdrop-filter:blur(6px); -webkit-backdrop-filter:blur(6px);
            align-items:center; justify-content:center">
    <div style="background:var(--surface); border-radius:20px; padding:44px 52px; text-align:center;
                box-shadow:0 30px 70px rgba(0,0,0,0.4); max-width:380px; width:90%; animation:grFadeIn .25s ease">
        <div style="width:60px; height:60px; border:5px solid var(--border); border-top-color:#f08a24;
                    border-radius:50%; margin:0 auto 28px; animation:grSpin .85s linear infinite"></div>
        <div style="font-family:'Bricolage Grotesque',sans-serif; font-size:20px; font-weight:700;
                    color:var(--text); margin-bottom:10px; line-height:1.2">
            Generando Reporte
        </div>
        <div style="font-size:14px; color:var(--text-muted)">
            Un momento por favor<span id="gr-dots"></span>
        </div>
    </div>
</div>

<style>
@keyframes grSpin   { to { transform: rotate(360deg); } }
@keyframes grFadeIn { from { opacity:0; transform:scale(.96); } to { opacity:1; transform:scale(1); } }
.btn-generar-reporte { background:#f08a24 !important; border-color:#f08a24 !important; color:#fff !important; font-weight:600; white-space:nowrap; }
.btn-generar-reporte:hover { background:#d9741a !important; border-color:#d9741a !important; }
</style>

<script>
(function () {
    var overlay = document.getElementById('gr-overlay');
    var dotsEl  = document.getElementById('gr-dots');
    var timer   = null;

    function mostrar(si) {
        overlay.style.display = si ? 'flex' : 'none';
        clearInterval(timer);
        if (si) {
            timer = setInterval(function () {
                dotsEl.textContent = '.'.repeat((dotsEl.textContent.length + 1) % 4);
            }, 500);
        }
    }

    // fetch() espera mientras el servidor genera el Excel; al terminar dispara la descarga
    window.generarReporteExcel = function (e, url) {
        e.preventDefault();
        mostrar(true);
        fetch(url, { credentials: 'same-origin' })
            .then(function (res) {
                // Si redirige (sin permiso, sesión vencida) llega HTML: no guardarlo como .xlsx
                var tipo = res.headers.get('Content-Type') || '';
                if (!res.ok || res.redirected || tipo.indexOf('spreadsheetml') === -1) throw new Error('error');
                var cd = res.headers.get('Content-Disposition') || '';
                var m  = cd.match(/filename\*?=(?:UTF-8'')?['"]?([^'"\n;]+)/i);
                var nombre = m ? decodeURIComponent(m[1]) : 'reporte.xlsx';
                return res.blob().then(function (blob) { return { blob: blob, nombre: nombre }; });
            })
            .then(function (data) {
                var href = URL.createObjectURL(data.blob);
                var a = document.createElement('a');
                a.href = href;
                a.download = data.nombre;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                setTimeout(function () { URL.revokeObjectURL(href); }, 2000);
                mostrar(false);
            })
            .catch(function () {
                mostrar(false);
                alert('Error al generar el reporte. Intenta de nuevo.');
            });
    };
})();
</script>
@endonce
