<?php

declare(strict_types=1);

namespace Modules\Classroom\Models;

use App\Models\Campus;
use App\Models\Person;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Term;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class ClassOffering extends Model
{
    protected $fillable = ['campus_id', 'term_id', 'subject_id', 'section_id', 'teacher_id', 'name', 'code', 'capacity', 'status', 'online_meeting_url'];

    protected $attributes = ['status' => 'draft'];

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Person::class, 'teacher_id');
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function meetings(): HasMany
    {
        return $this->hasMany(ClassMeeting::class);
    }

    public function members(): HasMany
    {
        return $this->hasMany(ClassMember::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(ClassPost::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class);
    }
}
