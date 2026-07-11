@php
/*
$layout_page = news_index
**Variables:**
- $entries: paginate
Use paginate: $entries->appends(request()->except(['page','_token']))->links()
*/
@endphp

{{--
    News list (v2 port). Overrides block_main_content_center (not block_main)
    so the page keeps the layout's container-x/grid wrapper, breadcrumb and
    admin-configurable sidebar blocks (gp247_render_block, layout_page =
    'news_index') — matching screen/shop_item_list.blade.php, the closest
    proven GP247Front listing screen. Reuses common/item_single.blade.php
    (thumb + title card, already compiled) rather than the unstyled legacy
    Bootstrap markup this view used before.
--}}
@extends($GP247TemplatePath.'.layout')

@section('block_main_content_center')
<div class="lg:col-span-12 w-full">
    @if ($entries->count())
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
            @foreach ($entries as $item)
                @php
                    $item = [
                        'title' => $item->title,
                        'url' => $item->getUrl(),
                        'thumb' => $item->getThumb(),
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
