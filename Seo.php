<?php

namespace App\GP247\Plugins\News;

use App\GP247\Plugins\News\Models\NewsCategory;
use App\GP247\Plugins\News\Models\NewsContent;

/**
 * Sitemap URL provider for the News plugin — reference implementation of the
 * plugin-manager ↔ seo integration contract (US-PLG-007, ADR
 * seo_plugin-sitemap-extension). Registered by `Provider.php` into
 * `config('gp247-config.front.seo_sitemap_providers')`; `SeoController`
 * calls {@see sitemapUrls()} at sitemap-build time and applies the same
 * `seo.sitemap_exclude_aliases` filter used for page/product/category URLs.
 *
 * @aidlc-unit plugin-manager
 * @aidlc-story US-PLG-007
 * @aidlc-adr seo_plugin-sitemap-extension
 */
class Seo
{
    /** SEO content type of an article (`gp247-config.front.seo_content_types`, US-news-seo-panel). */
    public const TYPE_CONTENT = 'news';

    /** SEO content type of a news category. */
    public const TYPE_CATEGORY = 'news_category';

    /** Ids handed to the front per round when clearing every news SEO value. */
    private const FORGET_CHUNK = 500;

    /**
     * Active news categories and content for the given store, shaped for
     * `SeoController::collectUrls()` (`alias` is required for the shared
     * exclusion filter to apply).
     *
     * @param  mixed $storeId
     * @return array<int, array{alias:string, loc:string, lastmod?:string, changefreq?:string, priority?:string}>
     */
    public static function sitemapUrls($storeId): array
    {
        $entries = [];

        $categories = NewsCategory::where('store_id', $storeId)
            ->where('status', 1)
            ->get(['id', 'alias', 'updated_at']);

        foreach ($categories as $category) {
            $entries[] = [
                'alias'      => $category->alias,
                'loc'        => gp247_route_front('news.category', ['alias' => $category->alias]),
                'lastmod'    => $category->updated_at?->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority'   => '0.6',
            ];
        }

        $contents = NewsContent::join(
            (new NewsCategory)->getTable(),
            (new NewsCategory)->getTable() . '.id',
            (new NewsContent)->getTable() . '.category_id',
        )
            ->where((new NewsContent)->getTable() . '.store_id', $storeId)
            ->where((new NewsContent)->getTable() . '.status', 1)
            ->where((new NewsCategory)->getTable() . '.status', 1)
            ->get([
                (new NewsContent)->getTable() . '.alias',
                (new NewsContent)->getTable() . '.updated_at',
                (new NewsCategory)->getTable() . '.alias as category_alias',
            ]);

        foreach ($contents as $content) {
            $entries[] = [
                'alias'      => $content->alias,
                'loc'        => gp247_route_front('news.content', ['category' => $content->category_alias, 'alias' => $content->alias]),
                'lastmod'    => $content->updated_at?->format('Y-m-d'),
                'changefreq' => 'weekly',
                'priority'   => '0.7',
            ];
        }

        return $entries;
    }

    /**
     * Whether the installed gp247/front keeps per-content SEO values for `$type`.
     * WHY: News runs on fronts without that layer too (no raised floor).
     *
     * @param string $type
     * @return bool
     *
     * @aidlc-unit news-plugin
     * @aidlc-story US-news-seo-panel
     */
    public static function available(string $type): bool
    {
        return class_exists(\GP247\Front\Library\SeoContentMeta::class)
            && \GP247\Front\Library\SeoContentMeta::isRegistered($type);
    }

    /**
     * Tell the front which news item the page shows, so its own SEO values apply.
     *
     * @param string     $type TYPE_CONTENT or TYPE_CATEGORY.
     * @param int|string $id
     * @return void
     *
     * @aidlc-unit news-plugin
     * @aidlc-story US-news-seo-panel
     */
    public static function declareSubject(string $type, $id): void
    {
        if (self::available($type)) {
            \GP247\Front\Library\SeoContentMeta::subject($type, $id);
        }
    }

    /**
     * Remove the SEO values of one deleted news item.
     *
     * @param string     $type TYPE_CONTENT or TYPE_CATEGORY.
     * @param int|string $id
     * @return void
     *
     * @aidlc-unit news-plugin
     * @aidlc-story US-news-seo-panel
     */
    public static function forget(string $type, $id): void
    {
        if (class_exists(\GP247\Front\Library\SeoContentMeta::class)) {
            \GP247\Front\Library\SeoContentMeta::forget($type, $id);
        }
    }

    /**
     * Remove the SEO values of every article and category, before the plugin drops
     * its tables. WHY here: the front's SEO check only cleans types it knows, so rows
     * of a removed plugin would stay forever.
     *
     * @return void
     *
     * @aidlc-unit news-plugin
     * @aidlc-story US-news-seo-panel
     */
    public static function forgetAll(): void
    {
        if (!class_exists(\GP247\Front\Library\SeoContentMeta::class)) {
            return;
        }
        $schema = \Illuminate\Support\Facades\Schema::connection(GP247_DB_CONNECTION);
        foreach ([self::TYPE_CONTENT => new NewsContent(), self::TYPE_CATEGORY => new NewsCategory()] as $type => $model) {
            if (!$schema->hasTable($model->getTable())) {
                continue;
            }
            $model->newQuery()->select('id')->orderBy('id')->chunk(self::FORGET_CHUNK, function ($rows) use ($type) {
                foreach ($rows as $row) {
                    self::forget($type, $row->id);
                }
            });
        }
    }
}
