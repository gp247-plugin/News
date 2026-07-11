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
}
