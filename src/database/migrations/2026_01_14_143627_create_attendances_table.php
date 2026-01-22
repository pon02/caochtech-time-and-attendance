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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->date('work_date');
            $table->datetime('clock_in_at')->nullable();
            $table->datetime('clock_out_at')->nullable();
            $table->unsignedInteger('break_minutes')->default(0);
            $table->unsignedInteger('work_minutes')->nullable();
            $table->text('note')->nullable();
            $table->enum('status', ['normal', 'pending', 'approved'])->default('normal');
            $table->timestamps();

            $table->unique(['user_id', 'work_date']);
            $table->index(['user_id', 'work_date']);
            $table->index('work_date');
            $table->index(['status', 'work_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendances');
    }
};
