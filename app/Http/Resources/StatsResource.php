<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StatsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'total_users'     => $this->total_users,
            'active_users'    => $this->active_users,
            'inactive_users'  => $this->inactive_users,
            'new_this_month'  => $this->new_this_month,
            'total_login_today' => $this->total_login_today,
            'active_rate'     => $this->active_rate . '%',
        ];
    }
}
