<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// Boot the core system
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "<h2 style='font-family: sans-serif'>System Override Initialized</h2>";

try {
    echo "1. Clearing Laravel Caches...<br>";
    \Illuminate\Support\Facades\Artisan::call('optimize:clear');
    echo "✅ Caches destroyed!<br><br>";

    echo "2. Building Database...<br>";
    \Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
    echo "✅ Database built perfectly!<br><br>";

    echo "<h3 style='color: green'>ALL DONE! You can delete this db.php file now!</h3>";

} catch (\Throwable $e) {
    echo "<h3 style='color: red'>❌ CRASH REPORT:</h3>";
    echo "<b>Error:</b> " . $e->getMessage() . "<br>";
    echo "<b>File:</b> " . $e->getFile() . " on line " . $e->getLine();
}
