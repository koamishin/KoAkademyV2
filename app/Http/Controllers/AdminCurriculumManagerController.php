<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Filament\Resources\Curricula\CurriculumResource;
use App\Support\CurrentCampus;
use Illuminate\Http\RedirectResponse;
use Modules\Portal\Support\PortalRoleResolver;

final class AdminCurriculumManagerController extends Controller
{
    public function __invoke(CurrentCampus $currentCampus, PortalRoleResolver $portalRoleResolver): RedirectResponse
    {
        $campus = $currentCampus->get();

        abort_unless($campus !== null && $portalRoleResolver->canAccessAdminPortal(request()->user(), $campus), 403);

        return redirect()->to(CurriculumResource::getUrl('index', ['tenant' => $campus]));
    }
}
