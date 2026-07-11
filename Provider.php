<?php
/**
 * Provides everything needed for the Extension
 */

 $config = file_get_contents(__DIR__.'/gp247.json');
 $config = json_decode($config, true);
 $extensionPath = $config['configGroup'].'/'.$config['configKey'];
 
 $this->loadTranslationsFrom(__DIR__.'/Lang', $extensionPath);
 
 if (gp247_extension_check_active($config['configGroup'], $config['configKey'])) {
     
     $this->loadViewsFrom(__DIR__.'/Views', $extensionPath);
     
     if (file_exists(__DIR__.'/config.php')) {
         $this->mergeConfigFrom(__DIR__.'/config.php', $extensionPath);
     }
 
     if (file_exists(__DIR__.'/function.php')) {
         require_once __DIR__.'/function.php';
     }
     
     view()->share('modelNewsCategory', (new \App\GP247\Plugins\News\Models\NewsCategory));
     view()->share('modelNewsContent', (new \App\GP247\Plugins\News\Models\NewsContent));

    // Add layout page for news
    $configLayout = config('gp247-config.front.layout_page', []);
    $configLayout['news_index'] = gp247_language_render($extensionPath.'::News.layout_block_page.news_index')   ;
    $configLayout['news_category'] = gp247_language_render($extensionPath.'::News.layout_block_page.news_category');
    $configLayout['news_detail'] = gp247_language_render($extensionPath.'::News.layout_block_page.news_detail');
    config(['gp247-config.front.layout_page' => $configLayout]);
    // End add layout page for news

    // Register this plugin's active categories/content into sitemap.xml
    // (US-PLG-007, ADR seo_plugin-sitemap-extension, amended 20260711T135121).
    // SeoController reads this registry — gp247/front never hardcodes the
    // News plugin's name. 'key' also lets the admin toggle this plugin's
    // whole sitemap contribution off from the "SEO" admin screen
    // (seo.plugin_enabled.News); 'label' is what that screen displays,
    // reusing the manifest's own "name" field so no new translation key
    // is needed just for this.
    $sitemapProviders = config('gp247-config.front.seo_sitemap_providers', []);
    $sitemapProviders[] = [
        'key' => $config['configKey'],
        'label' => $config['name'],
        'callback' => [\App\GP247\Plugins\News\Seo::class, 'sitemapUrls'],
    ];
    config(['gp247-config.front.seo_sitemap_providers' => $sitemapProviders]);
 }
