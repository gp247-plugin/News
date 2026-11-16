<?php
#App\GP247\Plugins\News\Controllers\FrontController.php
namespace App\GP247\Plugins\News\Controllers;

use App\GP247\Plugins\News\AppConfig;
use GP247\Front\Controllers\RootFrontController;
use App\GP247\Plugins\News\Models\NewsCategory;
use App\GP247\Plugins\News\Models\NewsContent;

class FrontController extends RootFrontController
{
    public $plugin;

    public function __construct()
    {
        parent::__construct();
        $this->plugin = new AppConfig;
    }

    
    /**
     * Process news front
     *
     * @param [type] ...$params
     * @return void
     */
    public function index(...$params) 
    {
        if (GP247_SEO_LANG) {
            $lang = $params[0] ?? '';
            gp247_lang_switch($lang);
        }
        return $this->_news();
    }

    /**
     * News 
     * @return [type] [description]
     */
    private function _news()
    {
        $sortBy = 'sort';
        $sortOrder = 'asc';
        $filter_sort = request('filter_sort') ?? '';
        $filterArr = [
            'sort_desc' => ['sort', 'desc'],
            'sort_asc' => ['sort', 'asc'],
            'id_desc' => ['id', 'desc'],
            'id_asc' => ['id', 'asc'],
        ];
        if (array_key_exists($filter_sort, $filterArr)) {
            $sortBy = $filterArr[$filter_sort][0];
            $sortOrder = $filterArr[$filter_sort][1];
        }

        $entries = (new NewsCategory)
            ->getCategoryRoot()
            ->setSort([$sortBy, $sortOrder])
            ->setPaginate()
            ->setLimit(gp247_config('item_list'))
            ->getData();

        $subPath = 'news_index';
        $view = gp247_plugin_process_view($this->plugin->appPath, $this->GP247TemplatePath,$subPath);
        gp247_check_view($view);

        return view(
            $view,
            array(
                'title'       => gp247_language_render($this->plugin->appPath.'::News.front.index'),
                'entries'   => $entries,
                'keyword'     => '',
                'description' => '',
                'layout_page' => 'news_index',
                'filter_sort' => $filter_sort,
                'breadcrumbs' => [
                    ['url'    => '', 'title' => gp247_language_render($this->plugin->appPath.'::News.front.index')],
                ],
            )
        );
    }


    /**
     * Process front category
     *
     * @param [type] ...$params
     * @return void
     */
    public function categoryProcessFront(...$params) 
    {
        if (GP247_SEO_LANG) {
            $lang = $params[0] ?? '';
            $alias = $params[1] ?? '';
            gp247_lang_switch($lang);
        } else {
            $alias = $params[0] ?? '';
        }
        return $this->_category($alias);
    }

    /**
     * Category news
     * @return [type] [description]
     */
    private function _category($alias)
    {
        $newsCategory = (new NewsCategory)->getDetail($alias, 'alias');
            if ($newsCategory) { 

                $subCategories = (new NewsCategory)
                ->setParent($newsCategory->id)
                ->setPaginate()
                ->setLimit(gp247_config('item_list'))
                ->getData();

                $entries = (new NewsContent)
                    ->getContentToCategory($newsCategory->id)
                    ->setLimit(gp247_config('item_list'))
                    ->setPaginate()
                    ->getData();

                // Process breadcrumbs
                $parentCategory = (new NewsCategory)
                    ->getDetail($newsCategory->parent, 'id');
                $breadcrumbs = [];
                $breadcrumbs[] = ['url'    => gp247_route_front('news.index'), 'title' => gp247_language_render($this->plugin->appPath.'::News.front.index')];
                if ($parentCategory) {
                    $breadcrumbs[] = [
                        'url'    => $parentCategory->getUrl(),
                        'title' => $parentCategory->title,
                    ];
                }
                $breadcrumbs[] = ['url'    => '', 'title' => $newsCategory->title];
                // End process breadcrumbs


                $subPath = 'news_category';
                $view = gp247_plugin_process_view($this->plugin->appPath, $this->GP247TemplatePath,$subPath);
                gp247_check_view($view);

                return view(
                    $view,
                    array(
                        'title'       => $newsCategory['title'],
                        'description' => $newsCategory['description'],
                        'keyword'     => $newsCategory['keyword'],
                        'entries'     => $entries,
                        'newsCategory' => $newsCategory,
                        'subCategories' => $subCategories,
                        'layout_page' => 'news_category',
                        'breadcrumbs' => $breadcrumbs,
                    )
                );
            } else {
                // WHY itemNotFound(): it answers HTTP 404; a plain view() was a 200
                // soft 404 (US-news-seo-correct-status, modification 20261117T055341).
                return $this->itemNotFound();
            }
    }

    /**
     * Process front Content detail
     *
     * @param [type] ...$params
     * @return void
     */
    public function contentProcessFront(...$params) 
    {
        if (GP247_SEO_LANG) {
            $lang = $params[0] ?? '';
            $category = $params[1] ?? '';
            $alias = $params[2] ?? '';
            gp247_lang_switch($lang);
        } else {
            $category = $params[0] ?? '';
            $alias = $params[1] ?? '';
        }
        return $this->_content($category, $alias);
    }

    /**
     * Content detail
     *
     * @param   [string]  $alias  [$alias description]
     *
     * @return  [type]          [return description]
     */
    private function _content($category, $alias)
    {
        $newsContent = (new NewsContent)->getDetail($alias, 'alias');

        if ($newsContent) {
            $categoryNews = $newsContent->category;

            if (!$categoryNews) {
                return $this->itemNotFound();
            }

            // WHY 301: the article lives under one category; a permanent redirect
            // hands old links' ranking to the real URL (a 302 does not).
            if ($categoryNews->alias != $category) {
                return redirect(gp247_route_front('news.content', ['category' => $categoryNews->alias, 'alias' => $alias]), 301);
            }

            $subPath = 'news_detail';
            $view = gp247_plugin_process_view($this->plugin->appPath, $this->GP247TemplatePath,$subPath);
            gp247_check_view($view);

            $title = ($newsContent) ? $newsContent->title : gp247_language_render('front.notfound');
            return view($view,
                array(
                    'title'           => $title,
                    'newsContent'     => $newsContent,
                    'description'     => $this->descriptionOf($newsContent),
                    'keyword'         => $newsContent['keyword'],
                    'og_image'        => $newsContent->getImage(),
                    'layout_page'     => 'news_detail',
                    'breadcrumbs'     => [
                        ['url'        => $categoryNews->getUrl(), 'title' => $categoryNews->getFull()->title],
                        ['url'        => '', 'title' => $title],
                    ],
                )
            );
        } else {
            return $this->itemNotFound();
        }

    }

    /**
     * Description of an article for meta tags and JSON-LD: its own description, or
     * — when it has none — the start of its text (tags stripped, whitespace
     * collapsed, at most 160 characters, cut on a word boundary), so search engines
     * never get an empty description.
     *
     * @param NewsContent $newsContent
     * @return string
     *
     * @aidlc-unit news-plugin
     * @aidlc-story US-news-seo-correct-status
     */
    private function descriptionOf(NewsContent $newsContent): string
    {
        $own = trim((string) $newsContent['description']);
        if ($own !== '') {
            return $own;
        }

        $text = html_entity_decode(strip_tags((string) $newsContent['content']), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim((string) preg_replace('/\s+/u', ' ', $text));

        return \Illuminate\Support\Str::limit($text, 160, '…', preserveWords: true);
    }
}
