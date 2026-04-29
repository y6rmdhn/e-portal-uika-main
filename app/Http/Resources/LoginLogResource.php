<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LoginLogResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'             => $this->id,
            'user'           => $this->whenLoaded('user', fn() => [
                'public_id' => $this->user->public_id,
                'name'      => $this->user->name,
                'email'     => $this->user->email,
            ]),
            'ip_address'     => $this->ip_address,
            'browser'        => $this->browser,
            'browser_version' => $this->browser_version,
            'platform'       => $this->platform,
            'device_type'    => $this->device_type,
            'status'         => $this->status,
            'failure_reason' => $this->failure_reason,
            'created_at'     => $this->created_at?->toISOString(),
            'created_at_human' => $this->created_at?->diffForHumans(),
        ];
    }
}
