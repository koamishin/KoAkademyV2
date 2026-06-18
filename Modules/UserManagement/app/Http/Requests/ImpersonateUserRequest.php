<?php

declare(strict_types=1);

namespace Modules\UserManagement\Http\Requests;

use App\Models\Campus;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Modules\UserManagement\Support\PortalUserManagementAuthorizer;

final class ImpersonateUserRequest extends FormRequest
{
    public function authorize(PortalUserManagementAuthorizer $authorizer): bool
    {
        $campus = $this->route('campus');
        $target = $this->route('user');

        return $campus instanceof Campus
            && $target instanceof User
            && $this->user()
            && $authorizer->canImpersonate($this->user(), $campus, $target);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
