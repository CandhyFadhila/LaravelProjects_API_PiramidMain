<?php

namespace App\Http\Resources\Templates;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithoutDataResource extends JsonResource
{
    public $status;
    public $case;
    public $title;
    public $description;

    public function __construct($status, $case = null, $title, $description)
    {
        parent::__construct(null);
        $this->status = $status;
        $this->case = $case;
        $this->title = $title;
        $this->description = $description;
    }

    public function toArray(Request $request): array
    {
        return array_filter([
            'status' => $this->status,
            'case' => $this->case,
            'message' => [
                'title' => $this->title,
                'description' => $this->description,
            ],
        ]);
    }
}
