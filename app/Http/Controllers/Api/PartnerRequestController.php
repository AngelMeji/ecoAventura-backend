<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PartnerRequest;
use App\Models\User;
use App\Models\Place;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PartnerRequestController extends Controller
{
    public function index()
    {
        // Admin only (middleware handled in route)
        $requests = PartnerRequest::with('user')->orderBy('created_at', 'desc')->get();
        return response()->json($requests);
    }

    public function store(Request $request)
    {
        $request->validate([
            'place_name' => 'required|string|max:255',
            'place_address' => 'required|string|max:255',
        ]);

        $userId = $request->user()->id;

        // Check if user already has a pending request
        $existingPending = PartnerRequest::where('user_id', $userId)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            return response()->json([
                'message' => 'Ya tienes una solicitud pendiente. Por favor espera a que sea procesada.'
            ], 409); // 409 Conflict
        }

        // Check rejection count and blocking period
        $rejectedRequests = PartnerRequest::where('user_id', $userId)
            ->where('status', 'rejected')
            ->orderBy('updated_at', 'desc')
            ->get();

        if ($rejectedRequests->count() >= 3) {
            // Get the most recent rejection date
            $lastRejection = $rejectedRequests->first();
            $daysSinceRejection = now()->diffInDays($lastRejection->updated_at);
            
            // Block for 30 days (1 month)
            if ($daysSinceRejection < 30) {
                $daysRemaining = 30 - $daysSinceRejection;
                $unblockDate = $lastRejection->updated_at->addDays(30)->format('d/m/Y');
                
                return response()->json([
                    'message' => "Has alcanzado el límite de 3 solicitudes rechazadas. Podrás enviar una nueva solicitud después del {$unblockDate} ({$daysRemaining} días restantes)."
                ], 429); // 429 Too Many Requests
            }
        }

        $partnerRequest = PartnerRequest::create([
            'user_id' => $userId,
            'place_name' => $request->place_name,
            'place_address' => $request->place_address,
            'status' => 'pending',
        ]);

        return response()->json($partnerRequest, 201);
    }

    public function approve($id)
    {
        $partnerRequest = PartnerRequest::findOrFail($id);

        if ($partnerRequest->status !== 'pending') {
            return response()->json(['message' => 'Esta solicitud ya ha sido procesada.'], 400);
        }

        DB::transaction(function () use ($partnerRequest) {
            // 1. Update User Role
            $user = User::findOrFail($partnerRequest->user_id);
            if ($user->role === 'user') {
                $user->role = 'partner';
                $user->save();
            }

            // 2. Create Draft Place
            Place::create([
                'user_id' => $user->id,
                'category_id' => 1, // Default category, assuming 1 exists or handled later
                'name' => $partnerRequest->place_name,
                'description' => 'Descripción pendiente...',
                'short_description' => 'Descripción corta pendiente...',
                'address' => $partnerRequest->place_address,
                'latitude' => 0, // Default coordinates
                'longitude' => 0,
                'status' => 'pending', // Draft/Pending
                'slug' => \Illuminate\Support\Str::slug($partnerRequest->place_name) . '-' . uniqid(),
            ]);

            // 3. Update Request Status
            $partnerRequest->status = 'approved';
            $partnerRequest->save();
        });

        return response()->json(['message' => 'Solicitud aprobada y usuario promovido a socio.']);
    }

    public function reject($id)
    {
        $partnerRequest = PartnerRequest::findOrFail($id);
        
        if ($partnerRequest->status !== 'pending') {
            return response()->json(['message' => 'Esta solicitud ya ha sido procesada.'], 400);
        }

        $partnerRequest->status = 'rejected';
        $partnerRequest->save();

        return response()->json(['message' => 'Solicitud rechazada.']);
    }

    public function getNotifications(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'admin') {
            // Admin sees count of pending requests
            $count = PartnerRequest::where('status', 'pending')->count();
            return response()->json(['type' => 'admin', 'count' => $count]);
        } else {
            // Users see their requests that have been processed but not read
            $notifications = PartnerRequest::where('user_id', $user->id)
                ->where('status', '!=', 'pending')
                ->where('user_read', false)
                ->orderBy('updated_at', 'desc')
                ->get();

            return response()->json(['type' => 'user', 'notifications' => $notifications]);
        }
    }

    public function markAsRead($id)
    {
        $request = PartnerRequest::findOrFail($id);

        if ($request->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $request->user_read = true;
        $request->save();

        return response()->json(['message' => 'Marked as read']);
    }
}
