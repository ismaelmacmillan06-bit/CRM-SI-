@php
    $productosSel = (array) old('productos', $visit->productos ?? []);
    $iconosProducto = [
        'MEE'           => ['📘', '#0ea5e9'],
        'Ree Roo Reads' => ['🦘', '#f59e0b'],
        'Lego'          => ['🧱', '#dc2626'],
        'Equipamiento'  => ['🖥️', '#64748b'],
        'Global Educa'  => ['🌎', '#16a34a'],
    ];
@endphp

<style>
    .prod-grid { display:flex; flex-wrap:wrap; gap:10px; }
    .prod-chip { position:relative; cursor:pointer; }
    .prod-chip input { position:absolute; opacity:0; pointer-events:none; }
    .prod-chip span {
        display:inline-flex; align-items:center; gap:8px; padding:9px 14px;
        border:1.5px solid var(--border); border-radius:999px; background:var(--surface);
        font-size:13.5px; font-weight:500; color:var(--text);
        transition:border-color .15s, background .15s, box-shadow .15s;
    }
    .prod-chip span::after {
        content:''; width:16px; height:16px; border-radius:50%; border:1.5px solid var(--border);
        margin-left:2px; transition:all .15s;
    }
    .prod-chip:hover span { border-color:var(--c); }
    .prod-chip input:checked + span {
        border-color:var(--c); color:var(--c); font-weight:600;
        background:color-mix(in srgb, var(--c) 10%, var(--surface));
        box-shadow:0 0 0 3px color-mix(in srgb, var(--c) 14%, transparent);
    }
    .prod-chip input:checked + span::after {
        content:'✓'; background:var(--c); border-color:var(--c); color:#fff;
        font-size:10px; line-height:13px; text-align:center;
    }
    .prod-chip input:focus-visible + span { outline:2px solid var(--c); outline-offset:2px; }
</style>

<div class="form-group">
    <label class="form-label">Producto * <small style="font-weight:400; color:var(--text-muted)">(puedes elegir varios)</small></label>
    <div class="prod-grid">
        @foreach(\App\Models\Visit::PRODUCTOS as $producto)
            @php [$ico, $color] = $iconosProducto[$producto] ?? ['📦', '#64748b']; @endphp
            <label class="prod-chip" style="--c:{{ $color }}">
                <input type="checkbox" name="productos[]" value="{{ $producto }}" class="sesion-producto"
                       {{ in_array($producto, $productosSel, true) ? 'checked' : '' }}>
                <span>{{ $ico }} {{ $producto }}</span>
            </label>
        @endforeach
    </div>
    @error('productos')<small style="color:var(--danger)">{{ $message }}</small>@enderror
    @error('productos.*')<small style="color:var(--danger)">{{ $message }}</small>@enderror
</div>

<script>
    (function () {
        const checks = document.querySelectorAll('.sesion-producto');
        function sync() {
            const alguno = Array.from(checks).some(c => c.checked);
            checks.forEach(c => c.required = !alguno);
        }
        checks.forEach(c => c.addEventListener('change', sync));
        sync();
    })();
</script>
