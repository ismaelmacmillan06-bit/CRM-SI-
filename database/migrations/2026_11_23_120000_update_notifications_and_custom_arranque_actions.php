<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'notification_settings')) {
            Schema::table('users', function (Blueprint $table) {
                $table->json('notification_settings')->nullable()->after('password');
            });
        }

        if (!Schema::hasTable('seguimiento_definicion_acciones')) {
            Schema::create('seguimiento_definicion_acciones', function (Blueprint $table) {
                $table->id();
                $table->string('plataforma', 30)->index();
                $table->string('clave', 60);
                $table->string('etiqueta');
                $table->unsignedInteger('orden')->default(1);
                $table->timestamps();

                $table->unique(['plataforma', 'clave'], 'seg_def_acc_plat_clave_unique');
            });
        }

        if (DB::table('seguimiento_definicion_acciones')->count() === 0) {
            $now = now();
            $seed = [
                'meta' => [
                    'compartir-edgar'    => 'Compartir información a Edgar',
                    'capacitacion'       => 'Capacitación',
                    'fechas-aplicacion'  => 'Fechas de aplicación',
                    'info-administrador' => 'Información del administrador',
                    'carga-alumnos'      => 'Carga de alumnos en META',
                ],
                'global-educa' => [
                    'kickoff'                   => 'Kickoff (contacto con el colegio)',
                    'entrega-informacion'       => 'Entrega de información por parte del colegio',
                    'configuracion'             => 'Configuración de Global Educa (Arranque)',
                    'pruebas'                   => 'Pruebas de configuración',
                    'presentacion-capacitacion' => 'Presentación a colegio y capacitación',
                    'liberacion'                => 'Liberación',
                    'seguimiento'               => 'Seguimiento',
                ],
                'frances' => [
                    'alta-docentes'     => 'Alta de docentes en Espace Virtuel',
                    'alta-alumnos'      => 'Alta de alumnos en Espace Virtuel',
                    'creacion-clases'   => 'Creación de clases en Espace Virtuel',
                    'asignar-docentes'  => 'Asignar docentes a clases en Espace Virtuel',
                    'asignar-alumnos'   => 'Asignar alumnos a clases en Espace Virtuel',
                    'correo-activacion' => 'Envío de correo para activación de materiales',
                    'entregar-colegio'  => 'Entregar colegio de Francés (entregar cuentas)',
                ],
            ];

            $rows = [];
            foreach ($seed as $plataforma => $acciones) {
                $orden = 1;
                foreach ($acciones as $clave => $etiqueta) {
                    $rows[] = [
                        'plataforma' => $plataforma,
                        'clave'      => $clave,
                        'etiqueta'   => $etiqueta,
                        'orden'      => $orden++,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ];
                }
            }
            DB::table('seguimiento_definicion_acciones')->insert($rows);
        }

        if (Schema::hasTable('user_notifications')) {
            DB::table('user_notifications')
                ->where('event_type', 'reporte_semanal_updated')
                ->update(['category' => 'reporte_semanal']);

            DB::table('user_notifications')
                ->whereIn('event_type', ['ssa_created', 'ssa_updated', 'capacitacion_proxima'])
                ->update(['category' => 'recordatorios']);

            DB::table('user_notifications')
                ->whereNotIn('event_type', [
                    'tarea_created',
                    'tarea_updated',
                    'comunicado_created',
                    'comunicado_updated',
                    'manual_uploaded',
                    'manual_updated',
                    'herramienta_uploaded',
                    'herramienta_updated',
                    'reporte_semanal_updated',
                    'reporte_semanal_viernes_1',
                    'reporte_semanal_viernes_2',
                    'ssa_created',
                    'ssa_updated',
                    'capacitacion_proxima',
                ])
                ->delete();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('seguimiento_definicion_acciones');

        if (Schema::hasColumn('users', 'notification_settings')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('notification_settings');
            });
        }
    }
};
