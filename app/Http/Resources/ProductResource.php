<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $isDetail = $request->route()?->getName() === 'products.show' || $request->is('api/products/*');

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->when($isDetail, $this->description),
            'price' => (float) $this->price,
            'stock' => $this->stock,
            'availability_status' => $this->availability_status,
            'image' => $this->image_url,
            'images' => $this->when($isDetail, [$this->image_url]),
            'category' => $this->category?->name,
        ];
    }
}
