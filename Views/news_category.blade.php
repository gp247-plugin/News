@php
/*
$layout_page = news_category
**Variables:**
- $newsCategory
- $subCategories: paginate
- $entries: paginate
Use paginate: $entries->appends(request()->except(['page','_token']))->links()
*/
@endphp

{{--
    News category listing (v2 port). Overrides block_main_content_center (not
    block_main) so breadcrumb/sidebar blocks stay wired — see news_index.blade.php
    for the same rationale. Reuses common/item_single.blade.php for both the
    sub-category tiles and the entries grid (already compiled; the legacy
    item_single_long.blade.php partial this view used before is orphaned —
    unstyled `post-classic-*` classes left over from the removed template).
--}}
@extends($GP247TemplatePath.'.layout')

@section('block_main_content_center')
<div class="lg:col-span-12 w-full">

    @if ($subCategories->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mb-8">
            @foreach ($subCategories as $cate)
                @php
                    $cate = [
                        'title' => $cate->title,
                        'url' => $cate->getUrl(),
                        'thumb' => $cate->getThumb(),
                    ];
                @endphp
                @include($GP247TemplatePath.'.common.item_single', ['item' => $cate])
            @endforeach
        </div>

        @include($GP247TemplatePath.'.common.pagination', ['items' => $subCategories])
    @endif

    @if ($entries->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($entries as $entryDetail)
                @php
                    $item = [
                        'title' => $entryDetail->title,
                        'url' => $entryDetail->getUrl(),
                        'thumb' => $entryDetail->getThumb(),
                    ];
                @endphp
                @include($GP247TemplatePath.'.common.item_single', ['item' => $item])
            @endforeach
        </div>

        @include($GP247TemplatePath.'.common.pagination', ['items' => $entries])
    @else
        <p class="text-center text-ink-400 py-12">{{ gp247_language_render('front.no_item') }}</p>
    @endif

</div>
@endsection

@push('scripts')
@endpush
