<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Requests;

use App\Models\Campus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Modules\UserManagement\Support\PortalUserManagementAuthorizer;

final class StoreUserRequest extends FormRequest
{
    public function authorize(PortalUserManagementAuthorizer $authorizer): bool
    {
        $campus = $this->route('campus');

        return $campus instanceof Campus
            && $this->user()
            && $authorizer->canMutateAny($this->user(), $campus);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(PortalUserManagementAuthorizer $authorizer): array
    {
        $campus = $this->route('campus');
        $allowedCampusIds = $campus instanceof Campus && $this->user()
            ? $authorizer->manageableCampusIds($this->user(), $campus)
            : [];
        $allowedRoles = $campus instanceof Campus && $this->user()
            ? $authorizer->manageableRoleValues($this->user(), $campus)
            : [];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'email_verified' => ['boolean'],
            'memberships' => ['required', 'array', 'min:1'],
            'memberships.*.campus_id' => ['required', 'integer', Rule::in($allowedCampusIds)],
            'memberships.*.role' => ['required', 'string', Rule::in($allowedRoles)],
            'memberships.*.active' => ['boolean'],
            'memberships.*.is_default' => ['boolean'],
        ];
    }
}
