@props(['items' => []])
@php
    $listItems = [];
    foreach ($items as $index => $item) {
        $listItems[] = ['@type' => 'ListItem', 'position' => $index + 1, 'name' => $item['label'], ...(!empty($item['url']) ? ['item' => $item['url']] : [])];
    }
@endphp
<nav {{ $attributes->merge(['aria-label' => 'Breadcrumb']) }}>
    <ol class="flex flex-wrap items-center gap-2 text-sm text-ink-600">
        @foreach($items as $item)
            @if(!$loop->first)<li aria-hidden="true">/</li>@endif
            <li @if($loop->last) aria-current="page" class="font-medium text-ink-950" @endif>
                @if(!$loop->last && !empty($item['url']))<a class="hover:text-brand-700 hover:underline" href="{{ $item['url'] }}">{{ $item['label'] }}</a>@else{{ $item['label'] }}@endif
            </li>
        @endforeach
    </ol>
</nav>
<script type="application/ld+json">{!! json_encode(['@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $listItems], JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR) !!}</script>
