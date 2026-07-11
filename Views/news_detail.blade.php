@php
/*
$layout_page = news_detail
**Variables:**
- $newsContent: collection
*/
@endphp

{{--
    News content detail (v2 port). Overrides block_main_content_center (not
    block_main) so breadcrumb/sidebar blocks stay wired — see
    news_index.blade.php for the same rationale. Title heading + rich-content
    wrapper reuse the exact classes already proven on
    screen/shop_product_detail.blade.php's description tab.
--}}
@extends($GP247TemplatePath.'.layout')

@section('block_main_content_center')
<div class="lg:col-span-12 w-full">
    <h1 class="text-2xl font-semibold text-ink-800 mb-4">{{ $newsContent->title }}</h1>
    <div class="text-sm text-ink-700 leading-relaxed space-y-3 [&_a]:text-brand-600 [&_img]:rounded-lg [&_img]:max-w-full">
        {!! gp247_html_render($newsContent->content) !!}
    </div>
</div>
@endsection

@push('scripts')
@endpush
