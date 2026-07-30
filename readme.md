**🌐 Language:** **English** | [Tiếng Việt](readme_vi.md)

# News Plugin for GP247

News management plugin for GP247 Framework with the following features:

## Main Features

- Multi-level news category management
- Article management by categories 
- Multi-language support for categories and articles
- SEO friendly URLs

## License

The GP247 News Plugin is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
 
## Installation Guide

GP247 supports 3 ways to install an extension (plugin/template): **Online** (from the official library), **Import** (upload a `.zip` file), and **Manual** (copy the folder to the server).

Refer to the official documentation: [Guide to Installing the Extension](https://gp247.net/en/docs/user-guide-extension/guide-to-installing-the-extension.html).

### Method 1: Install Online (from the library)

Step 0 — Register an API License (one-time setup):
- Go to the **API License** settings in Admin.
- Click "Register/Setup" to obtain a free license key.
- The system automatically saves it to `GP247_API_LICENSE` in your `.env` file.

Installation steps:
- Go to the **Plugin** (or Template) menu and select the **Online** tab.
- Browse the GP247 extension library; use search/filter to pick a plugin compatible with your current Core version.
- Click **Install** — the system automatically downloads, validates compatibility, extracts, and installs it.
- A success message appears and the plugin shows up in the installed list.
- Note: paid extensions require their own license key in addition to the free API License.

### Method 2: Import zip file
- Go to the **Plugin** menu and select the **Import** tab.
- Choose the plugin `.zip` file from your computer and click **Upload** — the system validates and installs it automatically.
- ZIP requirements:
  - `.zip` format only, maximum 50MB.
  - Must contain a `gp247.json` file at the root level.
  - Must not duplicate the `configKey` of an existing extension.

### Method 3: Manual installation
- Unzip the source and copy the plugin folder (containing `AppConfig.php` and `gp247.json`) to:
  - `app/GP247/Plugins/News`
- If the plugin has a `public` folder, copy its contents to:
  - `public/GP247/Plugins/News`
- The plugin auto-appears in Admin > Extensions > Plugins (uninstalled state). Find "News" and click **Install** to activate.

## Post-Installation
- Plugins typically work immediately; they may offer Enable/Disable and Config options.
- The system auto-clears the cache. If needed, run:

```bash
php artisan optimize:clear
```

## References
- Extension installation guide: https://gp247.net/en/docs/user-guide-extension/guide-to-installing-the-extension.html
- GitHub (News Plugin): https://github.com/gp247net/news
    