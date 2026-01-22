<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('attendance_audits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->onDelete('cascade');
            $table->foreignId('actor_user_id')->constrained('users')->onDelete('cascade');
            $table->enum('action', ['admin_update', 'apply_change_request', 'revert_change_request']);
            $table->json('before_change');
            $table->json('after_change');
            $table->timestamps();

            $table->index(['attendance_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_audits');
    }
};
