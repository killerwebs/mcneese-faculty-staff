# McNeese Faculty Staff

A lightweight, dependency-free WordPress plugin for managing a faculty/staff directory and displaying it anywhere with shortcodes. Modeled on a liberal-arts directory: a filterable general list, individual bio pages, and per-department embeds.

- **Author:** [Killerwebsites.com](https://killerwebsites.com)
- **Version:** 1.2.0
- **Requires:** WordPress 5.8+, PHP 7.4+

## Features

- **Custom post type** "Faculty & Staff" with a **Departments** taxonomy. Field/column keys deliberately match the WordPress export format so CSV exports re-import cleanly.
- **Per-person fields:** Title/Position, Email, Phone, Office Location, Website, Photo — edited on a clean single-form screen with the fields grouped into labeled sections under the bio (no ACF dependency).
- **Three shortcodes** with rich presentation options (layouts, contact display, theming, grouping, A–Z index).
- **Individual bio pages** via theme-overridable templates.
- **Batched CSV importer** with a live progress bar — resumable, and matches by slug so re-running updates instead of duplicating.
- **Admin list columns** showing Position / Email / Phone / Departments at a glance.

## Installation

1. Download `mcneese-faculty-staff.zip` (or `faculty-staff-x.y.z.zip`) from the [Releases](https://github.com/killerwebs/mcneese-faculty-staff/releases) page.
2. In WordPress: **Plugins → Add New → Upload Plugin**, choose the zip, install, and activate.
3. Add people under **Faculty & Staff**, or bulk-import via **Faculty & Staff → Import CSV**.

## Shortcodes

### `[faculty_directory]`

Filterable, searchable grid of everyone.

```
[faculty_directory]
[faculty_directory layout="list" show_email="true" show_phone="true" show_location="true"]
[faculty_directory groupby="department" index="true" photo_shape="circle"]
```

| Attribute | Default | Description |
|---|---|---|
| `layout` | `grid` | `grid`, `list`, `compact`, or `names` |
| `columns` | `3` | Items per row (desktop) |
| `columns_md` | auto | Items per row at ≤900px |
| `columns_sm` | `1` | Items per row at ≤600px |
| `photo_shape` | `square` | `square`, `circle`, or `portrait` |
| `accent` | theme | Hex color to re-skin this instance (e.g. `#8a0050`) |
| `department` | — | Scope to department slug(s)/name(s), comma-separated |
| `orderby` | `title` | `title`, `menu_order`, `date`, `rand` |
| `order` | `ASC` | `ASC` or `DESC` |
| `filter` | `true` | Show the department filter pills |
| `search` | `true` | Show the live search box |
| `groupby` | — | `department` to render a heading per department |
| `index` | `false` | Show an A–Z jump bar |
| `show_title` | `true` | Position under the name |
| `show_dept` | `false` | Department label on each card |
| `show_email` / `show_phone` / `show_location` / `show_website` | `false` | Contact rows on each card |
| `number` | `-1` | Max people (`-1` = all) |

### `[faculty_department dept="history"]`

A single department, no filter bar. Accepts all `[faculty_directory]` presentation attributes.

### `[faculty_member slug="jane-doe"]`

A single person card. Use `slug="..."` or `id="123"`.

## CSV import

Upload a CSV exported from WordPress (or any CSV with matching headers). Recognized columns:

`Title`, `Content`, `Excerpt`, `Slug`, `Status`, `Order`, `Departments` (pipe- or comma-separated for multiple), `Image URL`, `fs_title`, `fs_email`, `fs_phone`, `fs_location`, `fs_website`.

People are matched by `Slug` (falling back to `Title`), so re-importing updates existing records instead of creating duplicates. Photos can be downloaded into the Media Library and set as the featured image.

## Theming

All front-end colors flow through CSS custom properties (`--fs-accent`, `--fs-text`, `--fs-muted`, `--fs-border`, …), so a theme can re-skin the directory without overriding markup. Bio and archive templates can be overridden by copying `templates/single-faculty_staff.php` / `templates/archive-faculty_staff.php` into your theme.

## License

GPL-2.0-or-later.
