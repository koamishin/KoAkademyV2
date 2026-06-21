<?php

declare(strict_types=1);

namespace Modules\Enrollment\Models;

use App\Models\Campus;
use App\Models\Person;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class StudentDocument extends Model
{
    public const TYPES = [
        'student_photo' => 'Student photo',
        'psa_birth_certificate' => 'PSA birth certificate',
        'form_137' => 'Form 137',
        'form_138' => 'Form 138 / Report card',
        'good_moral' => 'Good moral certificate',
        'transfer_credential' => 'Transfer credential / Honorable dismissal',
        'transcript_of_records' => 'Transcript of records / Grades',
        'certificate_of_enrollment' => 'Certificate of enrollment',
        'income_proof' => 'Income proof',
        'residency_certificate' => 'Residency / Barangay certificate',
        'custom' => 'Custom document',
    ];

    public const STATUSES = ['pending', 'verified', 'rejected', 'expired'];

    protected $fillable = [
        'campus_id',
        'student_id',
        'document_type',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
        'status',
        'issued_on',
        'expires_on',
        'reviewed_by',
        'reviewed_at',
        'notes',
        'metadata',
    ];

    protected $attributes = [
        'disk' => 'local',
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'issued_on' => 'date',
            'expires_on' => 'date',
            'reviewed_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'student_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
