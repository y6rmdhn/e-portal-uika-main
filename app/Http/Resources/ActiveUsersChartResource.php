<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActiveUsersChartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'label' => $this->resource['label'],
            'date'  => $this->resource['date'],
            'count' => $this->resource['count'],
        ];
    }
}
