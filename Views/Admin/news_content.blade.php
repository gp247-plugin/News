{{--
    News content manager — v2 port (Livewire + TailAdmin, two-panel: add/edit
    form left, list right, on the core ResourcePanel base + multilingual trait).
    Replaces the legacy AdminLTE view (which extended the now-deleted
    `gp247-core::layout`). UI text via gp247_language_render.

    @aidlc-unit plugin-news
    @aidlc-story GP247-v2-compat

    Variables: $rows (NewsContent paginator); $form, $desc, $editingId, $galleryImages, $sortField, $sortDir (state).
--}}
@php($inputCls = 'w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100')
@php($labelCls = 'mb-2 block text-sm font-medium text-gray-700 dark:text-gray-200')

<div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

    {{-- Left: add / edit form --}}
    <x-gp247::card :title="gp247_language_render($editingId ? 'action.edit' : 'Plugins/News::Content.admin.add_news_title')">
        <form wire:submit="save" class="space-y-4">

            @php($tabsMap = [
                'general' => gp247_language_render('admin.product.tab_general'),
                'desc'    => gp247_language_render('admin.product.tab_description'),
            ])
            {{-- Surface validation errors on hidden tabs --}}
            @php($tabsWithErrors = array_values(array_intersect(
                array_keys($tabsMap),
                array_unique(array_map(
                    static fn ($k) => (str_starts_with($k, 'desc.') || $k === 'form.alias') ? 'desc' : 'general',
                    $errors->keys()
                ))
            )))
            <x-gp247::tabs :tabs="$tabsMap" :errors="$tabsWithErrors" default="general">

                {{-- ---- General ---- --}}
                <div x-show="tab === 'general'" class="space-y-4">
                    @include('gp247-admin::partials.store-scope-picker', ['testid' => 'news-content-store-select'])
                    {{-- wire:key on $formStoreId: the searchable-select is wire:ignore'd, so
                         changing the store must REPLACE it to reload the store-scoped
                         category options (ADR admin-shell_store-scoped-resource-panel). --}}
                    <div wire:key="news-content-category-{{ $formStoreId }}">
                        <x-gp247::searchable-select
                            model="form.category_id"
                            :label="gp247_language_render('Plugins/News::Content.admin.select_category')"
                            :options="collect($this->categoryOptions())->map(fn ($title, $id) => ['id' => (string) $id, 'label' => $title])->values()->all()"
                        />
                        @error('form.category_id')<p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>@enderror
                    </div>

                    <x-gp247::media-input :working-store="$formStoreId ?? ''" :label="gp247_language_render('Plugins/News::Content.image')" name="image" type="content"
                        wire:model="form.image" :value="$form['image'] ?? ''" :error="$errors->first('form.image')" />

                    <x-gp247::media-input :working-store="$formStoreId ?? ''" :label="gp247_language_render('Plugins/News::Content.additional_images')" name="galleryImages" type="content"
                        wire:model="galleryImages" :value="$galleryImages" help="{{ gp247_language_render('Plugins/News::Content.add_more_image') }}" />

                    <x-gp247::input type="number" min="0" :label="gp247_language_render('Plugins/News::Content.sort')"
                        name="sort" wire:model="form.sort" :error="$errors->first('form.sort')" />

                    <x-gp247::checkbox :label="gp247_language_render('Plugins/News::Content.status')" wire:model="form.status" value="1" />
                </div>

                {{-- ---- Description (per language) ---- --}}
                <div x-show="tab === 'desc'" x-cloak class="space-y-5">
                    <x-gp247::input :label="gp247_language_render('Plugins/News::Content.alias')" name="alias"
                        wire:model="form.alias" :error="$errors->first('form.alias')" />

                    @foreach ($this->languages() as $code => $lang)
                        <div class="space-y-4 rounded-lg border border-gray-200 p-4 dark:border-gray-700" wire:key="content-lang-{{ $code }}">
                            <h3 class="flex items-center gap-2 text-sm font-semibold text-gray-700 dark:text-gray-200">
                                @if ($lang->icon)
                                    {!! gp247_image_render($lang->icon, '20px', '20px', $lang->name) !!}
                                @endif
                                {{ $lang->name }}
                            </h3>
                            <x-gp247::input :label="gp247_language_render('Plugins/News::Content.title')" name="title_{{ $code }}"
                                wire:model="desc.{{ $code }}.title" :error="$errors->first('desc.' . $code . '.title')" required />
                            <x-gp247::input :label="gp247_language_render('Plugins/News::Content.keyword')" name="keyword_{{ $code }}"
                                wire:model="desc.{{ $code }}.keyword" :error="$errors->first('desc.' . $code . '.keyword')" />
                            <div>
                                <label class="{{ $labelCls }}">{!! gp247_language_render('Plugins/News::Content.description') !!}</label>
                                <textarea wire:model="desc.{{ $code }}.description" rows="2" class="{{ $inputCls }}"></textarea>
                                @error('desc.' . $code . '.description')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <x-gp247::rich-editor
                                :model="'desc.' . $code . '.content'"
                                :label="gp247_language_render('Plugins/News::Content.content')" />
                        </div>
                    @endforeach
                </div>

            </x-gp247::tabs>

            <div class="flex items-center justify-between border-t border-gray-200 pt-4 dark:border-gray-700">
                <x-gp247::button variant="secondary" wire:click="cancelEdit" data-testid="admin-news-content-form-cancel">{{ gp247_language_render($editingId ? 'admin.cancel' : 'admin.reset') }}</x-gp247::button>
                <x-gp247::button type="submit" wire:loading.attr="disabled">
                    <i class="fas fa-save"></i> {{ gp247_language_render($editingId ? 'admin.update' : 'admin.submit') }}
                </x-gp247::button>
            </div>
        </form>
    </x-gp247::card>

    {{-- Right: list --}}
    <x-gp247::card :title="gp247_language_render('Plugins/News::Content.admin.list')">
        <div class="mb-3">
            <input type="search" wire:model.live.debounce.300ms="keyword" placeholder="{{ gp247_language_render('Plugins/News::Content.admin.search') }}"
                class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100">
        </div>

        <x-gp247::table :empty="$rows->isEmpty() ? gp247_language_render('admin.no_records') : null">
            <x-slot:head>
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('Plugins/News::Content.image') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('Plugins/News::Content.title') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('Plugins/News::Content.category_id') }}</th>
                    <th class="cursor-pointer px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400" wire:click="setSort('sort')">
                        {{ gp247_language_render('Plugins/News::Content.sort') }} @if ($sortField === 'sort')<span class="text-[10px]">{{ $sortDir === 'asc' ? '▲' : '▼' }}</span>@endif
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('Plugins/News::Content.status') }}</th>
                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">{{ gp247_language_render('Plugins/News::Content.admin.action') }}</th>
                </tr>
            </x-slot:head>

            @foreach ($rows as $row)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 {{ (string) $row->id === (string) $editingId ? 'bg-blue-100 border-l-4 border-blue-500 dark:bg-blue-900 dark:border-blue-500' : '' }}" wire:key="news-content-{{ $row->id }}">
                    <td class="px-4 py-3">
                        @if ($row->image)<img src="{{ gp247_image_get_path_thumb($row->image) }}" alt="" class="h-9 w-auto rounded border border-gray-200 dark:border-gray-600">@else<span class="text-xs text-gray-400">—</span>@endif
                    </td>
                    <td class="px-4 py-3 text-sm font-medium text-gray-800 dark:text-gray-100">
                        {{ $row->getTitle() ?: $row->alias }}
                        @include('gp247-admin::partials.store-scope-line', ['storeId' => $row->store_id])
                    </td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->category->getTitle() ?? '—' }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->sort }}</td>
                    <td class="px-4 py-3"><x-gp247::badge :color="$row->status ? 'green' : 'gray'">{{ $row->status ? gp247_language_render('admin.active') : gp247_language_render('admin.inactive') }}</x-gp247::badge></td>
                    <td class="px-4 py-3">
                        <div class="flex items-center justify-end gap-1">
                            <x-gp247::button size="sm" variant="ghost" href="{{ gp247_route_front('news.content', ['category' => $row->category->alias ?? 'null', 'alias' => $row->alias]) }}" target="_blank"><i class="fas fa-external-link-alt"></i></x-gp247::button>
                            <x-gp247::button size="sm" variant="ghost" wire:click="editRow('{{ $row->id }}')" data-testid="admin-news-content-list-edit"><i class="fas fa-edit"></i></x-gp247::button>
                            <x-gp247::button size="sm" variant="ghost" wire:click="delete('{{ $row->id }}')" wire:confirm="{{ gp247_language_render('action.delete_confirm') }}"><i class="fas fa-trash-alt text-red-600"></i></x-gp247::button>
                        </div>
                    </td>
                </tr>
            @endforeach
        </x-gp247::table>

        <div class="mt-4">{{ $rows->links('gp247-admin::partials.pagination') }}</div>
    </x-gp247::card>
</div>
