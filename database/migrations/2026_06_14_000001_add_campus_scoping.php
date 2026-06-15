<?php

use App\Enums\RoleEnums;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('campuses', function (Blueprint $table): void {
            $table->string('slug')->nullable()->after('code');
        });

        DB::table('campuses')
            ->orderBy('id')
            ->get(['id', 'name', 'code'])
            ->each(function (object $campus): void {
                $baseSlug = Str::slug($campus->code ?: $campus->name);
                $slug = $baseSlug;
                $sequence = 2;

                while (DB::table('campuses')->where('slug', $slug)->where('id', '!=', $campus->id)->exists()) {
                    $slug = "{$baseSlug}-{$sequence}";
                    $sequence++;
                }

                DB::table('campuses')->where('id', $campus->id)->update(['slug' => $slug]);
            });

        Schema::table('campuses', function (Blueprint $table): void {
            $table->unique('slug');
        });

        Schema::create('campus_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('campus_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->index();
            $table->boolean('active')->default(true)->index();
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            $table->unique(['campus_id', 'user_id']);
        });

        if (! Schema::hasColumn('roles', 'campus_id')) {
            Schema::table('roles', function (Blueprint $table): void {
                $table->unsignedBigInteger('campus_id')->nullable()->after('id');
                $table->index('campus_id', 'roles_campus_foreign_key_index');
                $table->dropUnique('roles_name_guard_name_unique');
                $table->unique(['campus_id', 'name', 'guard_name']);
            });
        }

        if (! Schema::hasColumn('model_has_roles', 'campus_id')) {
            Schema::table('model_has_roles', function (Blueprint $table): void {
                $table->unsignedBigInteger('campus_id')->default(0)->first();
                $table->index('campus_id', 'model_has_roles_campus_foreign_key_index');
                $table->dropPrimary('model_has_roles_role_model_type_primary');
                $table->primary(['campus_id', 'role_id', 'model_id', 'model_type'], 'model_has_roles_role_model_type_primary');
            });
        }

        if (! Schema::hasColumn('model_has_permissions', 'campus_id')) {
            Schema::table('model_has_permissions', function (Blueprint $table): void {
                $table->unsignedBigInteger('campus_id')->default(0)->first();
                $table->index('campus_id', 'model_has_permissions_campus_foreign_key_index');
                $table->dropPrimary('model_has_permissions_permission_model_type_primary');
                $table->primary(['campus_id', 'permission_id', 'model_id', 'model_type'], 'model_has_permissions_permission_model_type_primary');
            });
        }

        Schema::table('applications', function (Blueprint $table): void {
            $table->foreignId('campus_id')->nullable()->after('person_id')->constrained()->cascadeOnDelete();
            $table->index(['campus_id', 'status']);
        });

        DB::table('applications')
            ->orderBy('id')
            ->get(['id', 'admission_period_id'])
            ->each(function (object $application): void {
                DB::table('applications')
                    ->where('id', $application->id)
                    ->update([
                        'campus_id' => DB::table('admission_periods')
                            ->where('id', $application->admission_period_id)
                            ->value('campus_id'),
                    ]);
            });

        Schema::table('enrollments', function (Blueprint $table): void {
            $table->foreignId('campus_id')->nullable()->after('student_id')->constrained()->cascadeOnDelete();
            $table->index(['campus_id', 'status']);
        });

        DB::table('enrollments')
            ->orderBy('id')
            ->get(['id', 'enrollment_period_id'])
            ->each(function (object $enrollment): void {
                DB::table('enrollments')
                    ->where('id', $enrollment->id)
                    ->update([
                        'campus_id' => DB::table('enrollment_periods')
                            ->where('id', $enrollment->enrollment_period_id)
                            ->value('campus_id'),
                    ]);
            });

        DB::table('person_roles')
            ->join('people', 'people.id', '=', 'person_roles.person_id')
            ->whereNotNull('people.user_id')
            ->whereNotNull('person_roles.campus_id')
            ->where('person_roles.active', true)
            ->whereIn('person_roles.role', RoleEnums::values())
            ->select([
                'person_roles.campus_id',
                'people.user_id',
                'person_roles.role',
            ])
            ->get()
            ->each(function (object $assignment): void {
                DB::table('campus_user')->updateOrInsert(
                    [
                        'campus_id' => $assignment->campus_id,
                        'user_id' => $assignment->user_id,
                    ],
                    [
                        'role' => $assignment->role,
                        'active' => true,
                        'is_default' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ],
                );
            });

        $administrativeRoles = [
            RoleEnums::SUPER_ADMIN->value,
            RoleEnums::SCHOOL_ADMIN->value,
            RoleEnums::REGISTRAR->value,
            RoleEnums::ADMISSIONS_OFFICER->value,
            RoleEnums::ACADEMIC_COORDINATOR->value,
        ];

        DB::table('model_has_roles')
            ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->where('model_has_roles.model_type', 'App\\Models\\User')
            ->whereIn('roles.name', $administrativeRoles)
            ->select(['model_has_roles.model_id as user_id', 'roles.name as role'])
            ->get()
            ->each(function (object $assignment): void {
                $campusIds = $assignment->role === RoleEnums::SUPER_ADMIN->value
                    ? DB::table('campuses')->pluck('id')
                    : DB::table('campuses')->orderBy('id')->limit(1)->pluck('id');

                foreach ($campusIds as $campusId) {
                    DB::table('campus_user')->updateOrInsert(
                        ['campus_id' => $campusId, 'user_id' => $assignment->user_id],
                        [
                            'role' => $assignment->role,
                            'active' => true,
                            'is_default' => false,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    );
                }
            });

        DB::table('campus_user')
            ->where('active', true)
            ->get(['campus_id', 'user_id', 'role'])
            ->each(function (object $membership): void {
                $roleId = DB::table('roles')
                    ->whereNull('campus_id')
                    ->where('name', $membership->role)
                    ->where('guard_name', 'web')
                    ->value('id');

                if (! $roleId) {
                    return;
                }

                DB::table('model_has_roles')->insertOrIgnore([
                    'campus_id' => $membership->campus_id,
                    'role_id' => $roleId,
                    'model_type' => 'App\\Models\\User',
                    'model_id' => $membership->user_id,
                ]);
            });
    }

    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('campus_id');
        });

        Schema::table('applications', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('campus_id');
        });

        Schema::dropIfExists('campus_user');

        Schema::table('campuses', function (Blueprint $table): void {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
