<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Place;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ChatbotController extends Controller
{
    /**
     * Handle chat request for a specific place.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function chat(Request $request, $id)
    {
        $request->validate([
            'message' => 'required|string',
        ]);

        $place = Place::with(['category', 'reviews.user'])->findOrFail($id);
        $userMessage = $request->input('message');
        $apiKey = env('GEMINI_API_KEY');

        if (!$apiKey) {
            return response()->json(['error' => 'API Key not configured'], 500);
        }

        // Construct context from place data
        $context = "Eres un guía turístico experto en el lugar '{$place->name}'. \n";
        $context .= "Ubicación: {$place->address} (Coordenadas: {$place->latitude}, {$place->longitude}). \n";
        $context .= "Descripción: {$place->description}. \n";
        $context .= "Dificultad: {$place->difficulty}. Duración: {$place->duration}. Mejor temporada: {$place->best_season}. \n";
        $context .= "Categoría: {$place->category->name}. \n";
        
        // Add summary of reviews if available
        if ($place->reviews->count() > 0) {
            $context .= "Reseñas destacadas: \n";
            foreach ($place->reviews->take(5) as $review) {
                $context .= "- {$review->user->name} dijo: {$review->comment} (Calificación: {$review->rating}/5). \n";
            }
        }

        $context .= "\nResponde a la siguiente pregunta del usuario de manera amigable, completa y profesional. \n";
        $context .= "Instrucciones: \n";
        $context .= "1. TU ÚNICO OBJETIVO es responder preguntas sobre el lugar '{$place->name}'. NO respondas preguntas sobre otros temas, lugares lejanos o asuntos generales que no tengan relación con visitar este sitio. \n";
        $context .= "2. Usa la información proporcionada arriba como tu fuente principal. \n";
        $context .= "3. Si la respuesta no está explícita, usa tu CONOCIMIENTO GENERAL sobre '{$place->name}' y su zona inmediata. \n";
        $context .= "4. Si te preguntan por restaurantes, tiendas, etc., infiere basándote en la ubicación, pero sé honesto si es un lugar aislado. \n";
        $context .= "5. SI NO SABES LA RESPUESTA o si la información no es suficiente para dar una respuesta útil, DEBES incluir al final de tu respuesta la siguiente frase exacta: \"Si necesitas información más específica, contacta a un guía local.\" \n";
        $context .= "Responde siempre en español. \n";
        $context .= "Pregunta: {$userMessage}";

        try {
            // Call Gemini API (Google Generative AI)
            // Using gemini-flash-latest (likely Gemini 1.5 Flash) to avoid rate limits and errors
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->withOptions([
                'verify' => false, // Disable SSL verification for Windows environments
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key={$apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $context]
                        ]
                    ]
                ]
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $botReply = $data['candidates'][0]['content']['parts'][0]['text'] ?? 'Lo siento, no pude generar una respuesta en este momento.';
                return response()->json(['response' => $botReply]);
            } else {
                Log::error('Gemini API Error: ' . $response->body());
                return response()->json(['error' => 'Error communicating with AI service'], 502);
            }
        } catch (\Exception $e) {
            Log::error('Chatbot Exception: ' . $e->getMessage());
            return response()->json(['error' => 'Internal server error'], 500);
        }
    }
}
