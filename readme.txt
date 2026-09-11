=== McNeese Faculty Staff ===
Contributors: killerwebsites
Plugin URI: https://killerwebsites.com
Tags: faculty, staff, directory, shortcode, csv import, departments
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.3.2
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
* `orderby` — last_name | title | menu_order | date | rand (default last_name).
* `order` — ASC | DESC.
* `filter` — true|false, show the department dropdown (default true).
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

= 1.3.2 =
* Fixed: grouped directories (groupby="department") showed only the first department after the pagination refactor; every group is visible again and search/index cover all of them.
* Fixed: [faculty_member] no longer renders draft, pending, or private people on the public site.
* Fixed: placeholder initials ignore honorifics and credentials ("Dr. Jane Doe, PhD" shows JD, not DP).
* Fixed: search now matches accented names typed in lowercase (multibyte-safe lowercasing).
* Fixed: with orderby="menu_order"/"date"/"rand" the sort dropdown offers a "Default order" option that restores the rendered order instead of claiming A-Z.
* Fixed: number="N" with the last_name order now picks the first N by surname, not by title.
* Fixed: an empty toolbar no longer renders as a blank gray bar; no empty pager is emitted for grouped directories (and the builder disables Per page when Group by is on).
* Fixed: the shortcode builder strips quotes/brackets from free-text values and lists every person instead of the first 200.
* Performance: featured-image lookups are primed in one query instead of two per card.
* Added client-side pagination (per_page attribute) so long directories page N at a time, in both grid and list view; the /faculty/ archive now paginates at 12 per page. Search/filter/sort re-paginate live and off-page photos stay unloaded.
* Titles (card names, group/archive/bio headings) are pinned to #1c3654 so the theme can no longer render some of them black.
* Directory archive (/faculty/) now shows richer cards (department badge, contact info, View Profile button) and the full program-finder toolbar (search + department + sort + view toggle). Customizable via the new fs_archive_atts filter.
* Shortcodes admin page: added an interactive shortcode builder — pick a type and options and copy the generated shortcode (only non-default options are included).
* Sorting and the A–Z index now go by last name (ignoring "Dr." and ", PhD"), and last_name is the default order — previously "Dr." names all clumped under D.
* Bio page "Back" link now returns to the page the visitor came from (the department list they clicked through from) instead of always the full directory; falls back to the directory archive on direct visits.
* New dept_badge attribute: show the department as a pill overlay on the photo, matching the program cards.
* Fixed the card button text rendering in the theme's link color instead of white, and enlarged + top-aligned the photo in list view.
* Edit screen: people now use a clean single-form editor (classic editor) with the detail fields grouped into Position, Contact, and Links & Photo sections directly under the bio.
* Native theming: directory cards and bio pages inherit the host site's design tokens (colors, fonts, radius) when present, falling back to neutral defaults. Card titles use the site heading font/color, with a gold-accented contact card on bio pages.
* New shortcode attributes: show_excerpt (short bio on cards) and button (a "View Profile" call-to-action link).
* New admin reference page (Faculty & Staff → Shortcodes): copy-paste examples, full attribute tables, and a live list of your departments with a copy-ready shortcode for each.
* Departments screen: added a Shortcode column with a one-click copy button for each department's [faculty_department] shortcode.
* show_dept now renders a labeled "Department(s):" line on cards.
* Toolbar controls: optional Grid/List view toggle (view_toggle) and A–Z / Z–A sort (sort).
* New show_contact shorthand to show email, phone, office, and website on every card at once.
* Card button restyled to match the McNeese program-card button (color, 6px radius, letter-spacing).
* Bio + directory archive now render in a centered, padded container so they format correctly on blank/zero themes (e.g. Breakdance) that supply no content wrapper.
* New fs_use_plugin_templates filter to hand single/archive templating to a page builder or theme instead.
* Toolbar restyled to match the site's program-finder bar: a rounded container with an icon search field, department and sort dropdowns, and a grid/list icon view toggle.

= 1.1.0 =
* Shortcode presentation options: layouts (grid/list/compact/names), photo_shape, accent color, responsive columns, groupby="department", and an A-Z index.
* Per-card contact toggles: show_email, show_phone, show_location, show_website.
* Admin list columns for Position / Email / Phone.

= 1.0.0 =
* Initial release.
