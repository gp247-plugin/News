<?php

namespace App\GP247\Plugins\News\Admin\Livewire;

use App\GP247\Plugins\News\Models\NewsCategory;
use App\GP247\Plugins\News\Models\NewsCategoryDescription;
use GP247\Core\AdminShell\Infrastructure\HasMultilingualDescriptions;
use GP247\Core\AdminShell\Infrastructure\ResourcePanel;
use GP247\Core\Models\AdminLanguage;
use Illuminate\Validation\Rule;

/**
 * News category manager (v2 port of the legacy AdminLTE NewsCategoryController) —
 * two-panel screen (add/edit form left, list right) on the core ResourcePanel
 * base plus the reusable multilingual trait, mirroring
 * GP247\Shop\Admin\Livewire\CategoryManager (same field shape: per-language
 * title/keyword/description, alias, parent tree, image, status, sort). Scoped
 * to the current admin store (legacy behaviour: `store_id` column, not a pivot).
 * Gated by `admin_news_category`.
 *
 * @aidlc-unit plugin-news
 * @aidlc-story GP247-v2-compat
 * @aidlc-adr ADR-001, ADR-005, ADR-006, ADR-007
 */
class NewsCategoryManager extends ResourcePanel
{
    use HasMultilingualDescriptions;

    protected ?string $permission = 'admin_news_category';

    /**
     * @return array<int, string>
     */
    protected function multilingualFields(): array
    {
        return ['title', 'keyword', 'description'];
    }

    /**
     * @return class-string
     */
    protected function descriptionModelClass(): string
    {
        return NewsCategoryDescription::class;
    }

    /**
     * @return string
     */
    protected function descriptionForeignKey(): string
    {
        return 'category_id';
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function baseQuery()
    {
        // WHY: legacy screen scoped list/lookups to the current admin store via a
        // direct `store_id` column (not a store pivot) — keep the same scoping.
        return NewsCategory::query()->where('store_id', session('adminStoreId'));
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
        return ['sort', 'asc'];
    }

    /**
     * @return string
     */
    protected function panelView(): string
    {
        return 'Plugins/News::Admin.news_category';
    }

    /**
     * @return string
     */
    protected function pageTitle(): string
    {
        return gp247_language_render('Plugins/News::Category.admin.title');
    }

    /**
     * @return string
     */
    protected function baseRoute(): string
    {
        // WHY: keep the legacy route name — AdminMenu rows already installed on
        // existing sites reference `route_admin::admin_news_category.index`.
        return 'admin_news_category.index';
    }

    /**
     * @return array<string, mixed>
     */
    protected function formDefaults(): array
    {
        return ['image' => '', 'alias' => '', 'parent' => '', 'status' => 1, 'sort' => 0];
    }

    /**
     * Reset both the scalar form and the per-language description state.
     *
     * @return void
     */
    public function resetForm(): void
    {
        parent::resetForm();
        $this->initDescriptions();
    }

    /**
     * @param NewsCategory $model
     * @return array<string, mixed>
     */
    protected function fillForm($model): array
    {
        $this->fillDescriptions($model->descriptions);

        return [
            'image' => (string) $model->image,
            'alias' => (string) $model->alias,
            'parent' => (string) ($model->parent ?? ''),
            'status' => (int) $model->status,
            'sort' => (int) $model->sort,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        $table = (new NewsCategory())->getTable();

        return [
            'form.alias' => ['required', 'string', 'max:100', Rule::unique($table, 'alias')->ignore($this->editingId)->where('store_id', session('adminStoreId'))],
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
            'parent' => empty($data['parent']) ? null : $data['parent'],
            'status' => empty($data['status']) ? 0 : 1,
            'sort' => (int) ($data['sort'] ?? 0),
            'store_id' => session('adminStoreId'),
        ];

        if ($this->editingId !== null) {
            $category = NewsCategory::findOrFail($this->editingId);
            $category->update($attributes);
        } else {
            $category = NewsCategory::create($attributes);
        }

        $this->saveDescriptions($category->id);

        if (function_exists('gp247_cache_clear')) {
            gp247_cache_clear('cache_news_category');
        }
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
                gp247_cache_clear('cache_news_category');
            }
        }
    }

    /**
     * Parent-category options (id => indented title) for the form select.
     *
     * @return array<string, string>
     */
    public function parentOptions(): array
    {
        return (new NewsCategory())->getTreeCategoriesAdmin();
    }

    /**
     * Flat map of category id => title (current admin store), used by the list
     * to resolve each row's `parent` id into a readable parent name.
     *
     * @return array<string, string>
     *
     * @aidlc-unit plugin-news
     * @aidlc-story GP247-v2-compat
     */
    public function categoryTitles(): array
    {
        return NewsCategory::getListTitleAdmin();
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
