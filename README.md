# Notification Composer for Wave

An optional Wave plugin for administrator-authored, site-wide **in-app** announcements. Compose a title, configurable label and rich-text message in the admin panel. Sending requires confirmation; no email is sent. All announcements use a shared team name/avatar, while Wave activity logs record the administrator who queued them.

## Requirements

Wave 3.7.1 (Laravel 13, Filament 5, Livewire 4), its standard database notifications table, and a configured queue worker. No additional Composer packages are required. The inbox uses the active theme’s app layout and Alpine. Anchor is the reference theme; premium themes have not been visually verified. A custom layout can be configured.

## Installation

1. Extract the release archive into your Wave application. It contains `resources/plugins/notification-composer/`. For a source checkout, copy this repository’s plugin files into that directory.
2. In `app/Providers/Filament/AdminPanelProvider.php`, register Wave’s plugin autoloader before constructing the panel, then add the adapter to the existing plugins list:

   ```php
   // Inside panel(), before return $panel:
   app(\Wave\Plugins\PluginManager::class);

   // Inside the panel's existing ->plugins([...]):
   ...(class_exists(\Wave\Plugins\NotificationComposer\FilamentPlugin::class)
       ? \Wave\Plugins\NotificationComposer\FilamentPlugin::active()
       : []),
   ```

   If the panel has no `plugins()` call, add one containing that expression to its chain. The class check also allows safely removing the plugin folder. Keep the adapter scoped to the admin panel. `active()` reads stock Wave’s installed-plugin file; it does not require the proposed PluginManager `isActive()` API.
3. After each Notifications menu label (including mobile), add:

   ```blade
   @includeIf('notification-composer::badge')
   ```

4. Add Tailwind sources for the plugin to your active app theme and admin stylesheet. Paths are relative to each stylesheet. For Anchor’s app CSS:

   ```css
   @source "../../../../../resources/plugins/notification-composer/**/*.php";
   @source "../../../../../resources/plugins/notification-composer/**/*.blade.php";
   ```

   For `resources/css/filament/admin/theme.css` use `../../../plugins/notification-composer/**/*.php` and `../../../plugins/notification-composer/**/*.blade.php`. Preserve the theme’s existing typography plugin. Rebuild with your normal `npm run build`.
5. Activate **Notification Composer** in Admin → Plugins, then run:

   ```sh
   php artisan optimize:clear
   php artisan filament:clear-cached-components
   php artisan migrate
   php artisan queue:restart
   ```

   Keep a worker running for your default queue (or use the synchronous queue for local testing). If your deployment caches routes/config/components, regenerate those caches after activation. Stock Wave may cache its installed-plugin list; clearing caches after changing activation is required.

The admin composer is `/admin/notifications` with Wave’s default panel path. The plugin takes over `/notifications` and the standard read endpoint while active, retaining existing notifications alongside announcements. Authentication and email verification are required. These routes intentionally replace Wave’s default inbox; check for conflicts with other inbox plugins.

## Configuration

Copy `config/notification-composer.php` to the application’s `config/notification-composer.php` to override defaults. Team identity can also use `NOTIFICATION_TEAM_NAME` and `NOTIFICATION_TEAM_AVATAR`. Supply an accessible avatar URL/path. Labels default to Security, Marketing and Product update; blog notifications remain the responsibility of blog publishing. Label keys must be at most 32 characters. Add any custom label classes to your Tailwind build sources or safelist.

`layout` defaults to `theme::components.layouts.app`. Other compatible layouts can be selected without copying the message renderer into a theme. The plugin sanitizes rich text, escapes titles, and includes a plain-text `body` fallback for other consumers. Its own renderer uses `rich_body`.

## Delivery and unread counts

Delivery targets users existing when the announcement was created, using the configured auth user model and its scopes. Batches contain 200 users. Stable per-user delivery IDs make queue retries idempotent, and announcement read receipts are retained so retries cannot resurrect dismissed announcements. Existing non-announcement notifications keep Wave’s dismissal behavior. Monitor and retry failed jobs through your application’s normal queue tools.

Desktop/mobile badges share one count query per user/request. Alpine updates counts after marking a message read. There is **no periodic polling**; incoming announcements appear on the next navigation or refresh.

The activity entry contains the acting user ID, broadcast ID and category, without message contents. Disabling Wave activity logging also disables this entry.

## Deactivation and removal

Finish queued deliveries before deactivation or removal. Deactivation restores Wave’s inbox after the caches above are cleared; retained announcements have readable plain-text bodies there. Data and read receipts are not deleted. Removing the folder while jobs are queued prevents those jobs from loading. Back up data before deliberately dropping the `notification_broadcasts` table; no destructive uninstall runs automatically.

## Development and release

Run `python3 scripts/build-release.py` to create `dist/notification-composer-1.0.0.zip` with the installable Wave directory structure. The archive excludes tests, tooling and repository metadata. Version metadata lives in `version.json`.

The feature tests run in a Wave application rather than bootstrapping a second framework. Install/activate the plugin and adapter as above, copy `tests/NotificationComposerTest.php` into the host’s `tests/Feature/`, then run `php artisan test tests/Feature/NotificationComposerTest.php` using the host’s isolated test database. Never run development tests against application or production data.

MIT licensed; see LICENSE.md.
