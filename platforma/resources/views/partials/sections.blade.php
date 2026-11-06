@php $toneIdx = 0; @endphp
@foreach ($sections as $s)
    @continue($s['type'] === 'scene' || ! view()->exists('sections.'.$s['type']))
    @php
        $neutral = in_array($s['type'], ['intro', 'text', 'features', 'steps', 'gallery', 'capabilities', 'envelope', 'services', 'industries', 'projects', 'documents', 'faq', 'quote_short', 'contact'], true);
        $tone = $neutral ? ($toneIdx++ % 2 === 0 ? 'sec--white' : 'sec--zinc') : null;
    @endphp
    @include('sections.'.$s['type'], ['d' => $s['data'] ?? [], 'tone' => $tone, 'legal' => $legal ?? false])
@endforeach
