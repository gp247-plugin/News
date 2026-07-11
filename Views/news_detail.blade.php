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

    JSON-LD Article/BreadcrumbList (US-SEO-005, modification 20260711T143819):
    this plugin supplies the content-specific data ($newsContent,
    $breadcrumbs, already passed by FrontController::_content()); the actual
    JSON-LD building/escaping stays owned by gp247/front (SeoMeta +
    jsonld_* partials) so this plugin never duplicates that logic — same
    @push('jsonld') point ADR seo_head-consolidation opened up for Product/
    Breadcrumb, now used by a plugin for the first time.
--}}
@extends($GP247TemplatePath.'.layout')

@push('jsonld')
    @php
        $jsonldArticle = [
            'headline'      => $newsContent->title,
            'url'           => request()->url(),
            'imageUrl'      => gp247_file($newsContent->getImage()),
            'datePublished' => $newsContent->created_at ? \Illuminate\Support\Carbon::parse($newsContent->created_at)->toIso8601String() : null,
            'dateModified'  => $newsContent->updated_at ? \Illuminate\Support\Carbon::parse($newsContent->updated_at)->toIso8601String() : null,
            'description'   => $newsContent['description'],
        ];
        $breadcrumbItems = array_map(
            fn ($item) => ['name' => $item['title'], 'url' => $item['url'] ?: null],
            $breadcrumbs ?? []
        );
    @endphp
    @include($GP247TemplatePath.'.common.jsonld_article')
    @include($GP247TemplatePath.'.common.jsonld_breadcrumb')
@endpush

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
