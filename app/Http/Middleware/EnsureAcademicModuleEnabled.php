<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Academic\AcademicModuleRegistry;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class EnsureAcademicModuleEnabled
{
    public function __construct(private AcademicModuleRegistry $academicModuleRegistry) {}

    public function handle(Request $request, Closure $next, string $module): Response
    {
        abort_unless($this->academicModuleRegistry->enabled($module), Response::HTTP_NOT_FOUND);

        return $next($request);
    }
}
