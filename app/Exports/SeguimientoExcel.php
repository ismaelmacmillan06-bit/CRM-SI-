<?php

namespace App\Exports;

use App\Models\SeguimientoAccion;
use App\Models\Visit;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

// Reporte de Seguimiento (Meta, Global Educa…): colegios con su avance de arranque + tickets y sesiones
// marcados con el producto de la plataforma. Las plataformas con agenda (Meta) agregan sus datos de examen.
class SeguimientoExcel
{
    private const COLORES = [
        'meta'          => '7C3AED',
        'global-educa'  => '16A34A',
        'frances'       => 'DB2777',
        'red-roo-reads' => '10B981',
        'equipamiento'  => '475569',
    ];
    private const COLOR_DEFAULT = '4F46E5';

    private string $color;
    private bool $agenda;
    private bool $porNivel;
    private bool $tieneAcciones;

    private const TICKET_STATUS = ['open' => 'Abierto', 'in_progress' => 'En proceso', 'closed' => 'Cerrado'];
    private const TICKET_PRIORIDAD = ['low' => 'Baja', 'medium' => 'Media', 'high' => 'Alta'];
    private const TICKET_MEDIO = ['salesforce' => 'Sales Force', 'whaticket' => 'Whaticket', 'whatsapp' => 'WhatsApp'];
    private const SESION_STATUS = ['pendiente' => 'Pendiente', 'en_curso' => 'En curso', 'terminada' => 'Terminada'];

    private string $generado;

    public function __construct(
        private string $slug,
        private array $info,
        private Collection $filas,
        private Collection $tickets,
        private Collection $sesiones,
    ) {
        $this->generado = now()->format('d/m/Y H:i');
        $this->color = self::COLORES[$slug] ?? self::COLOR_DEFAULT;
        $this->agenda = !empty($info['agenda']);
        $this->porNivel = !empty($info['por_nivel']);
        $this->tieneAcciones = !empty($info['acciones']);
    }

    // Guarda el archivo en un temporal y regresa la ruta
    public function guardar(): string
    {
        $libro = new Spreadsheet();
        $libro->getProperties()->setCreator('SICentral')->setTitle('Seguimiento ' . $this->info['nombre']);

        $this->hojaResumen($libro->getActiveSheet());
        $this->hojaColegios($libro->createSheet());
        if ($this->tieneAcciones) {
            $this->hojaAcciones($libro->createSheet());
        }
        $this->hojaTickets($libro->createSheet());
        $this->hojaSesiones($libro->createSheet());
        $libro->setActiveSheetIndex(0);

        $ruta = tempnam(sys_get_temp_dir(), 'seg') . '.xlsx';
        (new Xlsx($libro))->save($ruta);

        return $ruta;
    }

