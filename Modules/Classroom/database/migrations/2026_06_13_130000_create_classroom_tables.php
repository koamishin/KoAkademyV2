<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_offerings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->restrictOnDelete();
            $table->foreignId('section_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('people')->nullOnDelete();
            $table->string('name');
            $table->string('code');
            $table->unsignedInteger('capacity')->nullable();
            $table->string('status')->default('draft')->index();
            $table->string('online_meeting_url')->nullable();
            $table->timestamps();
            $table->unique(['term_id', 'code']);
        });
        Schema::create('class_meetings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('room_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('day_of_week')->nullable()->index();
            $table->date('meeting_date')->nullable()->index();
            $table->time('starts_at');
            $table->time('ends_at');
            $table->date('recurs_from')->nullable();
            $table->date('recurs_until')->nullable();
            $table->string('online_meeting_url')->nullable();
            $table->boolean('cancelled')->default(false);
            $table->timestamps();
        });
        Schema::create('class_members', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
            $table->string('role')->default('student')->index();
            $table->string('status')->default('active')->index();
            $table->timestamps();
            $table->unique(['class_offering_id', 'person_id']);
        });
        Schema::create('class_posts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('type')->default('announcement')->index();
            $table->string('title')->nullable();
            $table->longText('body');
            $table->dateTime('publish_at')->nullable()->index();
            $table->dateTime('published_at')->nullable()->index();
            $table->boolean('comments_enabled')->default(true);
            $table->timestamps();
        });
        Schema::create('assignments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('class_offering_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->longText('instructions')->nullable();
            $table->decimal('points', 8, 2)->nullable();
            $table->dateTime('due_at')->nullable()->index();
            $table->dateTime('publish_at')->nullable()->index();
            $table->dateTime('published_at')->nullable();
            $table->string('status')->default('draft')->index();
            $table->timestamps();
        });
        Schema::create('submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('people')->cascadeOnDelete();
            $table->longText('body')->nullable();
            $table->string('status')->default('draft')->index();
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('returned_at')->nullable();
            $table->decimal('score', 8, 2)->nullable();
            $table->text('feedback')->nullable();
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['assignment_id', 'student_id']);
        });
        Schema::create('class_comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->morphs('commentable');
            $table->text('body');
            $table->boolean('private')->default(false);
            $table->timestamps();
        });
        Schema::create('class_attachments', function (Blueprint $table): void {
            $table->id();
            $table->morphs('attachable');
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type');
            $table->unsignedBigInteger('size');
            $table->timestamps();
        });
        Schema::table('enrollment_subjects', function (Blueprint $table): void {
            $table->foreign('class_offering_id')->references('id')->on('class_offerings')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_subjects', function (Blueprint $table): void {
            $table->dropForeign(['class_offering_id']);
        });
        Schema::dropIfExists('class_attachments');
        Schema::dropIfExists('class_comments');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('assignments');
        Schema::dropIfExists('class_posts');
        Schema::dropIfExists('class_members');
        Schema::dropIfExists('class_meetings');
        Schema::dropIfExists('class_offerings');
    }
};
