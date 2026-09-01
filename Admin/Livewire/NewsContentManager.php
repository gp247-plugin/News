<?php

namespace App\GP247\Plugins\News\Admin\Livewire;

use App\GP247\Plugins\News\Models\NewsCategory;
use App\GP247\Plugins\News\Models\NewsContent;
use App\GP247\Plugins\News\Models\NewsContentDescription;
use App\GP247\Plugins\News\Models\NewsImage;
use GP247\Core\AdminShell\Infrastructure\HasMultilingualDescriptions;
use GP247\Core\AdminShell\Infrastructure\ResourcePanel;
use GP247\Core\Models\AdminLanguage;
use Illuminate\Validation\Rule;

/**
 * News content manager (v2 port of the legacy AdminLTE NewsContentController) —
 * two-panel screen (add/edit form left, list right) on the core ResourcePanel
 * base plus the reusable multilingual trait, mirroring NewsCategoryManager and
 * GP247\Shop\Admin\Livewire\CategoryManager. Adds a category select, a rich
 * (TinyMCE) per-language `content` body, and a simple image gallery
 * (delete-then-recreate, matching the legacy controller). Scoped to the current
 * admin store. Gated by `admin_news_content`.
 *
 * @aidlc-unit plugin-news
 * @aidlc-story GP247-v2-compat
 * @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007
 */
class NewsContentManager extends ResourcePanel
{
    use HasMultilingualDescriptions;

    protected ?string $permission = 'admin_news_content';

    /**
     * Keep list state (page/keyword/sort) and the edited record on screen when
     * editing/saving, instead of remounting via route navigation.
     *
     * @var bool
     * @aidlc-story US-AUI-two-panel-state-preservation
     * @aidlc-adr ADR-admin-shell-rbac-two-panel-state-preservation
     */
    protected bool $keepStateOnSave = true;

    /**
     * Comma-separated gallery image paths (matches <x-gp247::media-input>'s
     * multi-select callback, which joins picked LFM items with a comma).
     *
     * @var string
     */
    public string $galleryImages = '';

    /**
     * @return array<int, string>
     */
    protected function multilingualFields(): array
    {
        return ['title', 'keyword', 'description', 'content'];
    }

    /**
     * `content` is admin-authored rich HTML (TinyMCE) — must survive
     * saveDescriptions() unescaped.
     *
     * @return array<int, string>
     */
    protected function richDescriptionFields(): array
    {
        return ['content'];
    }

    /**
     * @return class-string
     */
    protected function descriptionModelClass(): string
    {
        return NewsContentDescription::class;
    }

    /**
     * @return string
     */
    protected function descriptionForeignKey(): string
    {
        return 'content_id';
    }

    /**
     * Store-scoped: pick a store on create (root admin), show it in the list, lock
     * it on edit; changing the store on create resets the (store-scoped) category.
     *
     * @return array<string, mixed>|null
     *
     * @aidlc-unit plugin-news
     * @aidlc-story US-SADM-store-content-assignment
     * @aidlc-adr admin-shell_store-scoped-resource-panel
     */
    protected function storeScoped(): ?array
    {
        return ['display' => 'title', 'reset' => ['category_id']];
    }

    /**
     * Store-scoped content query: root admin shows every store's articles (each row
     * labelled by its store); a scoped context (store-admin/switcher) or a
     * single-store install filters to the own store.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        $query = NewsContent::query();
        if (!($this->storeScopeActive() && $this->isRootScope())) {
            $query->where('store_id', $this->storeContext());
        }

        return $query;
    }

    /**
     * @return array<int, string>
     */
    protected function searchable(): array
    {
        return ['alias'];
    }

