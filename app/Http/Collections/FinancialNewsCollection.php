<?php

namespace App\Http\Collections;
use Illuminate\Http\Request;
use App\Http\Resources\FinancialNewsResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

class FinancialNewsCollection extends ResourceCollection
{
    /**
     * The resource that this collection collects.
     *
     * @var string
     */
    public $collects = FinancialNewsResource::class;

    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection, // Now each item is a FinancialNewsResource
            'meta' => [
                'total' => $this->resource->total(),
                'count' => $this->resource->count(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'total_pages' => $this->resource->lastPage(),
            ],
            'links' => [
                'first' => $this->resource->url(1),
                'last' => $this->resource->url($this->resource->lastPage()),
                'prev' => $this->resource->previousPageUrl(),
                'next' => $this->resource->nextPageUrl(),
            ],
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'success' => true,
            'message' => 'Actualités financières récupérées avec succès',
        ];
    }
}
