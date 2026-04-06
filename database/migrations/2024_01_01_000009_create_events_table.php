public function up(): void
{
    Schema::create('events', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->date('date');
        $table->time('time')->nullable();
        $table->text('description')->nullable();
        $table->enum('type', ['holiday', 'meeting', 'deadline', 'other'])->default('other');
        $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
        $table->timestamps();
    });
}