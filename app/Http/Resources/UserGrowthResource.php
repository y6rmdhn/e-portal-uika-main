<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserGrowthResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'label'    => $this->resource['label'],
            'active'   => $this->resource['active'],
            'inactive' => $this->resource['inactive'],
        ];
    }
}
