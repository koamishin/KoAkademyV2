<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Requests;

use App\Models\Campus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\UserManagement\Support\PortalUserManagementAuthorizer;

final class UpdateUserRequest extends FormRequest
{
    public function authorize(PortalUserManagementAuthorizer $authorizer): bool
    {
        $campus = $this->route('campus');
        $target = $this->route('user');

        return $campus instanceof Campus
            && $target instanceof User
            && $this->user()
            && $authorizer->canManage($this->user(), $campus, $target);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(PortalUserManagementAuthorizer $authorizer): array
    {
        $campus = $this->route('campus');
        $target = $this->route('user');
        $allowedCampusIds = $campus instanceof Campus && $this->user()
            ? $authorizer->manageableCampusIds($this->user(), $campus)
            : [];
        $allowedRoles = $campus instanceof Campus && $this->user()
            ? $authorizer->manageableRoleValues($this->user(), $campus)
            : [];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($target?->getKey())],
            'email_verified' => ['boolean'],
            'memberships' => ['required', 'array', 'min:1'],
            'memberships.*.campus_id' => ['required', 'integer', Rule::in($allowedCampusIds)],
            'memberships.*.role' => ['required', 'string', Rule::in($allowedRoles)],
            'memberships.*.active' => ['boolean'],
            'memberships.*.is_default' => ['boolean'],
        ];
    }
}
