<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary(); // Laravel uses UUIDs for notifications
            $table->string('type');        // Stores the notification class name
            $table->morphs('notifiable');  // Creates notifiable_id and notifiable_type
            $table->text('data');         // <--- THIS IS THE FIX (Stores the JSON data)
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};