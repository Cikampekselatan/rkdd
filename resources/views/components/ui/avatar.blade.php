@props(['name', 'src' => null, 'user' => null, 'size' => 'md', 'status' => null])

@php
    $src = $src ?: $user?->profilePhotoUrl();
    $initials = collect(preg_split('/\s+/', trim($name)))
        ->filter()
        ->take(2)
        ->map(fn (string $part): string => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<span {{ $attributes->class(['skuad-avatar', "skuad-avatar-{$size}"]) }} aria-label="{{ $name }}">
    @if ($src)
        <img src="{{ $src }}" alt="{{ $name }}">
    @else
        <span aria-hidden="true">{{ $initials }}</span>
    @endif

    @if ($status)
        <span class="skuad-avatar-status skuad-avatar-status-{{ $status }}" aria-label="Status {{ $status }}"></span>
    @endif
</span>
