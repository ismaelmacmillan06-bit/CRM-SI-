<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>Acceso · MacmillanSI</title>
<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,600;12..96,700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet" />
<style>
  :root{
    --rojo:        #E2231A;
    --rojo-osc:    #B31810;
    --tinta:       #181311;
    --tinta-2:     #221A17;
    --papel:       #FBFAF7;
    --papel-2:     #F2EFE9;
    --linea:       #E4E0D8;
    --gris:        #6F6A64;
    --gris-osc:    #3A3531;
    --radio:       14px;
    --sombra:      0 18px 50px -18px rgba(24,19,17,.28);
    --ease:        cubic-bezier(.22,.61,.36,1);
  }

  *{ box-sizing:border-box; margin:0; padding:0; }
  html,body{ height:100%; }

  body{
    font-family:"Inter", system-ui, sans-serif;
    color:var(--tinta);
    background:var(--papel);
    -webkit-font-smoothing:antialiased;
    text-rendering:optimizeLegibility;
  }

  .shell{
    display:grid;
    grid-template-columns: 1.05fr 1fr;
    min-height:100dvh;
  }

  /* ----------  PANEL DE MARCA  ---------- */
  .marca{
    position:relative;
    overflow:hidden;
    background:
      radial-gradient(120% 120% at 18% 12%, var(--tinta-2) 0%, var(--tinta) 55%, #100C0A 100%);
    color:var(--papel);
    padding:48px 56px;
    display:flex;
    flex-direction:column;
    justify-content:center;
  }

  .logo{ display:flex; align-items:center; gap:14px; position:relative; z-index:3; }
  .logo .wordmark{ line-height:1; }
  .logo .wordmark span{ display:block; font-family:"Bricolage Grotesque"; font-weight:700; font-size:24px; letter-spacing:1.5px; }
  .logo .wordmark small{
    display:block; margin-top:5px; color:var(--rojo);
    font-family:"Bricolage Grotesque"; font-weight:700; font-size:12px; letter-spacing:2px;
  }
  .logo .si{
    width:46px; height:46px; border-radius:50%;
    background:var(--rojo);
    display:grid; place-items:center;
    font-family:"Bricolage Grotesque"; font-weight:700; font-size:20px; color:#fff;
    box-shadow:0 0 0 6px rgba(226,35,26,.16);
  }

  /* molinete animado */
  .molinete{
    position:absolute;
    right:-160px; top:50%;
    transform:translateY(-50%);
    width:620px; height:620px;
    z-index:1;
    opacity:.9;
    animation:girar 48s linear infinite;
  }
  @keyframes girar{ to{ transform:translateY(-50%) rotate(360deg); } }
  .molinete .petalo{ transform-origin:300px 300px; }

  .marca .glow{
    position:absolute; inset:0; z-index:0;
    background:radial-gradient(60% 60% at 78% 50%, rgba(226,35,26,.20), transparent 70%);
    pointer-events:none;
  }

  .mensaje{ position:relative; z-index:3; max-width:26ch; }
  .mensaje .brand{
    font-family:"Bricolage Grotesque"; font-weight:700;
    font-size:clamp(48px, 6vw, 78px); line-height:.98; letter-spacing:-1.5px;
  }
  .mensaje .brand .si{ color:var(--rojo); }
  .mensaje p{ margin-top:20px; color:#C9C2BC; font-size:17px; line-height:1.6; max-width:32ch; }

  .pie-marca{ position:absolute; left:56px; bottom:40px; z-index:3; display:flex; gap:28px; color:#8C857F; font-size:13px; }
  .pie-marca b{ color:var(--papel); font-weight:600; }

  /* ----------  PANEL DE FORMULARIO  ---------- */
  .acceso{
    display:flex; align-items:center; justify-content:center;
    padding:48px 40px;
  }
  .card{ width:100%; max-width:400px; }

  .eyebrow{
    display:inline-flex; align-items:center; gap:8px;
    font-size:12px; font-weight:600; letter-spacing:.8px; text-transform:uppercase;
    color:var(--rojo);
  }
  .eyebrow::before{ content:""; width:18px; height:2px; background:var(--rojo); border-radius:2px; }

  .card h2{
    font-family:"Bricolage Grotesque"; font-weight:600;
    font-size:30px; letter-spacing:-.4px; margin-top:14px;
  }
  .card .sub{ color:var(--gris); margin-top:8px; font-size:15px; }

  .nota{
    display:flex; align-items:center; gap:9px;
    margin-top:18px; padding:11px 14px;
    background:var(--papel-2); border:1px solid var(--linea); border-radius:11px;
    font-size:13px; color:var(--gris-osc);
  }
  .nota svg{ width:16px; height:16px; flex-shrink:0; color:var(--rojo); }

  .nota-success{
    display:flex; align-items:center; gap:9px;
    margin-top:18px; padding:11px 14px;
    background:#f0fff4; border:1px solid #bbf7d0; border-radius:11px;
    font-size:13px; color:#166534;
  }

  form{ margin-top:30px; display:flex; flex-direction:column; gap:18px; }

  .campo label{
    display:block; font-size:13px; font-weight:600; color:var(--gris-osc); margin-bottom:7px;
  }
  .control{ position:relative; }
  .control input{
    width:100%;
    font:inherit; font-size:15px; color:var(--tinta);
    padding:14px 16px;
    background:#fff;
    border:1.5px solid var(--linea);
    border-radius:var(--radio);
    transition:border-color .18s, box-shadow .18s;
  }
  .control input::placeholder{ color:#B4AEA6; }
  .control input:hover{ border-color:#CFC9C0; }
  .control input:focus{
    outline:none;
    border-color:var(--rojo);
    box-shadow:0 0 0 4px rgba(226,35,26,.12);
  }
  .control input[type="password"], .control input.pwd{ padding-right:48px; }

  .toggle-pwd{
    position:absolute; right:8px; top:50%; transform:translateY(-50%);
    width:36px; height:36px; border:none; background:transparent; cursor:pointer;
    border-radius:9px; display:grid; place-items:center; color:var(--gris);
    transition:background .15s, color .15s;
  }
  .toggle-pwd:hover{ background:var(--papel-2); color:var(--tinta); }
  .toggle-pwd svg{ width:20px; height:20px; }

  .opciones{ display:flex; align-items:center; justify-content:space-between; margin-top:-2px; }

  /* switch "mantener sesión" */
  .switch{ display:inline-flex; align-items:center; gap:10px; cursor:pointer; user-select:none; font-size:14px; color:var(--gris-osc); }
  .switch input{ position:absolute; opacity:0; pointer-events:none; }
  .switch .track{
    width:40px; height:23px; border-radius:99px; background:#D9D4CC; position:relative; transition:background .2s;
  }
  .switch .track::after{
    content:""; position:absolute; top:3px; left:3px; width:17px; height:17px; border-radius:50%;
    background:#fff; box-shadow:0 1px 3px rgba(0,0,0,.2); transition:transform .2s var(--ease);
  }
  .switch input:checked + .track{ background:var(--rojo); }
  .switch input:checked + .track::after{ transform:translateX(17px); }
  .switch input:focus-visible + .track{ box-shadow:0 0 0 4px rgba(226,35,26,.18); }

  .link{ font-size:14px; font-weight:500; color:var(--rojo); text-decoration:none; }
  .link:hover{ color:var(--rojo-osc); text-decoration:underline; text-underline-offset:3px; }

  .btn{
    margin-top:6px;
    display:inline-flex; align-items:center; justify-content:center; gap:10px;
    width:100%;
    font:inherit; font-weight:600; font-size:15px; color:#fff;
    padding:15px 20px;
    background:var(--rojo);
    border:none; border-radius:var(--radio); cursor:pointer;
    transition:background .18s, transform .08s, box-shadow .18s;
    box-shadow:0 10px 24px -10px rgba(226,35,26,.6);
  }
  .btn:hover{ background:var(--rojo-osc); }
  .btn:active{ transform:translateY(1px); }
  .btn svg{ width:18px; height:18px; transition:transform .2s var(--ease); }
  .btn:hover svg{ transform:translate(2px,-2px); }
  .btn[aria-busy="true"]{ pointer-events:none; opacity:.85; }
  .btn[aria-busy="true"] .arrow{ display:none; }
  .btn .spinner{ display:none; width:18px; height:18px; border:2.5px solid rgba(255,255,255,.4); border-top-color:#fff; border-radius:50%; animation:spin .7s linear infinite; }
  .btn[aria-busy="true"] .spinner{ display:block; }
  @keyframes spin{ to{ transform:rotate(360deg); } }

  .error-msg{
    display:none; align-items:center; gap:8px;
    font-size:13px; color:var(--rojo-osc); margin-top:6px;
  }
  .campo.invalido .control input{ border-color:var(--rojo); }
  .campo.invalido .error-msg{ display:flex; }

  .pie-form{
    margin-top:34px; padding-top:22px; border-top:1px solid var(--linea);
    display:flex; align-items:center; justify-content:space-between;
    font-size:13px; color:var(--gris);
  }

  /* ----------  RESPONSIVE  ---------- */
  @media (max-width: 880px){
    .shell{ grid-template-columns:1fr; }
    .marca{
      padding:34px 28px 96px;
      min-height:auto;
      justify-content:flex-start;
    }
    .molinete{ width:380px; height:380px; right:-130px; top:auto; bottom:-110px; transform:none; animation:girar-m 48s linear infinite; }
    @keyframes girar-m{ to{ transform:rotate(360deg); } }
    .mensaje{ margin-top:10px; }
    .mensaje .brand{ font-size:46px; }
    .pie-marca{ display:none; }
    .acceso{ padding:38px 24px 56px; }
  }

  @media (prefers-reduced-motion: reduce){
    .molinete{ animation:none; }
    *{ transition:none !important; }
  }
</style>
</head>
<body>
<main class="shell">

  {{-- ====== PANEL DE MARCA ====== --}}
  <section class="marca">
    <div class="glow"></div>

    {{-- molinete de pétalos --}}
    <svg class="molinete" viewBox="0 0 600 600" aria-hidden="true">
      <g>
        <path class="petalo" d="M300 300 C300 220 340 150 420 130 C400 210 360 270 300 300 Z" fill="#E2231A"/>
        <path class="petalo" d="M300 300 C380 300 450 340 470 420 C390 400 330 360 300 300 Z" fill="none" stroke="#3A302C" stroke-width="2"/>
        <path class="petalo" d="M300 300 C300 380 260 450 180 470 C200 390 240 330 300 300 Z" fill="#B31810"/>
        <path class="petalo" d="M300 300 C220 300 150 260 130 180 C210 200 270 240 300 300 Z" fill="none" stroke="#4A3E39" stroke-width="2"/>
        <path class="petalo" d="M300 300 C356 244 432 232 492 268 C436 324 360 336 300 300 Z" fill="#E2231A" opacity=".92"/>
        <path class="petalo" d="M300 300 C356 356 368 432 332 492 C276 436 264 360 300 300 Z" fill="none" stroke="#3A302C" stroke-width="2"/>
        <path class="petalo" d="M300 300 C244 356 168 368 108 332 C164 276 240 264 300 300 Z" fill="#B31810" opacity=".9"/>
        <path class="petalo" d="M300 300 C244 244 232 168 268 108 C324 164 336 240 300 300 Z" fill="none" stroke="#4A3E39" stroke-width="2"/>
        <circle cx="300" cy="300" r="16" fill="#E2231A"/>
      </g>
    </svg>

    <div class="mensaje">
      <div class="brand"><span class="si">SI</span>Central</div>
      <p>Plataforma para la gestión de colegios Solución Integral.</p>
    </div>

    <div class="pie-marca">
      <span>Soporte <b>L–V · 9 a 18 h</b></span>
      <span>si@macmillaneducation.com</span>
    </div>
  </section>

  {{-- ====== PANEL DE FORMULARIO ====== --}}
  <section class="acceso">
    <div class="card">
      <span class="eyebrow">Acceso Equipo SI</span>
      <h2>Bienvenido de nuevo</h2>
      <p class="sub">Ingresa con tu usuario y contraseña.</p>

      @if(session('status'))
        <div class="nota-success">{{ session('status') }}</div>
      @endif

      <div class="nota">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
        </svg>
        Solo el administrador puede generar acceso al sistema.
      </div>

      <form method="POST" action="{{ route('login') }}" novalidate>
        @csrf

        <div class="campo @error('email') invalido @enderror" id="campoEmail">
          <label for="email">Usuario</label>
          <div class="control">
            <input type="text" id="email" name="email"
                   value="{{ old('email') }}"
                   autocomplete="username" autofocus required />
          </div>
          <p class="error-msg">
            @error('email'){{ $message }}@else Introduce un usuario válido. @enderror
          </p>
        </div>

        <div class="campo @error('password') invalido @enderror" id="campoPwd">
          <label for="password">Contraseña</label>
          <div class="control">
            <input type="password" id="password" name="password" class="pwd"
                   autocomplete="current-password" placeholder="••••••••" required />
            <button type="button" class="toggle-pwd" id="togglePwd"
                    aria-label="Mostrar contraseña" aria-pressed="false">
              <svg id="iconOjo" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
              </svg>
            </button>
          </div>
          <p class="error-msg">
            @error('password'){{ $message }}@else Introduce tu contraseña. @enderror
          </p>
        </div>

        <div class="opciones">
          <label class="switch">
            <input type="checkbox" name="remember" id="remember" />
            <span class="track"></span>
            Mantener sesión activa
          </label>
          <a class="link" href="{{ route('password.request') }}">¿Olvidaste tu contraseña?</a>
        </div>

        <button type="submit" class="btn" id="btnAcceder">
          <span class="label">Acceder</span>
          <svg class="arrow" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round">
            <path d="M7 17 17 7M9 7h8v8"/>
          </svg>
          <span class="spinner"></span>
        </button>
      </form>

      <div class="pie-form">
        <span>© Macmillan Castillo SI</span>
        <span>Solo el administrador puede crear cuentas</span>
      </div>
    </div>
  </section>

</main>

<script>
  // --- Mostrar / ocultar contraseña ---
  const togglePwd = document.getElementById('togglePwd');
  const pwd       = document.getElementById('password');
  const iconOjo   = document.getElementById('iconOjo');

  togglePwd.addEventListener('click', () => {
    const visible = pwd.type === 'text';
    pwd.type = visible ? 'password' : 'text';
    togglePwd.setAttribute('aria-pressed', String(!visible));
    togglePwd.setAttribute('aria-label', visible ? 'Mostrar contraseña' : 'Ocultar contraseña');
    iconOjo.innerHTML = visible
      ? '<path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>'
      : '<path d="M9.9 4.2A10.9 10.9 0 0 1 12 4c6.5 0 10 7 10 7a17.6 17.6 0 0 1-3 3.7M6.6 6.6A17.6 17.6 0 0 0 2 11s3.5 7 10 7a10.9 10.9 0 0 0 3.6-.6"/><path d="m2 2 20 20"/><path d="M9.5 9.5a3 3 0 0 0 4.2 4.2"/>';
  });

  // --- Validación cliente + spinner ---
  const form       = document.querySelector('form');
  const campoEmail = document.getElementById('campoEmail');
  const campoPwd   = document.getElementById('campoPwd');
  const email      = document.getElementById('email');
  const btn        = document.getElementById('btnAcceder');

  const esEmailValido = (v) => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim());

  function limpiar(campo){ campo.classList.remove('invalido'); }
  email.addEventListener('input', () => limpiar(campoEmail));
  pwd.addEventListener('input',   () => limpiar(campoPwd));

  form.addEventListener('submit', (e) => {
    let ok = true;

    if (!esEmailValido(email.value))         { campoEmail.classList.add('invalido'); ok = false; }
    if (pwd.value.trim().length === 0)       { campoPwd.classList.add('invalido');   ok = false; }

    if (!ok) {
      e.preventDefault();
      (campoEmail.classList.contains('invalido') ? email : pwd).focus();
      return;
    }

    // Muestra spinner mientras el POST viaja al servidor
    btn.setAttribute('aria-busy', 'true');
  });
</script>
</body>
</html>
