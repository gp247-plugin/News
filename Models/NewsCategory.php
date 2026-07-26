<?php
#app/GP247/Plugins/News/Models/NewsCategory.php
namespace App\GP247\Plugins\News\Models;

use App\GP247\Plugins\News\Models\NewsCategoryDescription;
use App\GP247\Plugins\News\Models\NewsContent;
use Illuminate\Database\Eloquent\Model;
use GP247\Core\Models\ModelTrait;
use Cache;

class NewsCategory extends Model
{
    use ModelTrait;
    use \GP247\Core\Models\UuidTrait;


    protected static $getListTitleAdmin = null;
    protected static $getListCategoryGroupByParentAdmin = null;
    
    public $table = GP247_DB_PREFIX.'news_category';
    protected $guarded = [];
    protected $connection = GP247_DB_CONNECTION;

    protected  $gp247_parent = ''; // category id parent

    public function descriptions()
    {
        return $this->hasMany(NewsCategoryDescription::class, 'category_id', 'id');
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
    //End  get text description

    public function contents()
    {
        return $this->hasMany(NewsContent::class, 'category_id', 'id');
    }


/**
 * Get category parent
 * @return [type]     [description]
 */
    public function getParent()
    {
        return $this->getDetail($this->parent);

    }

     /**
     * Get list category new
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

        $tableDescription = (new NewsCategoryDescription)->getTable();

        //description
        $data = $this
            ->leftJoin($tableDescription, $tableDescription . '.category_id', $this->getTable() . '.id')
            ->where($tableDescription . '.lang', gp247_get_locale());

        $data = $data->sort($sortBy, $sortOrder);
        if(count($arrOpt)) {
            foreach ($arrOpt as $key => $value) {
                $data = $data->where($key, $value);
            }
        }
        if((int)$limit) {
            $start = $step * $limit;
            $data = $data->offset((int)$start)->limit((int)$limit);
        }
        $data = $data->get()->groupBy('parent');

        return $data;
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

    public function getUrl($lang = null)
    {
        return gp247_route_front('news.category', ['alias' => $this->alias, 'lang' => $lang ?? app()->getLocale()]);
    }


    protected static function boot()
    {
        parent::boot();
        // before delete() method call this
        static::deleting(function ($category) {
            //Delete category descrition
            $category->descriptions()->delete();
        });

        //Uuid
        static::creating(function ($model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = gp247_generate_id();
            }
        });
    }


//Scort
    public function scopeSort($query, $sortBy = null, $sortOrder = 'asc')
    {
        $sortBy = $sortBy ?? 'sort';
        return $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Get categoy detail
     *
     * @param   [string]  $key     [$key description]
     * @param   [string]  $type  [id, alias]
     * @param   [int]  $checkActive
     *
     */
    public function getDetail($key, $type = null, $checkActive = 1)
    {
        if(empty($key)) {
            return null;
        }

        $tableDescription = (new NewsCategoryDescription)->getTable();

        //description
        $category = $this
            ->leftJoin($tableDescription, $tableDescription . '.category_id', $this->getTable() . '.id')
            ->where($tableDescription . '.lang', gp247_get_locale())
            ->where($this->getTable() . '.store_id', config('app.storeId'));

        if ($type == null) {
            $category = $category->where('id', $key);
        } else {
            $category = $category->where($type, $key);
        }
        if ($checkActive) {
            $category = $category->where('status', 1);
        }

        return $category->first();
    }
    
    /**
     * Start new process get data
     *
     * @return  new model
     */
    public function start() {
        return new NewsCategory;
    }

    /**
     * Set category parent
     */
    public function setParent($parent) {
        $this->gp247_parent = $parent;
        return $this;
    }

    /**
     * Category root
     */
    public function getCategoryRoot() {
        $this->setParent(null);
        return $this;
    }


    /**
     * build Query
     */
    public function buildQuery() {
        $tableDescription = (new NewsCategoryDescription)->getTable();

        //description
        $query = $this
            ->leftJoin($tableDescription, $tableDescription . '.category_id', $this->getTable() . '.id')
            ->where($tableDescription . '.lang', gp247_get_locale());
        //search keyword
        if ($this->gp247_keyword !='') {
            $query = $query->where(function ($sql) use($tableDescription){
                $sql->where($tableDescription . '.title', 'like', '%' . $this->gp247_keyword . '%');
            });
        }

        $query = $query->where('status', 1)
        ->where('store_id', config('app.storeId'));

        if ($this->gp247_parent !== 'all') {
            if (empty($this->gp247_parent)) {
                // Parent is root with parent is null or empty
                $query = $query->where(function ($sql) {
                    $sql->where('parent', "")
                        ->orWhereNull('parent');
                });
            } else {
                $query = $query->where('parent', $this->gp247_parent);
            }
        }

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
     * Get category detail in admin
     *
     * @param   [type]  $id  [$id description]
     *
     * @return  [type]       [return description]
     */
    public static function getCategoryAdmin($id) {
        return self::where('id', $id)
        ->where('store_id', session('adminStoreId'))
        ->first();
    }

    /**
     * Get list category in admin
     *
     * @param   [array]  $dataSearch  [$dataSearch description]
     *
     * @return  [type]               [return description]
     */
    public function getCategoryListAdmin(array $dataSearch) {
        $keyword          = $dataSearch['keyword'] ?? '';
        $sort       = $dataSearch['sort'] ?? '';
        $arrSort          = $dataSearch['arrSort'] ?? '';
        $tableDescription = (new NewsCategoryDescription)->getTable();
        $tableCategory    = $this->getTable();

        $categoryList = (new NewsCategory)
            ->leftJoin($tableDescription, $tableDescription . '.category_id', $tableCategory . '.id')
            ->where('store_id', session('adminStoreId'))
            ->where($tableDescription . '.lang', gp247_get_locale());

        if ($keyword) {
            $categoryList = $categoryList->where(function ($sql) use($tableDescription, $tableCategory, $keyword){
                $sql->where($tableDescription . '.title', 'like', '%' . $keyword . '%');
            });
        }

        if ($sort && array_key_exists($sort, $arrSort)) {
            $field = explode('__', $sort)[0];
            $sort_field = explode('__', $sort)[1];
            if($field == 'id') {
                $field = 'created_at';
            }
            $categoryList = $categoryList->sort($field, $sort_field);
        } else {
            $categoryList = $categoryList->sort('created_at', 'desc');
        }
        $categoryList = $categoryList->paginate(20);

        return $categoryList;
    }

    /**
     * Get tree categories
     *
     * @param   [type]  $parent      [$parent description]
     * @param   [type]  &$tree       [&$tree description]
     * @param   [type]  $categories  [$categories description]
     * @param   [type]  &$st         [&$st description]
     *
     * @return  [type]               [return description]
     */
    public function getTreeCategoriesAdmin($parent = 0, &$tree = [], $categories = null, &$st = '')
    {
        $categories = $categories ?? $this->getListCategoryGroupByParentAdmin();
        $categoriesTitle =  $this->getListTitleAdmin();
        $tree = $tree ?? [];
        $lisCategory = $categories[$parent] ?? [];
        if ($lisCategory) {
            foreach ($lisCategory as $category) {
                $tree[$category['id']] = $st . $categoriesTitle[$category['id']]??'';
                if (!empty($categories[$category['id']])) {
                    $st .= '-- ';
                    $this->getTreeCategoriesAdmin($category['id'], $tree, $categories, $st);
                    $st = '';
                }
            }
        }
        return $tree;
    }

    /**
     * Get array title category
     * user for admin 
     *
     * @return  [type]  [return description]
     */
    public static function getListTitleAdmin()
    {
        $tableDescription = (new NewsCategoryDescription)->getTable();
        $table = (new NewsCategory)->getTable();
        if (gp247_config_global('cache_status') && gp247_config_global('cache_category')) {
            if (!Cache::has(session('adminStoreId').'_cache_category_'.gp247_get_locale())) {
                if (self::$getListTitleAdmin === null) {
                    self::$getListTitleAdmin = self::join($tableDescription, $tableDescription.'.category_id', $table.'.id')
                    ->where('lang', gp247_get_locale())
                    ->where('store_id', session('adminStoreId'))
                    ->pluck('title', 'id')
                    ->toArray();
                }
                gp247_cache_set(session('adminStoreId').'_cache_category_'.gp247_get_locale(), self::$getListTitleAdmin);
            }
            return Cache::get(session('adminStoreId').'_cache_category_'.gp247_get_locale());
        } else {
            if (self::$getListTitleAdmin === null) {
                self::$getListTitleAdmin = self::join($tableDescription, $tableDescription.'.category_id', $table.'.id')
                ->where('lang', gp247_get_locale())
                ->where('store_id', session('adminStoreId'))
                ->pluck('title', 'id')
                ->toArray();
            }
            return self::$getListTitleAdmin;
        }
    }


    /**
     * Get array title category
     * user for admin 
     *
     * @return  [type]  [return description]
     */
    public static function getListCategoryGroupByParentAdmin()
    {
        if (self::$getListCategoryGroupByParentAdmin === null) {
            self::$getListCategoryGroupByParentAdmin = self::selectRaw('id, COALESCE(NULLIF(parent, ""), NULLIF(parent, 0), 0) as parent')
            ->where('store_id', session('adminStoreId'))
            ->get()
            ->groupBy('parent')
            ->toArray();
        }
        return self::$getListCategoryGroupByParentAdmin;
    }


    /**
     * Create a new category
     *
     * @param   array  $dataInsert  [$dataInsert description]
     *
     * @return  [type]              [return description]
     */
    public static function createCategoryAdmin(array $dataInsert) {

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

        return NewsCategoryDescription::create($dataInsert);
    }

    /**
     * [checkAliasValidationAdmin description]
     *
     * @param   [type]$type     [$type description]
     * @param   null  $fieldValue    [$field description]
     * @param   null  $categoryId      [$categoryId description]
     * @param   null  $storeId  [$storeId description]
     * @param   null            [ description]
     *
     * @return  [type]          [return description]
     */
    public function checkAliasValidationAdmin($type = null, $fieldValue = null, $categoryId = null, $storeId = null) {
        $storeId = $storeId ? $storeId : session('adminStoreId');
        $type = $type ? $type : 'alias';
        $fieldValue = $fieldValue;
        $categoryId = $categoryId;
        $tablePTS = (new NewsCategory)->getTable();
        $check =  $this
            ->where($type, $fieldValue)
            ->where($tablePTS . '.store_id', $storeId);
        if($categoryId) {
            $check = $check->where('id', '<>', $categoryId);
        }
        $check = $check->first();

        if($check) {
            return false;
        } else {
            return true;
        }
    }
}
