@props([
    'name' => 'circle',
    'class' => 'h-5 w-5',
    'stroke' => 1.9,
])

<svg
    {{ $attributes->class($class) }}
    xmlns="http://www.w3.org/2000/svg"
    viewBox="0 0 24 24"
    fill="none"
    stroke="currentColor"
    stroke-width="{{ $stroke }}"
    stroke-linecap="round"
    stroke-linejoin="round"
    aria-hidden="true"
>
    @switch($name)
        @case('home')
            <path d="M3 10.5L12 3l9 7.5" />
            <path d="M5.5 9.5V20h13V9.5" />
            <path d="M9.5 20v-5h5v5" />
            @break

        @case('plus')
            <path d="M12 5v14" />
            <path d="M5 12h14" />
            @break

        @case('clipboard')
            <rect x="7" y="5" width="10" height="15" rx="2" />
            <path d="M9 5.5V4.5A1.5 1.5 0 0 1 10.5 3h3A1.5 1.5 0 0 1 15 4.5v1" />
            @break

        @case('book')
            <path d="M5 5.5a2.5 2.5 0 0 1 2.5-2.5H19v17H7.5A2.5 2.5 0 0 1 5 17.5Z" />
            <path d="M5 17.5A2.5 2.5 0 0 1 7.5 15H19" />
            @break

        @case('search')
            <circle cx="11" cy="11" r="7" />
            <path d="m20 20-3.8-3.8" />
            @break

        @case('users')
            <path d="M16 21v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1" />
            <circle cx="9" cy="8" r="4" />
            <path d="M22 21v-1a4 4 0 0 0-3-3.87" />
            <path d="M16 3.13a4 4 0 0 1 0 7.75" />
            @break

        @case('settings')
            <circle cx="12" cy="12" r="3" />
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.65 1.65 0 0 0 15 19.4a1.65 1.65 0 0 0-1 .6 1.65 1.65 0 0 0-.33 1V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.6 15a1.65 1.65 0 0 0-.6-1 1.65 1.65 0 0 0-1-.33H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.6c.35-.28.57-.68.6-1.12V3a2 2 0 1 1 4 0v.09c.03.44.25.84.6 1.12a1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9c.28.35.68.57 1.12.6H21a2 2 0 1 1 0 4h-.09c-.44.03-.84.25-1.12.6Z" />
            @break

        @case('power')
            <path d="M12 2v10" />
            <path d="M6.2 6.2a8 8 0 1 0 11.3 0" />
            @break

        @case('bell')
            <path d="M15 17h5l-1.4-1.4a2 2 0 0 1-.6-1.4V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5" />
            <path d="M10 20a2 2 0 0 0 4 0" />
            @break

        @case('calendar')
            <rect x="3" y="4" width="18" height="18" rx="2" />
            <path d="M16 2v4" />
            <path d="M8 2v4" />
            <path d="M3 10h18" />
            @break

        @case('flask')
            <path d="M10 2v5l-5.2 9a3 3 0 0 0 2.6 4.5h9.2a3 3 0 0 0 2.6-4.5L14 7V2" />
            <path d="M8.8 12h6.4" />
            @break

        @case('chart')
            <path d="M3 3v18h18" />
            <path d="m7 14 3-3 3 2 4-5" />
            @break

        @case('clock')
            <circle cx="12" cy="12" r="9" />
            <path d="M12 7v5l3 2" />
            @break

        @case('eye')
            <path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6-10-6-10-6Z" />
            <circle cx="12" cy="12" r="3" />
            @break

        @case('print')
            <path d="M7 8V3h10v5" />
            <rect x="5" y="12" width="14" height="8" rx="2" />
            <path d="M7 16h10" />
            @break

        @case('pencil')
            <path d="M4 20h4l10.5-10.5a2.1 2.1 0 0 0 0-3L17.5 5a2.1 2.1 0 0 0-3 0L4 15.5V20Z" />
            @break

        @case('trash')
            <path d="M3 6h18" />
            <path d="M8 6V4h8v2" />
            <path d="M19 6l-1 14H6L5 6" />
            <path d="M10 11v6" />
            <path d="M14 11v6" />
            @break

        @case('filter')
            <path d="M4 5h16" />
            <path d="M7 12h10" />
            <path d="M10 19h4" />
            @break

        @case('sparkles')
            <path d="m12 3 1.7 3.9L18 8.5l-3.4 2.2L15.3 15 12 12.8 8.7 15l.7-4.3L6 8.5l4.3-1.6Z" />
            @break

        @default
            <circle cx="12" cy="12" r="9" />
    @endswitch
</svg>
