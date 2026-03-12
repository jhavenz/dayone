<?php

declare(strict_types=1);

namespace DayOne\Contracts\Billing\V1;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

interface WebhookHandler
{
    public function handleWebhook(Request $request): Response;

    public function verifySignature(Request $request): bool;
}
