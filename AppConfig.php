<?php
/**
 * Plugin format 1.0
 */
#App\GP247\Plugins\News\AppConfig.php
namespace App\GP247\Plugins\News;

use App\GP247\Plugins\News\Models\ExtensionModel;
use App\GP247\Plugins\News\Models\NewsCategory;
use App\GP247\Plugins\News\Models\NewsContent;
use GP247\Core\Models\AdminConfig;
use GP247\Core\Models\AdminHome;
use GP247\Core\ExtensionConfigDefault;
use GP247\Front\Models\FrontLink;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
class AppConfig extends ExtensionConfigDefault
{
    public function __construct()
    {
        //Read config from gp247.json
        $config = file_get_contents(__DIR__.'/gp247.json');
        $config = json_decode($config, true);
    	$this->configGroup = $config['configGroup'];
        $this->configKey = $config['configKey'];
        $this->configCode = $config['configCode'];
        $this->requireCore = $config['requireCore'] ?? [];
        $this->requireComposerPackages = $config['requireComposerPackages'] ?? [];
        $this->requireGp247Extensions = $config['requireGp247Extensions'] ?? [];
        //Path
        $this->appPath = $this->configGroup . '/' . $this->configKey;
        //Language
        $this->title = trans($this->appPath.'::lang.title');
        //Image logo or thumb
        $this->image = $this->appPath.'/'.$config['image'];
        //
        $this->version = $config['version'];
        $this->auth = $config['auth'];
        $this->link = $config['link'];
    }

    public function install()
    {
        $check = AdminConfig::where('key', $this->configKey)
            ->where('group', $this->configGroup)->first();
        if ($check) {
            //Check Plugin key exist
            $return = ['error' => 1, 'msg' =>  gp247_language_render('admin.extension.plugin_exist')];
        } else {
            //Insert plugin to config
            $dataInsert = [
                [
                    'group'  => $this->configGroup,
                    'code'    => $this->configCode,
                    'key'    => $this->configKey,
                    'sort'   => 0,
                    'store_id' => GP247_STORE_ID_GLOBAL,
                    'value'  => self::ON, //Enable extension
                    'detail' => $this->appPath.'::lang.title',
                ],
            ];
            try {
                AdminConfig::insert(
                    $dataInsert
                );
                (new ExtensionModel)->installExtension();
                $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.install_success')];
            } catch (\Throwable $th) {
                $return = ['error' => 1, 'msg' => $th->getMessage()];
            }
        }
        return $return;
    }

    public function uninstall()
    {
        //Please delete all values inserted in the installation step
        try {
            (new AdminConfig)
                ->where('key', $this->configKey)
                ->orWhere('code', $this->configKey.'_config')
                ->delete();
            //Admin config home
            AdminHome::where('extension', $this->appPath)->delete();
            (new ExtensionModel)->uninstallExtension();
            $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.uninstall_success')];
        } catch (\Throwable $e) {
            $return = ['error' => 1, 'msg' => $e->getMessage()];
        }

        return $return;
    }
    
    public function enable()
    {
        $process = (new AdminConfig)
            ->where('group', $this->configGroup)
            ->where('key', $this->configKey)
            ->update(['value' => self::ON]);

        //Admin config home
        AdminHome::where('extension', $this->appPath)->update(['status' => 1]);

        if (!$process) {
            $return = ['error' => 1, 'msg' => gp247_language_render('admin.extension.action_error', ['action' => 'Enable'])];
        }
        $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.enable_success')];
        return $return;
    }

    public function disable()
    {
        $process = (new AdminConfig)
            ->where('group', $this->configGroup)
            ->where('key', $this->configKey)
            ->update(['value' => self::OFF]);
        if (!$process) {
            $return = ['error' => 1, 'msg' => gp247_language_render('admin.extension.action_error', ['action' => 'Disable'])];
        }
        $return = ['error' => 0, 'msg' => gp247_language_render('admin.extension.disable_success')];
        //Admin config home
        AdminHome::where('extension', $this->appPath)->update(['status' => 0]);

        return $return;
    }

    /**
     * Remove all News data belonging to the given store.
     * Called by the platform when a store is deleted.
     *
     * Uses each()->delete() (not bulk delete) so Eloquent boot() cascades
     * descriptions and images correctly.
     * Schema::hasTable() guards against the plugin being uninstalled first.
     *
     * @param  mixed $storeId
     * @return void
     *
     * @aidlc-unit plugin-news
     * @aidlc-story US-news-store-remove-cleanup
     * @aidlc-adr plugin-news_store-1to1-link-compat
     */
    public function removeStore($storeId = null)
    {
        if ($storeId === null) {
            return;
        }

        FrontLink::where('module', $this->configKey)
            ->where('store_id', $storeId)
            ->delete();

        $schema = Schema::connection(GP247_DB_CONNECTION);

        if ($schema->hasTable(GP247_DB_PREFIX . 'news_content')) {
            NewsContent::where('store_id', $storeId)
                ->each(fn ($model) => $model->delete());
        }

        if ($schema->hasTable(GP247_DB_PREFIX . 'news_category')) {
            NewsCategory::where('store_id', $storeId)
                ->each(fn ($model) => $model->delete());
        }
    }

    /**
     * Seed the minimum News data for a newly added store.
     * Idempotent: skipped if a FrontLink for this plugin+store already exists.
     *
     * @param  mixed $storeId
     * @return void
     *
     * @aidlc-unit plugin-news
     * @aidlc-story US-news-store-setup-link
     * @aidlc-adr plugin-news_store-1to1-link-compat
     */
    public function setupStore($storeId = null)
    {
        if ($storeId === null) {
            return;
        }

        $exists = FrontLink::where('module', $this->configKey)
            ->where('store_id', $storeId)
            ->exists();

        if ($exists) {
            return;
        }

        FrontLink::create([
            'name'     => $this->appPath . '::' . $this->configKey . '.front.index',
            'url'      => 'route_front::news.index',
            'target'   => '_self',
            'module'   => $this->configKey,
            'group'    => 'menu',
            'status'   => '1',
            'sort'     => '20',
            'store_id' => $storeId,
        ]);
    }

    // Process when click button plugin in admin    
    
    public function clickApp()
    {
        //
    }

    /**
     * Get info plugin
     *
     * @return  [type]  [return description]
     */
    public function getInfo()
    {
        $arrData = [
            'title' => $this->title,
            'key' => $this->configKey,
            'code' => $this->configCode,
            'image' => $this->image,
            'permission' => self::ALLOW,
            'version' => $this->version,
            'auth' => $this->auth,
            'link' => $this->link,
            'value' => 0, // this return need for plugin shipping
            'appPath' => $this->appPath
        ];

        return $arrData;
    }
}
