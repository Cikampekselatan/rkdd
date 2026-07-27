@php($previousStep = $step->previous())

<div class="onboarding-actions">
    @if ($previousStep)
        <x-ui.button :href="route('onboarding.wizard.show', $previousStep->value)" variant="ghost" icon="bi-arrow-left">Kembali</x-ui.button>
    @else
        <x-ui.button :href="route('onboarding.registration-code.accepted')" variant="ghost" icon="bi-arrow-left">Kembali</x-ui.button>
    @endif
    <x-ui.button type="submit" icon="{{ isset($finalStep) ? 'bi-check2-circle' : 'bi-arrow-right' }}" data-wizard-submit>
        {{ isset($finalStep) ? 'Setujui dan aktifkan akun' : 'Simpan dan lanjutkan' }}
    </x-ui.button>
</div>
