> 🌐 **言語:** [🇻🇳 Tiếng Việt](./readme_vi.md) · [🇬🇧 English](./readme.md) · 🇯🇵 日本語（現在）

# GP247 用ニュースプラグイン

GP247 Framework 向けのニュース管理プラグインで、次の機能を備えています：

## 主な機能

- 多階層のニュースカテゴリー管理
- カテゴリー別の記事管理
- カテゴリーと記事の多言語対応
- SEO に適した URL

## ライセンス

GP247 News Plugin は [MIT](https://opensource.org/licenses/MIT) ライセンスに基づくオープンソースソフトウェアです。

## インストール手順

GP247 は拡張機能（plugin/template）のインストール方法を 4 通りサポートしています：**Online**（公式ライブラリから）、**Import**（`.zip` ファイルをアップロード）、**手動**（フォルダーをサーバーにコピー）、**コマンドライン**（CLI、gp247 3.x 以降）。

公式ドキュメントを参照してください：[拡張機能のインストールガイド](https://gp247.net/en/docs/user-guide-extension/guide-to-installing-the-extension.html)。

### 方法 1：Online でインストール（ライブラリから）

ステップ 0 — API License を登録する（一度だけ）：
- Admin の **API License** 設定を開きます。
- 「登録／設定」をクリックして、無料のライセンスキーを取得します。
- システムがキーを `.env` ファイルの変数 `GP247_API_LICENSE` に自動で保存します。

インストール手順：
- **Plugin**（または Template）メニューを開き、**Online** タブを選びます。
- GP247 の拡張機能ライブラリを閲覧し、検索／絞り込みを使って現在の Core バージョンに合ったプラグインを選びます。
- **Install** をクリックすると、システムが自動でダウンロード、互換性チェック、展開、インストールを行います。
- インストールが終わると成功メッセージが表示され、プラグインがインストール済みの一覧に表示されます。
- 注意：有料の拡張機能には、無料の API License に加えて、その拡張機能専用のライセンスキーが必要です。

### 方法 2：zip ファイルをインポート
- **Plugin** メニューを開き、**Import** タブを選びます。
- パソコンからプラグインの `.zip` ファイルを選んで **Upload** をクリックすると、システムが自動でチェックしてインストールします。
- zip ファイルの要件：
  - `.zip` 形式のみ受け付け、容量は最大 50MB です。
  - ルートフォルダーに `gp247.json` ファイルが含まれている必要があります。
  - 既存の拡張機能と `configKey` が重複してはいけません。

### 方法 3：手動でインストール
- ソースコードを展開し、プラグインのフォルダー（`AppConfig.php` と `gp247.json` を含む）を次の場所にコピーします：
  - `app/GP247/Plugins/News`
- プラグインに `public` フォルダーがある場合は、その中身を次の場所にコピーします：
  - `public/GP247/Plugins/News`
- プラグインは Admin > Extensions > Plugins に自動で表示されます（未インストールの状態）。「News」を探して **Install** をクリックし、有効化します。

### 方法 4：コマンドラインでインストール（CLI、gp247 3.x）

gp247 3.x 以降では、管理画面を開かずに、**News** を GP247 ライブラリからダウンロードしてコマンドラインでそのままインストールできます。サイトのルートフォルダーでターミナルを開き、次を実行します：

```bash
# 1) ウェブサイトごとに 1 回だけ：GP247 ライブラリに接続するための API License（無料）を登録
php artisan gp247:ext-register-license

# 2) ライブラリからプラグインをダウンロードしてインストール
php artisan gp247:ext-install --type=plugin --key=News
```

- ステップ 1 の前に、`.env` の `APP_URL` がサイトの**実際のドメイン**になっていること（`http://localhost` のままでないこと）を確認してください。ライセンスはこのドメインに紐付けられます。
- インストールが終わると、プラグインは**有効化済み**になり、キャッシュも自動で更新されるため、管理画面での追加操作は不要です。
- コマンドは `gp247.json` に宣言された条件（core のバージョン、composer パッケージ、依存プラグイン）を自動で確認します。不足があればコマンドは停止し、何が足りないかを明確に表示します。
- このプラグインには `gp247/front` パッケージがインストールされている必要があります。不足している場合、コマンドは停止して通知します。
- `app/GP247/Plugins/News` フォルダーがすでにサーバー上にある場合（手動でコピーした、またはインストーラーに同梱されていた場合）、コマンドは再ダウンロードせずに**その場でインストール**します。
- プラグインがすでにインストールされている場合、コマンドは実行を拒否します。新しいバージョンに上げるには `php artisan gp247:ext-update --type=plugin --key=News` を実行してください。
- コマンドの末尾に `--json` を付けると、機械で読み取れる形式で結果を受け取れます（スクリプト／CI 用）。
- 下記の **インストール後** の章（Enable/Disable、Config、必要に応じて `php artisan optimize:clear` を実行）はそのまま必要です。
- 詳細：[プラグイン・テンプレートのインストールガイド](https://github.com/gp247net/gp247-docs/blob/main/extension/install-extension.md) · [コマンドリファレンス](https://github.com/gp247net/gp247-docs/blob/main/system/command-line-reference.md)。

## インストール後
- プラグインは通常すぐに動作します。Enable/Disable と Config のオプションがある場合があります。
- システムが自動でキャッシュをクリアします。必要に応じて、次も実行してください：

```bash
php artisan optimize:clear
```

## 参考
- 拡張機能のインストールガイド：https://gp247.net/en/docs/user-guide-extension/guide-to-installing-the-extension.html
- GitHub（News Plugin）：https://github.com/gp247net/news

