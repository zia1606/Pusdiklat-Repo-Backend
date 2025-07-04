<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class KoleksiResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);

        return [
            'id' => $this->id,
            'judul' => $this->judul,
            'penulis' => $this->penulis,
            'ringkasan' => $this->ringkasan,
            'kategoriBangKom' => $this->kategoriBangKom->nama ?? null,
            'jenis_dokumen' => $this->jenisDokumen->nama ?? null,
            'tahun_terbit' => $this->tahun_terbit,
            'penerbit' => $this->penerbit,
            'keywords' => $this->keywords,
            'views' => $this->views,
            'is_best_collection' => $this->is_best_collection,
            'preprocessing' => $this->preprocessing,
            'dokumen_pdf' => $this->dokumen_pdf ? asset("storage/{$this->dokumen_pdf}") : null,
            'youtube_link' => $this->youtube_link,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
