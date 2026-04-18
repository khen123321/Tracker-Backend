<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('interns', function (Blueprint $table) {
            // 👇 This tells MySQL to add the 3 missing columns 👇
            $table->string('emergency_name')->nullable();
            $table->string('emergency_number')->nullable();
            $table->text('emergency_address')->nullable();
            
            // Also adding 'school' since the error showed it might be missing too!
            if (!Schema::hasColumn('interns', 'school')) {
                $table->string('school')->nullable();
            }
        });
    }

    public function down()
    {
        Schema::table('interns', function (Blueprint $table) {
            // This tells MySQL how to undo it if needed
            $table->dropColumn(['emergency_name', 'emergency_number', 'emergency_address']);
        });
    }
};