    private function hojaResumen(Worksheet $ws): void
    {
        $nombre = $this->info['nombre'];
        $total = count($this->info['acciones']);
        $ws->setTitle('Resumen');
        $this->titulo($ws, "Seguimiento {$nombre} — Resumen", 2);

        $datos = [
            ['Colegios con ' . $nombre, $this->filas->count()],
        ];
        if ($this->tieneAcciones) {
            $etiquetaCompletos = match (true) {
                $this->agenda   => 'Arranque completo (' . $total . '/' . $total . ')',
                $this->porNivel => 'Implementación completa (todos los niveles)',
                default         => 'Implementación completa (' . $total . '/' . $total . ')',
            };
            $datos[] = [$etiquetaCompletos, $this->filas->where('completo', true)->count()];
        }
        if ($this->agenda) {
            $datos[] = ['Capacitaciones próximas (30 días)', $this->filas->where('capacitacion_proxima', true)->count()];
            $datos[] = ['Aplicaciones próximas (30 días)', $this->filas->where('aplicacion_proxima', true)->count()];
            $datos[] = ['Con fechas pendientes', $this->filas->where('pendiente', true)->count()];
        }
        $filaTickets = 5 + count($datos);
        $datos = array_merge($datos, [
            ['Tickets ' . $nombre, $this->tickets->count()],
            ['   Abiertos / en proceso', $this->tickets->where('status', '!=', 'closed')->count()],
            ['   Cerrados', $this->tickets->where('status', 'closed')->count()],
            ['Sesiones ' . $nombre, $this->sesiones->count()],
            ['   Realizadas (terminadas)', $this->sesiones->where('status', 'terminada')->count()],
            ['   Pendientes / en curso', $this->sesiones->where('status', '!=', 'terminada')->count()],
        ]);
        $this->encabezados($ws, 4, ['Indicador', 'Total'], false);
        $ws->fromArray($datos, null, 'A5');
        $ws->getStyle('B5:B' . (4 + count($datos)))->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $ws->getStyle('A5:B' . (4 + count($datos)))->getFont()->setSize(11);
        $negritas = $this->tieneAcciones
            ? [5, 6, $filaTickets, $filaTickets + 3]
            : [5, $filaTickets, $filaTickets + 3];
        foreach ($negritas as $fila) {
            $ws->getStyle("A{$fila}:B{$fila}")->getFont()->setBold(true);
        }

        // Avance por acción de arranque
        if ($this->tieneAcciones) {
            $fila = 6 + count($datos);
            if ($this->porNivel) {
                $nivelesPlanos = $this->filas->flatMap(fn ($f) => $f['niveles'] ?? collect());
                $totalNiveles = $nivelesPlanos->count();
                $this->encabezados($ws, $fila, ['Acción de arranque', 'Niveles completados'], false);
                foreach ($this->info['acciones'] as $clave => $etiqueta) {
                    $fila++;
                    $hechas = $nivelesPlanos->filter(fn ($n) => ($n['acciones'][$clave]->status ?? null) === 'done')->count();
                    $ws->setCellValue("A{$fila}", $etiqueta);
                    $ws->setCellValue("B{$fila}", $hechas . ' / ' . $totalNiveles);
                    $ws->getStyle("B{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            } else {
                $this->encabezados($ws, $fila, ['Acción de arranque', 'Colegios completados'], false);
                foreach ($this->info['acciones'] as $clave => $etiqueta) {
                    $fila++;
                    $hechas = $this->filas->filter(fn ($f) => ($f['acciones'][$clave]->status ?? null) === 'done')->count();
                    $ws->setCellValue("A{$fila}", $etiqueta);
                    $ws->setCellValue("B{$fila}", $hechas . ' / ' . $this->filas->count());
                    $ws->getStyle("B{$fila}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                }
            }
        }

        $this->anchos($ws, [42, 22]);
    }

    private function hojaColegios(Worksheet $ws): void
    {
        $ws->setTitle('Colegios');
        $cab = $this->tieneAcciones
            ? ['ID Colegio', 'Colegio', 'Estado', 'Consultor digital', 'Niveles', 'Arranque', 'Siguiente acción']
            : ['ID Colegio', 'Colegio', 'Estado', 'Consultor digital', 'Niveles'];
        if ($this->agenda) {
            array_push($cab, 'Administrador', 'Usuario', 'Contraseña', 'Capacitación', 'Fecha capacitación', 'Horario', 'Fecha aplicación', 'Notas');
        }
        $this->titulo($ws, 'Colegios con ' . $this->info['nombre'] . ' (' . $this->filas->count() . ')', count($cab));
        $this->encabezados($ws, 4, $cab);

        $total = count($this->info['acciones']);
        $fila = 5;
        foreach ($this->filas as $i => $f) {
            $c = $f['colegio'];
            $nivelesTexto = !empty($f['niveles'])
                ? $f['niveles']->pluck('nivel.name')->implode(', ')
                : $c->levels->pluck('name')->implode(', ');
            $valores = [
                $c->nexus_id ?: '—', $c->name, $c->state ?: '—', $f['consultor'] ?: '—',
                $nivelesTexto ?: '—',
            ];
            if ($this->tieneAcciones) {
                $totalColegio = $f['total'] ?? $total;
                $avanceTexto = $totalColegio > 0 ? "{$f['hechas']}/{$totalColegio} ({$f['pct']}%)" : 'Sin niveles';
                $valores[] = $avanceTexto;
                $valores[] = $f['completo'] ? 'Completo' : ($f['siguiente'] ?: '—');
            }
            if ($this->agenda) {
                $m = $f['meta'];
                array_push($valores,
                    $m->administrador ?: '—', $m->usuario ?: '—', $m->contrasena ?: '—', $m->capacitacion ?: '—',
                    $m->fecha_capacitacion?->format('d/m/Y') ?? 'Pendiente', $m->horaCorta() ?: '—',
                    $m->fecha_aplicacion?->format('d/m/Y') ?? 'Pendiente', $m->notas ?: '—');
            }
            $ws->fromArray([$valores], null, "A{$fila}", true);
            $this->renglon($ws, $fila, count($cab), $i);
            $fila++;
        }
        $this->anchos($ws, $this->tieneAcciones
            ? [15, 34, 18, 22, 30, 13, 34, 22, 28, 16, 26, 16, 10, 16, 40]
            : [15, 34, 18, 22, 34]);
    }

    private function hojaAcciones(Worksheet $ws): void
    {
        $ws->setTitle('Acciones de arranque');
        $cab = $this->porNivel
            ? array_merge(['ID Colegio', 'Colegio', 'Nivel'], array_values($this->info['acciones']), ['Avance'])
            : array_merge(['ID Colegio', 'Colegio'], array_values($this->info['acciones']), ['Avance']);
        $this->titulo($ws, 'Acciones de arranque — ' . $this->info['nombre'], count($cab));
        $this->encabezados($ws, 4, $cab);
        $ws->getStyle('A4:' . $this->col(count($cab)) . '4')->getAlignment()->setWrapText(true);
        $ws->getRowDimension(4)->setRowHeight(32);

        $colores = ['done' => 'DCFCE7', 'in_progress' => 'FEF3C7'];
        $fila = 5;

        if ($this->porNivel) {
            $totalPorNivel = count($this->info['acciones']);
            $indiceFila = 0;

            foreach ($this->filas as $f) {
                $niveles = $f['niveles'] ?? collect();
                if ($niveles->isEmpty()) {
                    $valores = array_merge(
                        [$f['colegio']->nexus_id ?: '—', $f['colegio']->name, 'Sin niveles'],
                        array_fill(0, $totalPorNivel, '—'),
                        ['—']
                    );
                    $ws->fromArray([$valores], null, "A{$fila}", true);
                    $this->renglon($ws, $fila, count($cab), $indiceFila++);
                    $fila++;
                    continue;
                }

                foreach ($niveles as $n) {
                    $valores = [$f['colegio']->nexus_id ?: '—', $f['colegio']->name, $n['nivel']->name];
                    foreach (array_keys($this->info['acciones']) as $clave) {
                        $status = $n['acciones'][$clave]->status ?? 'pending';
                        $valores[] = trim(preg_replace('/^\S+\s/u', '', SeguimientoAccion::STATUS[$status]));
                    }
                    $valores[] = "{$n['hechas']}/{$totalPorNivel}";
                    $ws->fromArray([$valores], null, "A{$fila}", true);
                    $this->renglon($ws, $fila, count($cab), $indiceFila++);

                    foreach (array_keys($this->info['acciones']) as $idx => $clave) {
                        $status = $n['acciones'][$clave]->status ?? 'pending';
                        $celda = $this->col(4 + $idx) . $fila;
                        $ws->getStyle($celda)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                        if (isset($colores[$status])) {
                            $ws->getStyle($celda)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($colores[$status]);
                        }
                    }
                    $fila++;
                }
            }

            $this->anchos($ws, array_merge([15, 34, 18], array_fill(0, $totalPorNivel, 20), [10]));
            return;
        }

        foreach ($this->filas as $i => $f) {
            $valores = [$f['colegio']->nexus_id ?: '—', $f['colegio']->name];
            foreach (array_keys($this->info['acciones']) as $clave) {
                $status = $f['acciones'][$clave]->status ?? 'pending';
                $valores[] = trim(preg_replace('/^\S+\s/u', '', SeguimientoAccion::STATUS[$status]));
            }
            $valores[] = "{$f['hechas']}/" . count($this->info['acciones']);
            $ws->fromArray([$valores], null, "A{$fila}", true);
            $this->renglon($ws, $fila, count($cab), $i);

            foreach (array_keys($this->info['acciones']) as $n => $clave) {
                $status = $f['acciones'][$clave]->status ?? 'pending';
                $celda = $this->col(3 + $n) . $fila;
                $ws->getStyle($celda)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                if (isset($colores[$status])) {
                    $ws->getStyle($celda)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($colores[$status]);
                }
            }
            $fila++;
        }
        $this->anchos($ws, array_merge([15, 34], array_fill(0, count($this->info['acciones']), 20), [10]));
    }

    private function hojaTickets(Worksheet $ws): void
    {
        $ws->setTitle('Tickets');
        $cab = ['ID Colegio', 'Colegio', 'Título', 'Descripción', 'Status', 'Prioridad', 'Medio', 'Producto(s)', 'Consultor', 'Creado', 'Resuelto'];
        $this->titulo($ws, 'Tickets con producto ' . $this->info['nombre'] . ' (' . $this->tickets->count() . ')', count($cab));
        $this->encabezados($ws, 4, $cab);

        $fila = 5;
        foreach ($this->tickets as $i => $t) {
            $ws->fromArray([[
                $t->school->nexus_id ?: '—', $t->school->name, $t->title, $t->description ?: '—',
                self::TICKET_STATUS[$t->status] ?? $t->status, self::TICKET_PRIORIDAD[$t->priority] ?? $t->priority,
                self::TICKET_MEDIO[$t->medium] ?? $t->medium, implode(', ', $t->productos ?? []),
                $t->consultant?->user?->name ?? '—', $t->created_at?->format('d/m/Y') ?? '—',
                $t->resolved_at ? \Carbon\Carbon::parse($t->resolved_at)->format('d/m/Y') : '—',
            ]], null, "A{$fila}", true);
            $this->renglon($ws, $fila, count($cab), $i);
            $fila++;
        }
        if ($this->tickets->isEmpty()) {
            $ws->setCellValue('A5', 'Sin tickets con producto ' . $this->info['nombre'] . '.');
        }
        $this->anchos($ws, [15, 30, 24, 44, 12, 11, 13, 22, 22, 12, 12]);
    }

    private function hojaSesiones(Worksheet $ws): void
    {
        $ws->setTitle('Sesiones');
        $cab = ['ID Colegio', 'Colegio', 'Fecha programada', 'Fecha realizada', 'Status', 'Consultor', 'Modalidad', 'Tipo', 'Prioridad', 'Producto(s)', 'Motivo', 'Resumen'];
        $this->titulo($ws, 'Sesiones con producto ' . $this->info['nombre'] . ' (' . $this->sesiones->count() . ')', count($cab));
        $this->encabezados($ws, 4, $cab);

        $sinIcono = fn (array $lista, ?string $clave) => $clave ? trim(preg_replace('/^\S+\s/u', '', $lista[$clave] ?? $clave)) : '—';
        $fecha = fn ($valor) => $valor ? \Carbon\Carbon::parse($valor)->format('d/m/Y') : '—';

        $fila = 5;
        foreach ($this->sesiones as $i => $v) {
            $ws->fromArray([[
                $v->school->nexus_id ?: '—', $v->school->name, $fecha($v->scheduled_date), $fecha($v->visit_date),
                self::SESION_STATUS[$v->status] ?? $v->status, $v->consultant?->user?->name ?? '—',
                $sinIcono(Visit::MODALIDADES, $v->modalidad), $sinIcono(Visit::TIPOS, $v->tipo),
                $sinIcono(Visit::PRIORIDADES, $v->prioridad), implode(', ', $v->productos ?? []),
                $v->motivo ?: '—', $v->summary ?: '—',
            ]], null, "A{$fila}", true);
            $this->renglon($ws, $fila, count($cab), $i);
            $fila++;
        }
        if ($this->sesiones->isEmpty()) {
            $ws->setCellValue('A5', 'Sin sesiones con producto ' . $this->info['nombre'] . '.');
        }
        $this->anchos($ws, [15, 30, 16, 16, 12, 22, 12, 13, 12, 22, 34, 40]);
    }

    // ── Estilos ─────────────────────────────────────────────────────

    private function col(int $n): string
    {
        return Coordinate::stringFromColumnIndex($n);
    }

    private function titulo(Worksheet $ws, string $texto, int $columnas): void
    {
        $ultima = $this->col(max($columnas, 2));
        $ws->setCellValue('A1', $texto);
        $ws->setCellValue('A2', 'Generado el ' . $this->generado);
        $ws->mergeCells("A1:{$ultima}1");
        $ws->mergeCells("A2:{$ultima}2");
        $ws->getStyle('A1')->applyFromArray([
            'font'      => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->color]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $ws->getRowDimension(1)->setRowHeight(26);
        $ws->getStyle('A2')->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '6B7280']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
        ]);
    }

    private function encabezados(Worksheet $ws, int $fila, array $titulos, bool $tabla = true): void
    {
        $ws->fromArray([$titulos], null, "A{$fila}");
        $ws->getStyle("A{$fila}:" . $this->col(count($titulos)) . $fila)->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $this->color]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $ws->getRowDimension($fila)->setRowHeight(20);
        if (!$tabla) {
            return;
        }
        $ws->freezePane('A' . ($fila + 1));
        $ws->setAutoFilter("A{$fila}:" . $this->col(count($titulos)) . $fila);
    }

    private function renglon(Worksheet $ws, int $fila, int $columnas, int $i): void
    {
        $rango = "A{$fila}:" . $this->col($columnas) . $fila;
        $ws->getStyle($rango)->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);
        if ($i % 2) {
            $ws->getStyle($rango)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F5F3FF');
        }
    }

    private function anchos(Worksheet $ws, array $anchos): void
    {
        foreach ($anchos as $i => $ancho) {
            $ws->getColumnDimension($this->col($i + 1))->setWidth($ancho);
        }
    }
}
