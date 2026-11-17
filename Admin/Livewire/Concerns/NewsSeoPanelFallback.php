<?php

namespace App\GP247\Plugins\News\Admin\Livewire\Concerns;

/**
 * Stand-in for gp247/front's HasSeoPanel on a front without the content SEO layer.
 * Same method names, nothing SEO-related: no tab state, no rule, and the screen's
 * own save runs as before. Selected by NewsSeoPanel.
 *
 * @aidlc-unit news-plugin
 * @aidlc-story US-news-seo-panel
 * @aidlc-adr ADR-plugin-news-seo-panel-soft-dependency
 */
trait NewsSeoPanelFallback
{
    /**
     * Panel state; stays empty without the front SEO layer.
     *
     * @var array<string, mixed>
     */
    public array $seo = [];

    /**
     * Registered content-type key of the screen.
     *
     * @return string
     */
    abstract protected function seoType(): string;

    /**
     * No panel to load.
     *
     * @param int|string|null $id
     * @return void
     */
    protected function fillSeo($id): void
    {
    }

    /**
     * @return array<string, array<int, string>> No rule.
     */
    protected function seoRules(): array
    {
        return [];
    }

    /**
     * @return array<string, string> No label.
     */
    protected function seoAttributeLabels(): array
    {
        return [];
    }

    /**
     * @return array<string, string> No label.
     */
    protected function seoValidationAttributes(): array
    {
        return [];
    }

    /**
     * Run the screen's own save only.
     *
     * @param callable $persist
     * @return void
     */
    protected function persistWithSeo(callable $persist): void
    {
        $persist();
    }
}
