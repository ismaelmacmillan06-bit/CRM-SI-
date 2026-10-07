<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comunicado_audiencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comunicado_id')->constrained('comunicados')->onDelete('cascade');
            $table->string('rol', 40);
            $table->timestamps();
            $table->unique(['comunicado_id', 'rol']);
        });

        // Los comunicados que ya existían siguen visibles para todos
        $ahora = now();
        DB::table('comunicados')->pluck('id')->chunk(500)->each(function ($ids) use ($ahora) {
            DB::table('comunicado_audiencias')->insert(
                $ids->map(fn ($id) => [
                    'comunicado_id' => $id,
                    'rol'           => 'todos',
                    'created_at'    => $ahora,
                    'updated_at'    => $ahora,
                ])->all()
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comunicado_audiencias');
    }
};
