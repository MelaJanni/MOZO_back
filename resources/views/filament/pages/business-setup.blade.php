<x-filament-panels::page>
    <div class="space-y-6">
        <div class="text-center">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                ¡Bienvenido a MOZO Admin!
            </h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Para comenzar, necesitamos configurar algunos datos básicos de su negocio.
            </p>
        </div>

        <form wire:submit="save">
            {{ $this->form }}

            <div class="flex justify-end mt-6">
                {{ $this->getFormActions()[0] }}
            </div>
        </form>
    </div>
</x-filament-panels::page>