<?php

declare(strict_types=1);

namespace Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Model;

final class ApplicationStatusHistory extends Model
{
    protected $fillable = ['application_id', 'from_status', 'to_status', 'changed_by', 'notes'];
}
