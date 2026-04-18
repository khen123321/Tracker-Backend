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
    Schema::create('requirement_settings', function (Blueprint $row) {
        $row->id();
        $row->foreignId('school_id')->constrained()->onDelete('cascade');
        $row->string('course_name'); // e.g., "BS Information Technology"
        $row->integer('required_hours'); // e.g., 486
        $row->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requirement_settings');
    }
};
