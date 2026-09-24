<?php

namespace App\Casts;

use App\Support\RichText;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * HTML dari peserta dibersihkan (RichText::clean) setiap kali disimpan lewat model.
 * Catatan: Query Builder ->update([...]) melewati cast — jangan tulis kolom ini lewat sana.
 */
class SanitizedHtml implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return $value;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        return is_string($value) ? RichText::clean($value) : $value;
    }
}
