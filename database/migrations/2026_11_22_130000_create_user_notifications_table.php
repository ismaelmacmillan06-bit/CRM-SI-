<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('school_id')->nullable()->constrained('schools')->nullOnDelete();
            $table->string('category', 40)->index();
            $table->string('event_type', 60)->index();
            $table->string('priority', 20)->default('normal');
            $table->string('icon', 16)->default('🔔');
            $table->string('title');
            $table->text('message');
            $table->json('details')->nullable();
            $table->string('action_url')->nullable();
            $table->string('action_label')->nullable();
            $table->timestamp('read_at')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'read_at', 'created_at'], 'user_notif_user_read_created_idx');
            $table->index(['user_id', 'category'], 'user_notif_user_cat_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
    }
};
