<?php
use Illuminate\Support\Facades\Route;
use App\GP247\Plugins\News\Admin\Livewire\NewsCategoryManager;
use App\GP247\Plugins\News\Admin\Livewire\NewsContentManager;

$config = file_get_contents(__DIR__.'/gp247.json');
$config = json_decode($config, true);

if(gp247_extension_check_active($config['configGroup'], $config['configKey'])) {

    $langUrl = GP247_SEO_LANG ?'{lang?}/' : '';
    $suffix = GP247_SUFFIX_URL;
    $prefixNewsCategory = config($config['configGroup'].'/'.$config['configKey'].'.GP247_PREFIX_NEWS');
    Route::group(
        [
            'middleware' => GP247_FRONT_MIDDLEWARE,
            'prefix'    => $langUrl,
            'namespace' => 'App\GP247\Plugins\News\Controllers',
        ],
        function () use($prefixNewsCategory, $suffix) {
            Route::get($prefixNewsCategory, 'FrontController@index')
            ->name('news.index');
            Route::get($prefixNewsCategory.'/{alias}', 'FrontController@categoryProcessFront')
                ->name('news.category');
            Route::get($prefixNewsCategory.'/{category}/{alias}'.$suffix, 'FrontController@contentProcessFront')
                ->name('news.content');
        }
    );

    // v2 (Livewire + TailAdmin) — replaces the legacy AdminLTE controllers.
    // Route names kept identical to v1 for back-compat: existing sites already
    // have AdminMenu rows referencing `route_admin::admin_news_category.index`
    // and `route_admin::admin_news_content.index` (see ExtensionModel::installExtension()).
    Route::group(
        [
            'prefix' => GP247_ADMIN_PREFIX,
            'middleware' => GP247_ADMIN_MIDDLEWARE,
        ],
        function () {
            Route::group(['prefix' => 'news_category'], function () {
                Route::get('/', NewsCategoryManager::class)->name('admin_news_category.index');
                Route::get('/create', NewsCategoryManager::class)->name('admin_news_category.create');
                Route::get('/edit/{id}', NewsCategoryManager::class)->name('admin_news_category.edit');
            });

            Route::group(['prefix' => 'news_content'], function () {
                Route::get('/', NewsContentManager::class)->name('admin_news_content.index');
                Route::get('/create', NewsContentManager::class)->name('admin_news_content.create');
                Route::get('/edit/{id}', NewsContentManager::class)->name('admin_news_content.edit');
            });
        }
    );
}