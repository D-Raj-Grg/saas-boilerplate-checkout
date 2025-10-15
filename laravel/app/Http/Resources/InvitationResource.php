<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvitationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'token' => $this->token,
            'role' => $this->role,
            'message' => $this->message,
            'expires_at' => $this->expires_at,
            'workspace' => [
                'uuid' => $this->workspace->uuid,
                'name' => $this->workspace->name,
                'organization' => [
                    'uuid' => $this->workspace->organization->uuid,
                    'name' => $this->workspace->organization->name,
                ],
            ],
            'inviter' => [
                'name' => $this->inviter->name,
                'email' => $this->inviter->email,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
