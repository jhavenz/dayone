<?php

declare(strict_types=1);

namespace App\Http\Resources;

use DayOne\DTOs\TokenResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TokenResource extends JsonResource
{
    public function __construct(
        private readonly TokenResult $tokenResult,
        private readonly mixed $user,
    ) {
        parent::__construct($tokenResult);
    }

    public function toArray(Request $request): array
    {
        return [
            'user' => $this->user,
            'token' => $this->tokenResult->token,
        ];
    }
}
