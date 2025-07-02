<?php

namespace App\Http\Resources\Templates;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class WithDataResource extends JsonResource
{
    public $status;
    public $case;
    public $title;
    public $description;
    public $data;

    public function __construct($status, $case = null, $title, $description, $data = null)
    {
        parent::__construct($data);
        $this->status = $status;
        $this->case = $case;
        $this->title = $title;
        $this->description = $description;
        $this->data = $data;
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
            'data' => $this->data
        ]);
    }
}
