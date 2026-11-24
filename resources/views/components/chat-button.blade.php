@props([
    'type',
    'id',
    'label',
    'unread' => 0,
    'size' => 'sm',
    'variant' => 'default',
])

@php
    $sizeClasses = match ($size) {
        'xs' => 'py-1 px-2 text-[11px] rounded-lg gap-1',
        'md' => 'py-2 px-3.5 text-xs rounded-xl gap-1.5',
        default => 'py-1.5 px-3 text-xs rounded-xl gap-1.5',
    };

    $variantClasses = match ($variant) {
        'indigo' => 'text-indigo-700 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200/80',
        'amber' => 'text-amber-700 bg-amber-50 hover:bg-amber-100 border border-amber-200/80',
        default => 'text-sky-700 bg-sky-50 hover:bg-sky-100 border border-sky-200/80',
    };
@endphp

<button type="button"
        data-open-chat
        data-chat-type="{{ $type }}"
        data-chat-id="{{ $id }}"
        data-chat-label="{{ $label }}"
        {{ $attributes->merge(['class' => "inline-flex items-center justify-center font-bold transition-all shadow-2xs cursor-pointer {$sizeClasses} {$variantClasses}"]) }}>
    <i class="bi bi-chat-dots-fill text-[11px]"></i>
    <span>{{ $slot->isNotEmpty() ? $slot : 'Chat' }}</span>
    <span data-chat-badge="{{ $type }}-{{ $id }}"
          class="{{ $unread > 0 ? 'inline-flex' : 'hidden' }} items-center justify-center min-w-[16px] h-4 px-1 rounded-full text-[9px] font-black bg-rose-500 text-white">
        {{ $unread > 99 ? '99+' : $unread }}
    </span>
</button>
