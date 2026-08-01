<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'token_type' => 'Bearer',
            'token' => $this['token'],
            'expires_at' => $this['expires_at']?->toISOString(),
            'user' => new UserResource($this['user']),
        ];
    }
}