    /**
     * @return array<int, string>
     */
    protected function sortableColumns(): array
    {
        return ['alias', 'sort', 'id'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    protected function defaultSort(): array
    {
        // WHY: newest-created first by default (matches the legacy
        // NewsContentController list). `id` is a random UUID, not chronological,
        // so order by the `created_at` timestamp Eloquent fills on insert.
        return ['created_at', 'desc'];
    }

    /**
     * @return string
     */
    protected function panelView(): string
    {
        return 'Plugins/News::Admin.news_content';
    }

    /**
     * @return string
     */
    protected function pageTitle(): string
    {
        return gp247_language_render('Plugins/News::Content.admin.title');
    }

    /**
     * @return string
     */
    protected function baseRoute(): string
    {
        // WHY: keep the legacy route name — AdminMenu rows already installed on
        // existing sites reference `route_admin::admin_news_content.index`.
        return 'admin_news_content.index';
    }

    /**
     * @return array<string, mixed>
     */
    protected function formDefaults(): array
    {
        return ['image' => '', 'alias' => '', 'category_id' => '', 'status' => 1, 'sort' => 0];
    }

    /**
     * Reset the scalar form, per-language description state, and gallery.
     *
     * @return void
     */
    public function resetForm(): void
    {
        parent::resetForm();
        $this->initDescriptions();
        $this->galleryImages = '';
    }

    /**
     * @param NewsContent $model
     * @return array<string, mixed>
     */
    protected function fillForm($model): array
    {
        $this->fillDescriptions($model->descriptions);
        $this->galleryImages = $model->images->pluck('image')->implode(',');

        // Store is immutable on edit — expose it for the read-only display + to scope
        // the category option list to the record's own store.
        $this->formStoreId = (string) $model->store_id;

        return [
            'image' => (string) $model->image,
            'alias' => (string) $model->alias,
            'category_id' => (string) ($model->category_id ?? ''),
            'status' => (int) $model->status,
            'sort' => (int) $model->sort,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $table = (new NewsContent())->getTable();

        return [
            'form.category_id' => ['required'],
            'form.alias' => ['required', 'string', 'max:100', Rule::unique($table, 'alias')->ignore($this->editingId)->where('store_id', $this->currentStore())],
            'form.sort' => ['nullable', 'numeric', 'min:0'],
            'desc.*.title' => ['required', 'string', 'max:200'],
            'desc.*.keyword' => ['nullable', 'string', 'max:200'],
            'desc.*.description' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Derive alias from the first language's title when left blank (brownfield
     * parity) before the secure save pipeline.
     *
     * @return void
     */
    public function save(): void
    {
        if (empty($this->form['alias'])) {
            $firstLang = $this->firstDescriptionLanguage();
            $this->form['alias'] = $firstLang !== null ? ($this->desc[$firstLang]['title'] ?? '') : '';
        }
        $this->form['alias'] = gp247_word_limit(gp247_word_format_url((string) $this->form['alias']), 100);

        parent::save();
    }

    /**
     * @param array<string, mixed> $data
     * @return void
     */
    protected function persist(array $data): void
    {
        $attributes = [
            'image' => $data['image'] ?? '',
            'alias' => $data['alias'],
            'category_id' => $data['category_id'],
            'status' => empty($data['status']) ? 0 : 1,
            'sort' => (int) ($data['sort'] ?? 0),
        ];

        if ($this->editingId !== null) {
            // Store is immutable on edit — do NOT touch store_id (ADR 1-1).
            $store = (string) NewsContent::whereKey($this->editingId)->value('store_id');
            $this->assertCategorySameStore($attributes['category_id'] ?? null, $store);
            $content = NewsContent::findOrFail($this->editingId);
            $content->update($attributes);
        } else {
            // WHY: 1-1 ownership — a new article is owned by the store picked on
            // create (root admin) or the current scoped store (store-admin/switcher).
            $store = $this->resolveCreateStore();
            $this->assertCategorySameStore($attributes['category_id'] ?? null, $store);
            $attributes['store_id'] = $store;
            $content = NewsContent::create($attributes);
        }

        $this->saveDescriptions($content->id);
        $this->saveGallery($content->id);

        if (function_exists('gp247_cache_clear')) {
            gp247_cache_clear('cache_news_content');
        }
    }

    /**
     * Persist the gallery (delete-then-recreate, matching the legacy controller).
     *
     * @param int|string $contentId
     * @return void
     */
    private function saveGallery($contentId): void
    {
        NewsImage::where('content_id', $contentId)->delete();

        $paths = array_values(array_filter(array_map('trim', explode(',', $this->galleryImages))));
        if ($paths === []) {
            return;
        }

        $rows = [];
        foreach ($paths as $path) {
            $rows[] = [
                'id' => gp247_generate_id(),
                'content_id' => $contentId,
                'image' => $path,
                'status' => 1,
            ];
        }
        NewsImage::insert($rows);
    }

    /**
     * @param int|string $id
     * @return void
     */
    protected function deleteModel($id): void
    {
        $model = $this->baseQuery()->find($id);
        if ($model !== null) {
            $model->delete();
            if (function_exists('gp247_cache_clear')) {
                gp247_cache_clear('cache_news_content');
            }
        }
    }

    /**
     * Category options (id => indented title) for the form select.
     *
     * @return array<string, string>
     */
    public function categoryOptions(): array
    {
        if (!$this->storeScopeActive()) {
            return (new NewsCategory())->getTreeCategoriesAdmin();
        }

        // Store-scoped: only the picked/record store's categories (none until picked).
        $store = $this->currentStore();
        if ($store === null || $store === '') {
            return [];
        }

        $catTable = (new NewsCategory)->getTable();
        $descTable = (new \App\GP247\Plugins\News\Models\NewsCategoryDescription)->getTable();

        return NewsCategory::query()
            ->where($catTable . '.store_id', $store)
            ->join($descTable, $descTable . '.category_id', $catTable . '.id')
            ->where($descTable . '.lang', gp247_get_locale())
            ->pluck($descTable . '.title', $catTable . '.id')
            ->all();
    }

    /**
     * Reject a category that belongs to another store (same-store integrity,
     * server-side). No-op when not store-scoped or when no category is selected.
     *
     * @param int|string|null $categoryId
     * @param int|string      $storeId
     * @return void
     *
     * @aidlc-adr multi-store_one-to-one-store-ownership
     */
    private function assertCategorySameStore($categoryId, $storeId): void
    {
        if (!$this->storeScopeActive() || empty($categoryId)) {
            return;
        }
        $ok = NewsCategory::whereKey($categoryId)->where('store_id', $storeId)->exists();
        if (!$ok) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'form.category_id' => gp247_language_render('admin.store.related_wrong_store'),
            ]);
        }
    }

    /**
     * Active languages (code => language model) for the description tabs.
     *
     * @return array<string, mixed>
     */
    public function languages(): array
    {
        return AdminLanguage::getListActive()->all();
    }
}
