<?php
#app/GP247/Plugins/News/Models/NewsContent.php
namespace App\GP247\Plugins\News\Models;

use App\GP247\Plugins\News\Models\NewsCategory;
use App\GP247\Plugins\News\Models\NewsImage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use GP247\Core\Models\ModelTrait;
use App\GP247\Plugins\News\Models\NewsContentDescription;
use Cache;

class NewsContent extends Model
{
    use ModelTrait;
    use \GP247\Core\Models\UuidTrait;
    
    protected static $getListTitleAdmin = null;

    /**
     * Placeholder body of every sample article, per language.
     */
    private const SAMPLE_BODY = [
        'en' => '<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.<img alt="" src="https://static.gp247.net/logo/org.jpg" style="width: 262px; height: 262px; float: right; margin: 10px;" /></p>
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>',
        'ja' => '<p>これはサンプル記事の本文です。ここに記事の内容が入ります。テキストを編集して、お店の最新情報やお知らせを掲載してください。<img alt="" src="https://static.gp247.net/logo/org.jpg" style="width: 262px; height: 262px; float: right; margin: 10px;" /></p>
                <p>これはサンプル記事の本文です。管理画面から自由に書き換えることができます。写真や見出しを追加して、読みやすい記事に仕上げましょう。</p>',
        'vi' => '<p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.<img alt="" src="https://static.gp247.net/logo/org.jpg" style="width: 262px; height: 262px; float: right; margin: 10px;" /></p>
                <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.Lorem ipsum dolor sit amet, consectetur adipisicing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>',
    ];

    /**
     * Sample articles created on install: a technology blog whose articles each show the
     * hosted photo news-<image>.png that matches their topic.
     *
     * Category 1 = tech news, category 2 = tips & guides (see install()).
     *
     * @return array<int, array{alias: string, image: int, category: string, titles: array<string, string>}>
     */
    public static function sampleArticles(): array
    {
        return [
            ['alias' => 'laptop-ai-the-he-moi', 'image' => 1, 'category' => '1', 'titles' => [
                'en' => "What's new in the latest AI laptops", 'ja' => '最新AIノートPCの進化ポイント', 'vi' => 'Laptop AI thế hệ mới có gì khác?']],
            ['alias' => 'dien-thoai-gap-hay-dien-thoai-thuong', 'image' => 3, 'category' => '1', 'titles' => [
                'en' => 'Foldable or classic phone: which one fits you?', 'ja' => '折りたたみスマホと通常スマホ、どちらを選ぶ？', 'vi' => 'Điện thoại gập hay điện thoại thường: chọn loại nào?']],
            ['alias' => 'dong-ho-thong-minh-theo-doi-suc-khoe', 'image' => 9, 'category' => '1', 'titles' => [
                'en' => 'What a smartwatch can track about your health', 'ja' => 'スマートウォッチで分かる健康データ', 'vi' => 'Đồng hồ thông minh theo dõi sức khoẻ những gì?']],
            ['alias' => 'san-uu-dai-do-cong-nghe-cuoi-nam', 'image' => 10, 'category' => '1', 'titles' => [
                'en' => 'Year-end tech deals worth waiting for', 'ja' => '年末セールで狙いたいデジタル製品', 'vi' => 'Săn ưu đãi đồ công nghệ mùa mua sắm cuối năm']],
            ['alias' => 'may-anh-mirrorless-cho-nguoi-moi', 'image' => 8, 'category' => '2', 'titles' => [
                'en' => 'Mirrorless cameras for beginners', 'ja' => '初心者向けミラーレスカメラ入門', 'vi' => 'Máy ảnh mirrorless cho người mới bắt đầu']],
            ['alias' => 'cach-chon-tai-nghe-chong-on', 'image' => 2, 'category' => '2', 'titles' => [
                'en' => 'How to choose noise-cancelling headphones', 'ja' => 'ノイズキャンセリングヘッドホンの選び方', 'vi' => 'Cách chọn tai nghe chống ồn phù hợp']],
            ['alias' => 'bat-dau-voi-nha-thong-minh', 'image' => 4, 'category' => '2', 'titles' => [
                'en' => 'Getting started with a smart home', 'ja' => 'はじめてのスマートホーム', 'vi' => 'Bắt đầu với nhà thông minh từ thiết bị đơn giản']],
            ['alias' => 'chuan-bi-goc-choi-game', 'image' => 5, 'category' => '2', 'titles' => [
                'en' => 'Setting up your gaming corner', 'ja' => 'ゲーミング環境の整え方', 'vi' => 'Chuẩn bị góc chơi game: tay cầm, tai nghe và hơn thế']],
            ['alias' => 'thoi-quen-bao-mat-dien-thoai', 'image' => 6, 'category' => '2', 'titles' => [
                'en' => '5 habits that keep your phone secure', 'ja' => 'スマホを安全に使う5つの習慣', 'vi' => '5 thói quen giúp điện thoại luôn an toàn']],
            ['alias' => 'sac-nhanh-co-hai-pin-khong', 'image' => 7, 'category' => '2', 'titles' => [
                'en' => 'Does fast charging harm your battery?', 'ja' => '急速充電はバッテリーに悪い？', 'vi' => 'Sạc nhanh có làm hại pin không?']],
        ];
    }
    protected static $getListContentGroupByParentAdmin = null;


