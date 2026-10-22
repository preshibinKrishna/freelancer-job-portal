@props(['padding' => 'md']) @php($paddingClasses = ['none' => '', 'sm' => 'p-4', 'md' => 'p-5 sm:p-6', 'lg' => 'p-6 sm:p-8'])
<section {{ $attributes->class(['bt-card', $paddingClasses[$padding] ?? $paddingClasses['md']]) }}>{{ $slot }}</section>
