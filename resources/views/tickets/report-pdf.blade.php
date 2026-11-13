<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Tickets — {{ $school->name }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@600;700;800&family=Inter:wght@400;500;600;700&display=swap');
        :root {
            --accent: #E2231A;
            --text: #181311;
            --muted: #6F6A64;
            --border: #E4E0D8;
            --surface: #FBFAF7;
            --surface2: #F2EFE9;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f3ee;
            color: var(--text);
            padding: 24px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .toolbar {
            max-width: 1020px;
            margin: 0 auto 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
        }
        .btn {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 9px 16px; border-radius: 8px; font-size: 13px; font-weight: 600;
            text-decoration: none; cursor: pointer; border: 1px solid transparent;
        }
        .btn-primary { background: var(--accent); color: #fff; }
        .btn-secondary { background: #fff; color: var(--text); border-color: var(--border); }
        .page {
            max-width: 1020px;
            margin: 0 auto;
            background: #fff;
            border: 1px solid var(--border);
            border-radius: 14px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(24,19,17,0.08);
        }
        .header {
            background: linear-gradient(135deg, #181311 0%, #6b1512 48%, #E2231A 100%);
            color: #fff;
            padding: 28px 32px;
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 20px;
            flex-wrap: wrap;
            border-bottom: 4px solid #fbbf24;
        }
        .header-kicker {
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: rgba(255,255,255,0.75);
            margin-bottom: 4px;
        }
        .header h1 { font-family: 'Bricolage Grotesque', sans-serif; font-size: 24px; font-weight: 800; }
        .header-meta { font-size: 12.5px; color: rgba(255,255,255,0.88); margin-top: 6px; }
        .header-pills { display: flex; flex-direction: column; align-items: flex-end; gap: 6px; }
        .nexus-pill {
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.32);
            padding: 6px 14px;
            border-radius: 999px;
            font-size: 12px;
            font-weight: 600;
        }
        .body { padding: 28px 32px; }
        .kpis {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 12px;
            margin-bottom: 24px;
        }
        .kpi {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 14px 16px;
        }
        .kpi-label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--muted); }
        .kpi-val { font-family: 'Bricolage Grotesque', sans-serif; font-size: 28px; font-weight: 800; margin-top: 4px; }
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
            margin-bottom: 26px;
        }
        .chart-card {
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 16px 18px;
            background: var(--surface);
            break-inside: avoid;
        }
        .chart-title {
            font-family: 'Bricolage Grotesque', sans-serif;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 12px;
        }
        .bar-row { display: grid; grid-template-columns: 110px 1fr 34px; align-items: center; gap: 10px; margin-bottom: 8px; font-size: 12px; }
        .bar-track { height: 10px; background: #e8e4dc; border-radius: 999px; overflow: hidden; }
        .bar-fill { height: 100%; border-radius: 999px; }
        table { width: 100%; border-collapse: collapse; font-size: 12px; }
        th {
            background: #181311; color: #fff; text-align: left;
            padding: 9px 10px; font-size: 11px; text-transform: uppercase; letter-spacing: .4px;
        }
        td { padding: 9px 10px; border-bottom: 1px solid var(--border); vertical-align: top; }
        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 11px; font-weight: 600; white-space: nowrap;
        }
        @media print {
            body { background: #fff; padding: 0; }
            .toolbar { display: none; }
            .page { border: 0; box-shadow: none; border-radius: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <a href="{{ route('schools.tickets.index', $school) }}" class="btn btn-secondary">← Regresar a Tickets</a>
        <div style="display:flex; gap:8px">
            <a href="{{ route('schools.tickets.report.excel', array_filter(['school' => $school, 'desde' => $desde, 'hasta' => $hasta])) }}" class="btn btn-secondary">📊 Descargar Excel</a>
            <button type="button" onclick="window.print()" class="btn btn-primary">🖨️ Imprimir / Guardar PDF</button>
        </div>
    </div>

    <div class="page">
        <div class="header">
            <div style="display:flex; align-items:center; gap:18px; flex-wrap:wrap">
                <img src="{{ asset('images/logo-si-pdf.png') }}" alt="Macmillan SI"
                     style="height:50px; width:auto; object-fit:contain; filter:brightness(0) invert(1); flex-shrink:0">
                <div>
                    <div class="header-kicker">Macmillan SI · Reporte Ejecutivo</div>
                    <h1>{{ $school->name }}</h1>
                    <div class="header-meta">Generado el {{ $generado }}</div>
                </div>
            </div>
            <div class="header-pills">
                <div class="nexus-pill">🆔 Nexus: {{ $school->nexus_id ?: 'Sin Nexus ID' }}</div>
                <div class="nexus-pill">📅 {{ $periodoLabel }}</div>
            </div>
        </div>

        <div class="body">
            <div class="kpis">
                <div class="kpi">
                    <div class="kpi-label">Total tickets</div>
                    <div class="kpi-val">{{ $total }}</div>
                </div>
                <div class="kpi" style="border-left:4px solid #ef4444">
                    <div class="kpi-label">Abiertos</div>
                    <div class="kpi-val" style="color:#dc2626">{{ $porStatus['Abierto'] }}</div>
                </div>
                <div class="kpi" style="border-left:4px solid #f59e0b">
                    <div class="kpi-label">En proceso</div>
                    <div class="kpi-val" style="color:#d97706">{{ $porStatus['En proceso'] }}</div>
                </div>
                <div class="kpi" style="border-left:4px solid #22c55e">
                    <div class="kpi-label">Cerrados</div>
                    <div class="kpi-val" style="color:#16a34a">{{ $porStatus['Cerrado'] }}</div>
                </div>
            </div>

            <div class="charts-grid">
                {{-- Gráfico SVG Donut: Estatus --}}
                @php
                    $r = 44; $circ = 2 * M_PI * $r;
                    $den = max(1, $total);
                    $segAbierto = ($porStatus['Abierto'] / $den) * $circ;
                    $segProceso = ($porStatus['En proceso'] / $den) * $circ;
                    $segCerrado = ($porStatus['Cerrado'] / $den) * $circ;
                @endphp
                <div class="chart-card">
                    <div class="chart-title">Distribución por Estatus</div>
                    <div style="display:flex; align-items:center; gap:20px">
                        <svg width="120" height="120" viewBox="0 0 120 120">
                            <circle cx="60" cy="60" r="{{ $r }}" fill="none" stroke="#e8e4dc" stroke-width="18"/>
                            @if($total > 0)
                            <circle cx="60" cy="60" r="{{ $r }}" fill="none" stroke="#ef4444" stroke-width="18"
                                    stroke-dasharray="{{ $segAbierto }} {{ $circ }}" transform="rotate(-90 60 60)"/>
                            <circle cx="60" cy="60" r="{{ $r }}" fill="none" stroke="#f59e0b" stroke-width="18"
                                    stroke-dasharray="{{ $segProceso }} {{ $circ }}"
                                    stroke-dashoffset="-{{ $segAbierto }}" transform="rotate(-90 60 60)"/>
                            <circle cx="60" cy="60" r="{{ $r }}" fill="none" stroke="#22c55e" stroke-width="18"
                                    stroke-dasharray="{{ $segCerrado }} {{ $circ }}"
                                    stroke-dashoffset="-{{ $segAbierto + $segProceso }}" transform="rotate(-90 60 60)"/>
                            @endif
                            <text x="60" y="64" text-anchor="middle" font-size="18" font-weight="800" fill="#181311">{{ $total }}</text>
                        </svg>
                        <div style="font-size:12.5px; display:grid; gap:6px">
                            <div><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#ef4444;margin-right:6px"></span>Abiertos: <strong>{{ $porStatus['Abierto'] }}</strong></div>
                            <div><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#f59e0b;margin-right:6px"></span>En proceso: <strong>{{ $porStatus['En proceso'] }}</strong></div>
                            <div><span style="display:inline-block;width:10px;height:10px;border-radius:50%;background:#22c55e;margin-right:6px"></span>Cerrados: <strong>{{ $porStatus['Cerrado'] }}</strong></div>
                        </div>
                    </div>
                </div>

                {{-- Gráfico Barras: Prioridad --}}
                @php $maxPrio = max(1, max($porPrioridad)); @endphp
                <div class="chart-card">
                    <div class="chart-title">Tickets por Prioridad</div>
                    @foreach(['Alta' => '#ef4444', 'Media' => '#f59e0b', 'Baja' => '#22c55e'] as $lbl => $clr)
                        @php $cnt = $porPrioridad[$lbl] ?? 0; $pct = round(($cnt / $maxPrio) * 100); @endphp
                        <div class="bar-row">
                            <span>{{ $lbl }}</span>
                            <div class="bar-track"><div class="bar-fill" style="width:{{ $pct }}%; background:{{ $clr }}"></div></div>
                            <strong style="text-align:right">{{ $cnt }}</strong>
                        </div>
                    @endforeach
                </div>

                {{-- Gráfico Barras: Medio --}}
                @php $maxMed = max(1, max($porMedio)); @endphp
                <div class="chart-card">
                    <div class="chart-title">Tickets por Medio</div>
                    @foreach(['Sales Force' => '#2563eb', 'Whaticket' => '#f59e0b', 'WhatsApp' => '#16a34a'] as $lbl => $clr)
                        @php $cnt = $porMedio[$lbl] ?? 0; $pct = round(($cnt / $maxMed) * 100); @endphp
                        <div class="bar-row">
                            <span>{{ $lbl }}</span>
                            <div class="bar-track"><div class="bar-fill" style="width:{{ $pct }}%; background:{{ $clr }}"></div></div>
                            <strong style="text-align:right">{{ $cnt }}</strong>
                        </div>
                    @endforeach
                </div>

                {{-- Gráfico Barras: Producto --}}
                @php $maxProd = max(1, max($porProducto ?: [0])); @endphp
                <div class="chart-card">
                    <div class="chart-title">Tickets por Producto</div>
                    @foreach($porProducto as $lbl => $cnt)
                        @php
                            $pct = round(($cnt / $maxProd) * 100);
                            $clr = (\App\Models\Visit::PRODUCTO_ESTILOS[$lbl][1] ?? '#64748b');
                        @endphp
                        <div class="bar-row">
                            <span style="white-space:nowrap; overflow:hidden; text-overflow:ellipsis">{{ $lbl }}</span>
                            <div class="bar-track"><div class="bar-fill" style="width:{{ $pct }}%; background:{{ $clr }}"></div></div>
                            <strong style="text-align:right">{{ $cnt }}</strong>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="chart-title" style="font-size:15px; margin-bottom:10px">Detalle de Tickets ({{ $total }})</div>
            <table>
                <thead>
                    <tr>
                        <th>Título y Descripción</th>
                        <th>Medio</th>
                        <th>Productos</th>
                        <th>Prioridad</th>
                        <th>Status</th>
                        <th>Consultor</th>
                        <th>Creado</th>
                        <th>Resuelto</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($tickets as $t)
                    <tr>
                        <td>
                            <strong>{{ $t->title }}</strong>
                            @if($t->description)
                                <div style="color:var(--muted); margin-top:2px">{{ $t->description }}</div>
                            @endif
                        </td>
                        <td>
                            @if($t->medium === 'salesforce') Sales Force
                            @elseif($t->medium === 'whaticket') Whaticket
                            @else WhatsApp @endif
                        </td>
                        <td>{{ implode(', ', $t->productos ?? []) ?: '—' }}</td>
                        <td>
                            @if($t->priority === 'high')
                                <span class="badge" style="background:#fee2e2;color:#dc2626">Alta</span>
                            @elseif($t->priority === 'medium')
                                <span class="badge" style="background:#fef3c7;color:#b45309">Media</span>
                            @else
                                <span class="badge" style="background:#f3f4f6;color:#4b5563">Baja</span>
                            @endif
                        </td>
                        <td>
                            @if($t->status === 'open')
                                <span class="badge" style="background:#fee2e2;color:#dc2626">Abierto</span>
                            @elseif($t->status === 'in_progress')
                                <span class="badge" style="background:#fef3c7;color:#b45309">En proceso</span>
                            @else
                                <span class="badge" style="background:#dcfce7;color:#16a34a">Cerrado</span>
                            @endif
                        </td>
                        <td>{{ $t->consultant->user->name ?? '—' }}</td>
                        <td>{{ $t->created_at?->format('d/m/Y') ?? '—' }}</td>
                        <td>{{ $t->resolved_at ? \Carbon\Carbon::parse($t->resolved_at)->format('d/m/Y') : '—' }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" style="text-align:center; padding:24px; color:var(--muted)">No hay tickets registrados para este colegio.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