    public $table = GP247_DB_PREFIX.'news_content';
    protected $guarded = [];
    protected $connection = GP247_DB_CONNECTION;

    protected  $gp247_category = []; 

    public function category()
    {
        return $this->belongsTo(NewsCategory::class, 'category_id', 'id');
    }

    public function descriptions()
    {
        return $this->hasMany(NewsContentDescription::class, 'content_id', 'id');
    }

    //Function get text description
    public function getText()
    {
        return $this->descriptions()->where('lang', gp247_get_locale())->first();
    }
    public function getTitle()
    {
        return $this->getText()->title ?? '';
    }
    public function getDescription()
    {
        return $this->getText()->description ?? '';
    }
    public function getKeyword()
    {
        return $this->getText()->keyword?? '';
    }
    public function getContent()
    {
        return $this->getText()->content;
    }
    //End  get text description

    public function images()
    {
        return $this->hasMany(NewsImage::class, 'content_id', 'id');
    }

    protected static function boot()
    {
        parent::boot();
        // before delete() method call this
        static::deleting(function ($content) {
            //Delete content descrition
            $content->descriptions()->delete();
            //Delete content images
            $content->images()->delete();
        });
        // Own SEO values go with the article (US-news-seo-panel); `deleted` so a
        // cancelled delete keeps them.
        static::deleted(function ($content) {
            \App\GP247\Plugins\News\Seo::forget(\App\GP247\Plugins\News\Seo::TYPE_CONTENT, $content->id);
        });

        //Uuid
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = gp247_generate_id();
            }
        });
    }


    /*
    Get thumb
    */
    public function getThumb()
    {
        return gp247_image_get_path_thumb($this->image);
    }

    /*
    Get image
    */
    public function getImage()
    {
        return gp247_image_get_path($this->image);

    }

    /**
     * [getUrl description]
     * @return [type] [description]
     */
    public function getUrl($lang = null)
    {
        return gp247_route_front('news.content', ['category' => $this->category->alias, 'alias' => $this->alias, 'lang' => $lang ?? app()->getLocale()]);
    }

    //Scort
    public function scopeSort($query, $sortBy = null, $sortOrder = 'asc')
    {
        $sortBy = $sortBy ?? 'sort';
        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Get news detail
     *
     * @param   [string]  $key     [$key description]
     * @param   [string]  $type  [id, alias]
     * @param   [int]  $checkActive
     *
     */
    public function getDetail(string $key, $type = null, $checkActive = 1)
    {
        if(empty($key)) {
            return null;
        }
        $tableDescription = (new NewsContentDescription)->getTable();

        $content = $this
            ->leftJoin($tableDescription, $tableDescription . '.content_id', $this->getTable() . '.id')
            ->where($tableDescription . '.lang', gp247_get_locale());

        if ($type == null) {
            $content = $content->where('id', $key);
        } else {
            $content = $content->where($type, $key);
        }
        if ($checkActive) {
            $content = $content->where('status', 1);
        }
        $content = $content->where('store_id', config('app.storeId'));
        return $content->first();
    }

    /**
     * Get list new content
     *
     * @param   array  $arrOpt
     * Example: ['status' => 1, 'top' => 1]
     * @param   array  $arrSort
     * Example: ['sortBy' => 'id', 'sortOrder' => 'asc']
     * @param   array  $arrLimit  [$arrLimit description]
     * Example: ['step' => 0, 'limit' => 20]
     * @return  [type]             [return description]
     */
    public function getList($arrOpt = [], $arrSort = [], $arrLimit = [])
    {
        $sortBy = $arrSort['sortBy'] ?? null;
        $sortOrder = $arrSort['sortOrder'] ?? 'asc';
        $step = $arrLimit['step'] ?? 0;
        $limit = $arrLimit['limit'] ?? 0;

        $data = $this->sort($sortBy, $sortOrder);
        if(count($arrOpt)) {
            foreach ($arrOpt as $key => $value) {
                $data = $data->where($key, $value);
            }
        }
        if((int)$limit) {
            $start = $step * $limit;
            $data = $data->offset((int)$start)->limit((int)$limit);
        }
        $data = $data->get()->groupBy('id');

        return $data;
    }

    //=========================

    public function uninstall()
    {
        // SEO values of every article/category first, while the tables still list
        // their ids (US-news-seo-panel).
        \App\GP247\Plugins\News\Seo::forgetAll();

        //Content
        $schema = Schema::connection(GP247_DB_CONNECTION);
        $tableContent = $this->getTable();
        if ($schema->hasTable($tableContent)) {
            $schema->drop($tableContent);
        }
        if ($schema->hasTable($tableContent.'_description')) {
            $schema->drop($tableContent.'_description');
        }

        //Category
        $tableCategory = (new NewsCategory)->getTable();
        if ($schema->hasTable($tableCategory)) {
            $schema->drop($tableCategory);
        }

        if ($schema->hasTable($tableCategory.'_description')) {
            $schema->drop($tableCategory.'_description');
        }

        //Image
        $tableImage = (new NewsImage)->getTable();
        if ($schema->hasTable($tableImage)) {
            $schema->drop($tableImage);
        }
    }

    public function install()
    {
        $this->uninstall();

        $schema = Schema::connection(GP247_DB_CONNECTION);
        
        //Content
        $tableContent = $this->getTable();
        $schema->create($tableContent, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('category_id')->default(0);
            $table->string('image', 100)->nullable();
            $table->string('alias', 120)->index();
            $table->integer('sort')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->uuid('store_id')->default(1)->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        $schema->create($tableContent.'_description', function (Blueprint $table) {
            $table->uuid('content_id');
            $table->string('lang', 10);
            $table->string('title', 300)->nullable();
            $table->string('keyword', 200)->nullable();
            $table->string('description', 500)->nullable();
            $table->mediumText('content')->nullable();
            $table->primary(['content_id', 'lang']);
        });

        $contentRows = [];
        $descriptionRows = [];
        foreach (self::sampleArticles() as $index => $article) {
            $id = (string) ($index + 1);
            $contentRows[] = ['id' => $id, 'alias' => $article['alias'], 'image' => 'https://static.gp247.net/file/s-cart/news/news-'.$article['image'].'.png', 'category_id' => $article['category'], 'sort' => 0, 'status' => '1', 'created_at' => date("Y-m-d"), 'store_id' => GP247_STORE_ID_ROOT];
            foreach ($article['titles'] as $lang => $title) {
                $descriptionRows[] = ['content_id' => $id, 'lang' => $lang, 'title' => $title, 'keyword' => '', 'description' => '', 'content' => self::SAMPLE_BODY[$lang]];
            }
        }
        DB::connection(GP247_DB_CONNECTION)->table($tableContent)->insert($contentRows);
        DB::connection(GP247_DB_CONNECTION)->table($tableContent.'_description')->insert($descriptionRows);


        //Category
        $tableCategory = (new NewsCategory)->getTable();
        $schema->create($tableCategory, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('image', 100)->nullable();
            $table->uuid('parent')->default(null)->nullable();
            $table->string('alias', 120)->index();
            $table->uuid('store_id')->default(1)->index();
            $table->integer('sort')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });

        $schema->create($tableCategory.'_description', function (Blueprint $table) {
            $table->uuid('category_id');
            $table->string('lang', 10);
            $table->string('title', 300)->nullable();
            $table->string('keyword', 200)->nullable();
            $table->string('description', 500)->nullable();
            $table->primary(['category_id', 'lang']);
        });

        DB::connection(GP247_DB_CONNECTION)->table($tableCategory)->insert(
            [
                ['id' => '1', 'alias'=> 'tin-cong-nghe', 'image' => 'https://static.gp247.net/file/s-cart/news/news-1.png', 'parent' => '', 'sort' => '0', 'status' => '1', 'store_id' => 1],
                ['id' => '2', 'alias'=> 'meo-huong-dan', 'image' => 'https://static.gp247.net/file/s-cart/news/news-4.png', 'parent' => '', 'sort' => '0', 'status' => '1', 'store_id' => 1],
            ]
        );

        DB::connection(GP247_DB_CONNECTION)->table($tableCategory.'_description')->insert(
            [
                ['category_id' => '1', 'lang' => 'en', 'title' => 'Tech news', 'keyword' => '', 'description' => ''],
                ['category_id' => '1', 'lang' => 'ja', 'title' => 'テクノロジーニュース', 'keyword' => '', 'description' => ''],
                ['category_id' => '1', 'lang' => 'vi', 'title' => 'Tin công nghệ', 'keyword' => '', 'description' => ''],
                ['category_id' => '2', 'lang' => 'en', 'title' => 'Tips & guides', 'keyword' => '', 'description' => ''],
                ['category_id' => '2', 'lang' => 'ja', 'title' => '使い方・ヒント', 'keyword' => '', 'description' => ''],
                ['category_id' => '2', 'lang' => 'vi', 'title' => 'Mẹo & hướng dẫn', 'keyword' => '', 'description' => ''],
            ]
        );

        //Image
        $tableImage = (new NewsImage)->getTable();
        $schema->create($tableImage, function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('content_id')->default(0);
            $table->string('image', 100)->nullable();
            $table->integer('sort')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
        });
    }


    /**
     * Start new process get data
     *
     * @return  new model
     */
    public function start() {
        return new NewsContent;
    }
    
    /**
     * Set array category 
     *
     * @param   [array|int]  $category 
     *
     */
    private function setCategory($category) {
        if (is_array($category)) {
            $this->gp247_category = $category;
        } else {
            $this->gp247_category = array($category);
        }
        return $this;
    }

    /**
     * Get content to array Catgory
     * @param   [array|int]  $arrCategory 
     */
    public function getContentToCategory($arrCategory) {
        $this->setCategory($arrCategory);
        return $this;
    }

    /**
     * build Query
     */
    public function buildQuery() {
        $tableDescription = (new NewsContentDescription)->getTable();

        //description
        $query = $this
            ->leftJoin($tableDescription, $tableDescription . '.content_id', $this->getTable() . '.id')
            ->where($tableDescription . '.lang', gp247_get_locale());
        //search keyword
        if ($this->gp247_keyword !='') {
            $query = $query->where(function ($sql) use($tableDescription){
                $sql->where($tableDescription . '.title', 'like', '%' . $this->gp247_keyword . '%');
            });
        }

        if (count($this->gp247_category)) {
            $query = $query->whereIn('category_id', $this->gp247_category);
        }

        $query = $query->where('status', 1)
            ->where('store_id', config('app.storeId'));

        $query = $this->processMoreQuery($query);
        
        if ($this->gp247_random) {
            $query = $query->inRandomOrder();
        } else {
            $checkSort = false;
            if (is_array($this->gp247_sort) && count($this->gp247_sort)) {
                foreach ($this->gp247_sort as  $rowSort) {
                    if (is_array($rowSort) && count($rowSort) == 2) {
                        if ($rowSort[0] == 'sort') {
                            $checkSort = true;
                        }
                        $query = $query->sort($rowSort[0], $rowSort[1]);
                    }
                }
            }
        }

        //Use field "sort" if haven't above
        if (empty($checkSort)) {
            $query = $query->orderBy($this->getTable().'.sort', 'asc');
        }
        //Default, will sort id
        $query = $query->orderBy($this->getTable().'.id', 'desc');

        return $query;
    }    

    /**
     * Get list content with category alias
     *
     * @param   string  $aliasCategory  [$aliasCategory description]
     *
     * @return  [type]                  [return description]
     */
    public function getContentWithAliasCategory(string $aliasCategory) {
        $tableDescription = (new NewsContentDescription)->getTable();
        $tableNewsCategory = (new NewsCategory)->getTable();
        $content = $this
        ->selectRaw($this->getTable().'.*, '.$tableDescription.'.*')
        ->leftJoin($tableNewsCategory, $tableNewsCategory . '.id', $this->getTable() . '.category_id')
        ->leftJoin($tableDescription, $tableDescription . '.content_id', $this->getTable() . '.id')
        ->where($tableDescription . '.lang', gp247_get_locale())
        ->where($tableNewsCategory . '.alias', $aliasCategory)
        ->where($this->getTable() . '.status', 1)
        ->where($this->getTable() . '.store_id', config('app.storeId'))
        ->get();
        return $content;
    }

    /**
     * Get content detail in admin
     *
     * @param   [type]  $id  [$id description]
     *
     * @return  [type]       [return description]
     */
    public static function getContentAdmin($id) {
        return self::where('id', $id)
        ->where('store_id', session('adminStoreId'))
        ->first();
    }

    /**
     * Get list content in admin
     *
     * @param   [array]  $dataSearch  [$dataSearch description]
     *
     * @return  [type]               [return description]
     */
    public function getContentListAdmin(array $dataSearch) {
        $keyword          = $dataSearch['keyword'] ?? '';
        $sort             = $dataSearch['sort'] ?? '';
        $arrSort          = $dataSearch['arrSort'] ?? '';
        $tableDescription = (new NewsContentDescription)->getTable();
        $tableContent    = $this->getTable();

        $contentList = (new NewsContent)
            ->leftJoin($tableDescription, $tableDescription . '.content_id', $tableContent . '.id')
            ->where('store_id', session('adminStoreId'))
            ->where($tableDescription . '.lang', gp247_get_locale());

        if ($keyword) {
            $contentList = $contentList->where(function ($sql) use($tableDescription, $keyword){
                $sql->where($tableDescription . '.title', 'like', '%' . $keyword . '%');
            });
        }

        if ($sort && array_key_exists($sort, $arrSort)) {
            $field = explode('__', $sort)[0];
            $sort_field = explode('__', $sort)[1];
            if($field == 'id') {
                $field = 'created_at';
            }
            $contentList = $contentList->sort($field, $sort_field);
        } else {
            $contentList = $contentList->sort('created_at', 'desc');
        }
        $contentList = $contentList->paginate(20);

        return $contentList;
    }

    /**
     * Get array title content
     * user for admin 
     *
     * @return  [type]  [return description]
     */
    public static function getListTitleAdmin()
    {
        $tableDescription = (new NewsContentDescription)->getTable();
        $table = (new NewsContent)->getTable();
        if (gp247_config_global('cache_status') && gp247_config_global('cache_content')) {
            if (!Cache::has(session('adminStoreId').'_cache_content_'.gp247_get_locale())) {
                if (self::$getListTitleAdmin === null) {
                    self::$getListTitleAdmin = self::join($tableDescription, $tableDescription.'.content_id', $table.'.id')
                    ->where('lang', gp247_get_locale())
                    ->where('store_id', session('adminStoreId'))
                    ->pluck('title', 'id')
                    ->toArray();
                }
                gp247_cache_set(session('adminStoreId').'_cache_content_'.gp247_get_locale(), self::$getListTitleAdmin);
            }
            return Cache::get(session('adminStoreId').'_cache_content_'.gp247_get_locale());
        } else {
            if (self::$getListTitleAdmin === null) {
                self::$getListTitleAdmin = self::join($tableDescription, $tableDescription.'.content_id', $table.'.id')
                ->where('lang', gp247_get_locale())
                ->where('store_id', session('adminStoreId'))
                ->pluck('title', 'id')
                ->toArray();
            }
            return self::$getListTitleAdmin;
        }
    }


    /**
     * Create a new content
     *
     * @param   array  $dataInsert  [$dataInsert description]
     *
     * @return  [type]              [return description]
     */
    public static function createContentAdmin(array $dataInsert) {

        return self::create($dataInsert);
    }


    /**
     * Insert data description
     *
     * @param   array  $dataInsert  [$dataInsert description]
     *
     * @return  [type]              [return description]
     */
    public static function insertDescriptionAdmin(array $dataInsert) {

        return NewsContentDescription::create($dataInsert);
    }

    /**
     * [checkAliasValidationAdmin description]
     *
     * @param   [type]$type     [$type description]
     * @param   null  $fieldValue    [$field description]
     * @param   null  $contentId      [$contentId description]
     * @param   null  $storeId  [$storeId description]
     * @param   null            [ description]
     *
     * @return  [type]          [return description]
     */
    public function checkAliasValidationAdmin($type = null, $fieldValue = null, $contentId = null, $storeId = null) {
        $storeId = $storeId ? $storeId : session('adminStoreId');
        $type = $type ? $type : 'alias';
        $fieldValue = $fieldValue;
        $contentId = $contentId;
        $tablePTS = (new NewsContent)->getTable();
        $check =  $this
        ->where($type, $fieldValue)
        ->where($tablePTS . '.store_id', $storeId);
        if($contentId) {
            $check = $check->where('id', '<>', $contentId);
        }
        $check = $check->first();

        if($check) {
            return false;
        } else {
            return true;
        }
    }


}
