=== McNeese Faculty Staff ===
Contributors: killerwebsites
Plugin URI: https://killerwebsites.com
Tags: faculty, staff, directory, shortcode, csv import, departments
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage faculty & staff and display them anywhere with shortcodes. Filterable directory, per-department lists, individual bio pages, and a CSV importer.

== Description ==

A lightweight, dependency-free plugin for showing a faculty/staff directory across department pages.

* **Custom post type** "Faculty & Staff" with a **Departments** taxonomy.
* Per-person fields: Title/Position, Email, Phone, Office Location, Website, Photo.
* **Shortcodes** to drop the directory anywhere.
* Individual **bio pages** generated automatically (theme-overridable templates).
* **CSV importer** built for the WordPress export format (re-import updates instead of duplicating).

Meta keys (`fs_title`, `fs_email`, `fs_phone`, `fs_location`) and the post type
(`faculty_staff`) / taxonomy (`departments`) match a standard WP faculty export,
so existing data imports cleanly.

== Shortcodes ==

`[faculty_directory]`
Full directory grid with a department filter bar and live search.
Attributes:
* `department` — slug(s) or name(s), comma-separated, to pre-scope the list.
* `columns` — number of columns (default 3).
* `orderby` — title | menu_order | date | rand (default title).
* `order` — ASC | DESC.
* `filter` — true|false, show the department pills (default true).
* `search` — true|false, show the search box (default true).
* `show_title` — true|false, show position under the name (default true).
* `show_dept` — true|false, show department label on each card (default false).
* `number` — max people, -1 for all (default -1).

`[faculty_department dept="history"]`
A single department with no filter bar — ideal to embed on a department page.
Accepts a slug or a name.

`[faculty_member slug="michael-smith"]` (or `id="123"`)
A single person card.

== Importing a CSV ==

1. Go to **Faculty & Staff → Import CSV**.
2. Choose your `.csv` and (optionally) tick "Download photos into the Media Library".
3. Import. A progress bar processes the rows in small batches (photos download in
   the background), so large imports never sit on a frozen screen.

People are matched by Slug (then Title), so re-importing updates them instead of
duplicating. If a batch fails (e.g. a network blip), just re-run the import:
already-imported people are matched and updated, and photos already downloaded
are skipped.

Recognized columns: Title, Content, Excerpt, Slug, Status, Order, Departments
(pipe-separated for multiple), Image URL, fs_title, fs_email, fs_phone,
fs_location, fs_website.

== Changelog ==

= 1.0.0 =
* Initial release.
