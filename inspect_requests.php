<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use App\Models\PartnerRequest;

$users = User::all();

foreach ($users as $user) {
    echo "User: " . $user->name . " (ID: " . $user->id . ", Role: " . $user->role . ")\n";
    $requests = PartnerRequest::where('user_id', $user->id)->get();
    
    if ($requests->count() > 0) {
        foreach ($requests as $req) {
            echo "  - Request ID: " . $req->id . "\n";
            echo "    Status: " . $req->status . "\n";
            echo "    User Read: " . ($req->user_read ? 'true' : 'false') . " (Raw: " . var_export($req->user_read, true) . ")\n";
            echo "    Updated At: " . $req->updated_at . "\n";
        }
    } else {
        echo "  No requests.\n";
    }
    echo "--------------------------------------------------\n";
}
