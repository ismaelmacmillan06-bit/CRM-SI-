<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>MacmillanSI — Iniciar sesión</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;600;700&family=Inter:wght@400;500&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Inter', sans-serif;
            height: 100vh;
            display: flex;
            background: #f5f5f5;
            overflow: hidden;
        }

        /* ── LEFT PANEL ──────────────────────────────── */
        .left-panel {
            width: 55%;
            background: linear-gradient(145deg, #a50e20 0%, #c81a30 40%, #e94560 100%);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: flex-start;
            padding: 64px 72px;
            position: relative;
            overflow: hidden;
        }

        /* Decorative blobs */
        .left-panel::before {
            content: '';
            position: absolute;
            width: 400px; height: 400px;
            border-radius: 50%;
            background: rgba(255,255,255,0.06);
            top: -100px; right: -80px;
        }
        .left-panel::after {
            content: '';
            position: absolute;
            width: 280px; height: 280px;
            border-radius: 50%;
            background: rgba(255,255,255,0.05);
            bottom: -60px; left: 40px;
        }

        /* Logo */
        .logo-wrap {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 60px;
            position: relative; z-index: 1;
        }

        .logo-circle {
            width: 72px; height: 72px;
            border-radius: 50%;
            background: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            position: relative;
            overflow: hidden;
        }
        .logo-waves {
            position: absolute;
            top: 10px;
            width: 100%;
            display: flex;
            justify-content: center;
        }
        .logo-si {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 30px;
            font-weight: 700;
            color: #c81a30;
            line-height: 1;
            margin-top: 12px;
        }
        .logo-text-wrap {
            display: flex;
            flex-direction: column;
        }
        .logo-brand {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 22px;
            font-weight: 400;
            color: white;
            letter-spacing: -0.3px;
            line-height: 1.2;
        }
        .logo-brand strong {
            font-weight: 700;
        }
        .logo-sub {
            font-size: 11px;
            color: rgba(255,255,255,0.65);
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-top: 3px;
        }

        /* Welcome copy */
        .welcome-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 44px;
            font-weight: 700;
            color: white;
            line-height: 1.1;
            margin-bottom: 20px;
            position: relative; z-index: 1;
        }
        .welcome-subtitle {
            font-size: 16px;
            color: rgba(255,255,255,0.75);
            line-height: 1.7;
            max-width: 420px;
            position: relative; z-index: 1;
        }

        /* Decorative dots grid */
        .dots-grid {
            position: absolute;
            bottom: 48px; right: 48px;
            display: grid;
            grid-template-columns: repeat(6, 10px);
            gap: 8px;
            opacity: 0.18;
        }
        .dots-grid span {
            width: 4px; height: 4px;
            border-radius: 50%;
            background: white;
            display: block;
        }

        /* ── RIGHT PANEL ─────────────────────────────── */
        .right-panel {
            width: 45%;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 64px 56px;
        }

        .form-box {
            width: 100%;
            max-width: 380px;
        }

        .form-title {
            font-family: 'Space Grotesk', sans-serif;
            font-size: 30px;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 8px;
        }
        .form-caption {
            font-size: 14px;
            color: #888;
            margin-bottom: 36px;
        }

        /* Session error */
        .alert-error {
            background: #fff0f2;
            border: 1px solid #f8c4cb;
            color: #c81a30;
            font-size: 13px;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
        }

        /* Form fields */
        .field-group {
            margin-bottom: 20px;
        }
        .field-label {
            display: block;
            font-size: 13px;
            font-weight: 500;
            color: #444;
            margin-bottom: 8px;
        }
        .field-wrap {
            position: relative;
        }
        .field-icon {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #aaa;
            pointer-events: none;
            display: flex;
        }
        .field-input {
            width: 100%;
            padding: 13px 14px 13px 42px;
            border: 1.5px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Inter', sans-serif;
            color: #1a1a2e;
            background: #fafafa;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
        }
        .field-input:focus {
            border-color: #c81a30;
            background: white;
            box-shadow: 0 0 0 3px rgba(200,26,48,0.1);
        }
        .field-error {
            font-size: 12px;
            color: #c81a30;
            margin-top: 5px;
            display: block;
        }

        /* Remember me */
        .remember-row {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 28px;
        }
        .remember-row input[type="checkbox"] {
            width: 16px; height: 16px;
            accent-color: #c81a30;
            cursor: pointer;
        }
        .remember-row label {
            font-size: 13px;
            color: #666;
            cursor: pointer;
        }

        /* Submit button */
        .btn-submit {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #c81a30, #e94560);
            color: white;
            border: none;
            border-radius: 10px;
            font-family: 'Space Grotesk', sans-serif;
            font-size: 15px;
            font-weight: 600;
            letter-spacing: 0.3px;
            cursor: pointer;
            transition: opacity 0.2s, transform 0.1s;
        }
        .btn-submit:hover  { opacity: 0.92; }
        .btn-submit:active { transform: scale(0.99); }

        /* Footer */
        .form-footer {
            text-align: center;
            margin-top: 24px;
            font-size: 12px;
            color: #bbb;
        }

        /* Responsive: stack on small screens */
        @media (max-width: 768px) {
            body { flex-direction: column; overflow: auto; }
            .left-panel { width: 100%; padding: 48px 32px 40px; }
            .welcome-title { font-size: 32px; }
            .right-panel { width: 100%; padding: 48px 32px; }
        }
    </style>
