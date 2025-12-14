<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function dashboard(Request $request)
    {
        return response()->json([
            'message' => 'Dashboard de usuario',
            'user' => $request->user(),
        ]);
    }
}
