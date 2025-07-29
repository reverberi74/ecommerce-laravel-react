<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function stripe(Request $request)
    {
        // TODO: gestire eventi Stripe
    }

    public function paypal(Request $request)
    {
        // TODO: gestire eventi PayPal
    }
}
