<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Producto(s) que atendió el ticket (mismo catálogo que Sesiones: Visit::PRODUCTOS).
// Nullable: los tickets anteriores quedan sin producto.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->json('productos')->nullable()->after('medium');
        });
    }

    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropColumn('productos');
        });
    }
};
