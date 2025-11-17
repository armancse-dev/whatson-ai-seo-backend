<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ReportResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'report_type' => $this->report_type,
            'data' => $this->data,
            'created_at' => $this->created_at,
        ];
    }
}
