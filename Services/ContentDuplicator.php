<?php

namespace App\GP247\Plugins\News\Services;

use App\GP247\Plugins\News\Models\NewsContent;
use App\GP247\Plugins\News\Models\NewsContentDescription;
use App\GP247\Plugins\News\Models\NewsImage;
use Illuminate\Support\Facades\DB;

/**
 * Duplicates a news article in one transaction: a new, switched-off article in the same
 * store and category that carries the original's image, gallery (the files are shared),
 * sort order and the texts of every language.
 *
 * What is copied is an allowlist: a column or table added later is not copied until it is
 * listed here. Not copied: the content SEO (SEO tab: title, description, canonical,
 * robots, OG image — the copy starts with an empty SEO tab, because people duplicating
 * rarely open it and the copy would go live with the original's meta), creation date.
 * Changed: status off, alias gets a `-copy` / `-copy-<n>` suffix unique in the article's
 * store, each title gets the "(copy)" label of its language. Lengths follow the article
 * form rules (alias 100, title 200) so the copy can be saved again right away.
 *
 * The caller decides which article may be read (store scope) and passes it in.
 *
 * @aidlc-unit news-plugin
 * @aidlc-story US-news-content-duplicate
 * @aidlc-adr ADR-plugin-news-content-duplicate
 */
class ContentDuplicator
{
    /** Max length of an alias (the article form rule `form.alias`). */
    public const ALIAS_MAX = 100;

    /** Max length of a title (the article form rule `desc.*.title`). */
    public const TITLE_MAX = 200;

    /** Language key of the title label. */
    public const NAME_SUFFIX_KEY = 'Plugins/News::Content.admin.duplicate_name_suffix';

    /** Suffix candidates tried before giving up. */
    private const SUFFIX_ATTEMPTS = 1000;

    /** Title label used when the language has no translation of the label. */
    private const NAME_SUFFIX_FALLBACK = '(copy)';

    /**
     * Duplicate an article.
     *
     * @param NewsContent $source Article to duplicate, already loaded within the admin's store scope.
     * @return NewsContent The copy.
     * @throws \RuntimeException When no free alias is found (nothing is kept).
     */
    public function duplicate(NewsContent $source): NewsContent
    {
        return DB::connection(GP247_DB_CONNECTION)->transaction(function () use ($source): NewsContent {
            $copy = NewsContent::create([
                'category_id' => $source->category_id,
                'image' => $source->image,
                'sort' => (int) $source->sort,
                'store_id' => $source->store_id,
                'alias' => $this->uniqueAlias((string) $source->alias, (string) $source->store_id),
                // WHY: a copy is never published before the editor has checked it.
                'status' => 0,
            ]);
            $this->copyDescriptions($source, $copy);
            $this->copyGallery($source, $copy);

            return $copy;
        });
    }

    /**
     * First free value of `$base-copy`, `$base-copy-2`… among the articles of a store.
     *
     * @param string $base    Alias of the original.
     * @param string $storeId Store the alias must be unique in.
     * @return string
     * @throws \RuntimeException When every candidate is taken.
     */
    public function uniqueAlias(string $base, string $storeId): string
    {
        for ($n = 1; $n <= self::SUFFIX_ATTEMPTS; $n++) {
            $candidate = self::suffixed($base, $n, self::ALIAS_MAX);
            if (!NewsContent::where('alias', $candidate)->where('store_id', $storeId)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('No free alias for the copy of article "' . $base . '".');
    }

    /**
     * `$base-copy` (n = 1) or `$base-copy-n`, the base cut (and trailing dashes dropped) to fit $max.
     *
     * @param string $base
     * @param int    $n   Candidate number, from 1.
     * @param int    $max Max length in characters.
     * @return string
     */
    public static function suffixed(string $base, int $n, int $max): string
    {
        $suffix = $n === 1 ? '-copy' : '-copy-' . $n;
        $room = max(0, $max - mb_strlen($suffix));

        return rtrim(mb_substr($base, 0, $room), '-') . $suffix;
    }

    /**
     * Copy every language row, each title with the copy label of its own language.
     *
     * @param NewsContent $source
     * @param NewsContent $copy
     * @return void
     */
    private function copyDescriptions(NewsContent $source, NewsContent $copy): void
    {
        $rows = [];
        foreach (NewsContentDescription::where('content_id', $source->id)->get() as $row) {
            $suffix = ' ' . $this->nameSuffix((string) $row->lang);
            $rows[] = [
                'content_id' => $copy->id,
                'lang' => $row->lang,
                'title' => trim(mb_substr((string) $row->title, 0, max(0, self::TITLE_MAX - mb_strlen($suffix))) . $suffix),
                'keyword' => $row->keyword,
                'description' => $row->description,
                'content' => $row->content,
            ];
        }
        if ($rows !== []) {
            NewsContentDescription::insert($rows);
        }
    }

    /**
     * Copy the gallery rows; the image files are shared, never copied.
     *
     * @param NewsContent $source
     * @param NewsContent $copy
     * @return void
     */
    private function copyGallery(NewsContent $source, NewsContent $copy): void
    {
        $rows = NewsImage::where('content_id', $source->id)->get(['image', 'sort', 'status'])
            ->map(static fn ($image): array => [
                'id' => gp247_generate_id(),
                'content_id' => $copy->id,
                'image' => $image->image,
                'sort' => (int) $image->sort,
                'status' => (int) $image->status,
            ])->all();
        if ($rows !== []) {
            NewsImage::insert($rows);
        }
    }

    /**
     * The "(copy)" label of a language.
     *
     * @param string $lang
     * @return string
     */
    private function nameSuffix(string $lang): string
    {
        $text = (string) trans(self::NAME_SUFFIX_KEY, [], $lang);

        // WHY: a language without the label returns the key itself.
        return ($text === '' || $text === self::NAME_SUFFIX_KEY) ? self::NAME_SUFFIX_FALLBACK : $text;
    }
}