</head>
<body>

    {{-- ── LEFT PANEL ── --}}
    <div class="left-panel">

        {{-- Logo --}}
        <div class="logo-wrap">
            <div class="logo-circle">
                <div class="logo-waves">
                    <svg width="44" height="14" viewBox="0 0 44 14" fill="none">
                        <path d="M2 10 Q7 2 12 10 Q17 2 22 10 Q27 2 32 10 Q37 2 42 10"
                              stroke="#c81a30" stroke-width="2.2" fill="none" stroke-linecap="round"/>
                    </svg>
                </div>
                <div class="logo-si">Sí</div>
            </div>
            <div class="logo-text-wrap">
                <div class="logo-brand">macmillan <strong>castillo</strong></div>
                <div class="logo-sub">Solución Integral</div>
            </div>
        </div>

        {{-- Welcome copy --}}
        <div class="welcome-title">
            Bienvenido<br>de vuelta
        </div>
        <div class="welcome-subtitle">
            Sistema para la administración y control de los Colegios Solución Integral
        </div>

        {{-- Decorative dots --}}
        <div class="dots-grid">
            @for($r = 0; $r < 5; $r++)
                @for($c = 0; $c < 6; $c++)
                    <span></span>
                @endfor
            @endfor
        </div>

    </div>

    {{-- ── RIGHT PANEL ── --}}
    <div class="right-panel">
        <div class="form-box">

            <div class="form-title">Iniciar sesión</div>
            <div class="form-caption">Ingresa tus credenciales para continuar</div>

            {{-- Session status --}}
            @if(session('status'))
                <div class="alert-error" style="background:#f0fff4; border-color:#bbf7d0; color:#166534">
                    {{ session('status') }}
                </div>
            @endif

            {{-- Auth errors --}}
            @if($errors->any())
                <div class="alert-error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- Usuario --}}
                <div class="field-group">
                    <label class="field-label" for="email">Usuario</label>
                    <div class="field-wrap">
                        <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                        <input id="email" name="email" type="text" class="field-input"
                               value="{{ old('email') }}" required autofocus autocomplete="username">
                    </div>
                    @error('email')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Password --}}
                <div class="field-group">
                    <label class="field-label" for="password">Contraseña</label>
                    <div class="field-wrap">
                        <svg class="field-icon" width="16" height="16" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                        </svg>
                        <input id="password" name="password" type="password" class="field-input"
                               required autocomplete="current-password"
                               placeholder="••••••••">
                    </div>
                    @error('password')
                        <span class="field-error">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Remember me --}}
                <div class="remember-row">
                    <input id="remember_me" name="remember" type="checkbox">
                    <label for="remember_me">Recordarme</label>
                </div>

                <button type="submit" class="btn-submit">Entrar</button>
            </form>

            <div class="form-footer">
                Solo el administrador puede crear cuentas nuevas
            </div>
        </div>
    </div>

</body>
</html>
