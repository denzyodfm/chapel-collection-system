@props(['name', 'class' => 'h-4 w-4'])

<svg {{ $attributes->merge(['class' => $class]) }} xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
    @switch($name)
        @case('dashboard')
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13h8V3H3v10Zm10 8h8V3h-8v18ZM3 21h8v-6H3v6Z" />
            @break
        @case('calendar')
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3v3m10-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z" />
            @break
        @case('gift')
            <path stroke-linecap="round" stroke-linejoin="round" d="M20 12v8H4v-8m16 0H4m16 0V8H4v4m8-4v12M8.5 8C7 8 6 7 6 5.8S7 4 8.1 4C10 4 12 8 12 8s2-4 3.9-4C17 4 18 4.7 18 5.8S17 8 15.5 8h-7Z" />
            @break
        @case('coins')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6c0 1.7-3.1 3-7 3S-2 7.7-2 6s3.1-3 7-3 7 1.3 7 3Zm0 0v4c0 1.7-3.1 3-7 3S-2 11.7-2 10V6m24 4c0 1.7-3.1 3-7 3-1.1 0-2.2-.1-3.1-.3M22 10V6c0-1.7-3.1-3-7-3-1.1 0-2.2.1-3.1.3M12 14c0 1.7-3.1 3-7 3s-7-1.3-7-3v-4" transform="translate(2 1)" />
            @break
        @case('report')
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3h7l5 5v13H7V3Zm7 0v5h5M10 13h6M10 17h6M10 9h2" />
            @break
        @case('ledger')
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 4h14v16H5V4Zm4 4h6M9 12h6M9 16h6" />
            @break
        @case('users')
            <path stroke-linecap="round" stroke-linejoin="round" d="M16 11a4 4 0 1 0-8 0m8 0a4 4 0 1 1-8 0m8 0c2.8.7 5 2.4 5 5v2H3v-2c0-2.6 2.2-4.3 5-5" />
            @break
        @case('home')
            <path stroke-linecap="round" stroke-linejoin="round" d="m3 11 9-8 9 8v9H5v-9Zm6 9v-6h6v6" />
            @break
        @case('plus')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
            @break
        @case('save')
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 4h12l2 2v14H5V4Zm3 0v6h8V4M8 20v-6h8v6" />
            @break
        @case('filter')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 5h16l-6 7v5l-4 2v-7L4 5Z" />
            @break
        @case('edit')
            <path stroke-linecap="round" stroke-linejoin="round" d="m4 16-.8 4 4-.8L18.5 7.9a2.1 2.1 0 0 0-3-3L4 16Z" />
            @break
        @case('trash')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M9 7V5h6v2m-8 0 1 14h8l1-14M10 11v6m4-6v6" />
            @break
        @default
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
    @endswitch
</svg>
