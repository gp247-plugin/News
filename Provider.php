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
 }
