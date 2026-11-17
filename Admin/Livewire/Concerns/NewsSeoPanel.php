<?php

/**
 * Trait name the News edit screens use for the SEO tab.
 *
 * Resolves to gp247/front's shared HasSeoPanel when the installed front has the
 * content SEO layer, otherwise to NewsSeoPanelFallback (same methods, does nothing).
 * WHY an alias instead of `use HasSeoPanel;` in the screens: on an older front that
 * trait does not exist and PHP would fail while loading the screen class, so the
 * whole news admin would break. Loaded by the PSR-4 autoloader on first use.
 *
 * @aidlc-unit news-plugin
 * @aidlc-story US-news-seo-panel
 * @aidlc-adr ADR-plugin-news-seo-panel-soft-dependency
 */

namespace App\GP247\Plugins\News\Admin\Livewire\Concerns;

class_alias(
    trait_exists(\GP247\Front\Admin\Livewire\Concerns\HasSeoPanel::class)
        ? \GP247\Front\Admin\Livewire\Concerns\HasSeoPanel::class
        : NewsSeoPanelFallback::class,
    NewsSeoPanel::class
);
