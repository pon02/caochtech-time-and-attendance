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
        Schema::create('attendance_change_request_payloads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('attendance_change_request_id')->unique();
            $table->foreign('attendance_change_request_id', 'acr_payloads_acr_id_fk')
                ->references('id')
                ->on('attendance_change_requests')
                ->onDelete('cascade');
            $table->json('before_attendance')->comment('入力形式: {clock_in_at, clock_out_at, note}');
            $table->json('after_attendance');
            $table->json('before_breaks')->comment('入力形式: [{id, start_at, end_at}, ...]');
            $table->json('after_breaks')->comment('入力形式: [{id|null, start_at, end_at}, ...]');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('attendance_change_request_payloads');
    }
};
