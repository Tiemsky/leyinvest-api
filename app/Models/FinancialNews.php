<?php

namespace App\Models;

use App\Models\Concerns\HasKey;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $company
 * @property string $title
 * @property string $pdf_url
 * @property string $source
 * @property \Carbon\Carbon|null $published_at
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class FinancialNews extends Model
{
    use HasKey;
    protected $fillable = ['company', 'title', 'pdf_url', 'source', 'published_at'];
    protected $dates = ['published_at'];
}
