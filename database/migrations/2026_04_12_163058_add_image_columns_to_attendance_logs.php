<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
{
    Schema::table('attendance_logs', function (Blueprint $table) {
        // We add these two columns so the database can store the selfie file paths
        $table->string('image_in')->nullable()->after('status');
        $table->string('image_out')->nullable()->after('image_in');
    });
}

public function down()
{
    Schema::table('attendance_logs', function (Blueprint $table) {
        $table->dropColumn(['image_in', 'image_out']);
    });
}
};
