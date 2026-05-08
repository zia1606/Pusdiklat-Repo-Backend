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
        return [
            'id' => $this->id,
            'no_induk' => $this->no_induk,
            'judul' => $this->judul,
            'penulis' => $this->penulis, // keep legacy field if still used
            'penulis_list' => $this->penulisList->map(function($p) {
                return [
                    'id' => $p->id,
                    'nama' => $p->nama
                ];
            }),
            'ringkasan' => $this->ringkasan,
            'kategoris' => $this->kategoris->map(function($k) {
                return [
                    'id' => $k->id,
                    'nama' => $k->nama
                ];
            }),
            'sub_kategoris' => $this->subKategoris->map(function($sk) {
                return [
                    'id' => $sk->id,
                    'nama' => $sk->nama,
                    'kategori_id' => $sk->kategori_id
                ];
            }),
            'tahun_terbit' => $this->tahun_terbit,
            'penerbit' => $this->penerbit,
            'edisi_cetak' => $this->edisi_cetak,
            'tempat_terbit' => $this->tempat_terbit,
            'isbn' => $this->isbn,
            'isbn_digital' => $this->isbn_digital,
            'issn' => $this->issn,
            'jml_hal_romawi' => $this->jml_hal_romawi,
            'jml_hal_angka' => $this->jml_hal_angka,
            'ddc' => $this->ddc,
            'cutter' => $this->cutter,
            'call_number' => $this->call_number,
            'Klasifikasi' => $this->Klasifikasi,
            'kode' => $this->kode,
            'keywords' => $this->keywords,
            'views' => $this->views,
            'is_best_collection' => $this->is_best_collection,
            'preprocessing' => $this->preprocessing,
            'dokumen_pdf' => $this->dokumen_pdf ? url("/api/koleksi/{$this->id}/pdf") : null,
            'thumbnail' => $this->thumbnail ? (\Illuminate\Support\Str::startsWith($this->thumbnail, ['http://', 'https://']) ? $this->thumbnail : asset("storage/{$this->thumbnail}")) : null,
            'youtube_link' => $this->youtube_link,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
