<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\NewsletterService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NewsletterController extends Controller
{
    protected NewsletterService $newsletterService;

    public function __construct(NewsletterService $newsletterService)
    {
        $this->newsletterService = $newsletterService;
    }

    /**
     * Preview newsletter for logged in user
     */
    public function preview(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $content = $this->newsletterService->generateNewsletterContent($user);

        return response()->json($content);
    }

    /**
     * Update newsletter consent
     */
    public function updateConsent(Request $request): JsonResponse
    {
        $request->validate([
            'consent' => 'required|boolean',
        ]);

        $user = $request->user();
        $user->newsletter_consent = $request->consent;
        $user->save();

        return response()->json([
            'message' => 'Newsletter consent updated',
            'consent' => $user->newsletter_consent,
        ]);
    }

    /**
     * Get newsletter consent status
     */
    public function getConsent(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'consent' => $user->newsletter_consent,
        ]);
    }
}

