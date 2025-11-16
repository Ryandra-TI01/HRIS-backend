<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Base Model untuk semua model HRIS
 * Override serializeDate untuk format konsisten tanpa timezone
 */
abstract class BaseModel extends Model
{
    /**
     * Prepare a date for array / JSON serialization.
     * Override untuk menghilangkan timezone dan format konsisten
     */
    protected function serializeDate(\DateTimeInterface $date): string
    {
        return $date->format('Y-m-d H:i:s');
    }
}