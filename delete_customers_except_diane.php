<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Illuminate\Support\Facades\DB;
DB::table('customers')->where('name', '!=', 'Diane')->delete();
echo "Customers (except Diane) deleted.\n";
?>
