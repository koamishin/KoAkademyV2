<x-filament-panels::page.simple>
    <div class="w-full">
        <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm dark:border-amber-700/50 dark:bg-amber-950/30">
            <div class="flex gap-4">
                <div class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-500 text-white">
                    <x-filament::icon icon="heroicon-o-sparkles" class="size-6" />
                </div>

                <div class="flex flex-col gap-1">
                    <h2 class="text-base font-semibold text-gray-950 dark:text-white">
                        Build the foundation once
                    </h2>
                    <p class="text-sm leading-6 text-gray-600 dark:text-gray-300">
                        Your progress is saved after every step. The admin workspace unlocks when this setup is complete.
                    </p>
                </div>
            </div>
        </div>

        <form wire:submit="complete">
            {{ $this->form }}
        </form>
    </div>
</x-filament-panels::page.simple>
