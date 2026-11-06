# Simple Media Categories

[![CI](https://github.com/philhoyt/simple-media-categories/actions/workflows/ci.yml/badge.svg)](https://github.com/philhoyt/simple-media-categories/actions/workflows/ci.yml)

Adds a hierarchical `media_category` taxonomy to the media library, with filters in the list and grid views, bulk tagging, and auto-tagging on upload.

Everything lives in wp-admin. There is no front-end output, no shortcode, and no block.

## Requirements

- WordPress 6.3 or newer
- PHP 7.4 or newer
- For development: Node.js (CI uses 24), Composer, and Docker for wp-env

## Installation

1. Download the zip from the latest [GitHub release](https://github.com/philhoyt/simple-media-categories/releases), or build from source (see Development).
2. Upload it on Plugins > Add New > Upload Plugin, or copy the folder to `wp-content/plugins/`.
3. Activate it.

Updates are delivered from GitHub releases through the bundled Plugin Update Checker.

## Usage

The taxonomy appears in the places WordPress already provides for attachment taxonomies, plus a few the plugin adds:

| Screen | What you get |
|---|---|
| Media > Categories | Core taxonomy screen: add, rename, delete, nest. |
| Media library, list view | Category dropdown above the table (`restrict_manage_posts`), and an Edit Categories bulk action that opens a form for the selected files. |
| Media library, grid view and media modal | Category dropdown in the toolbar (extends `wp.media.view.AttachmentsBrowser`) and a checkbox list in the attachment details sidebar. |
| Block editor media modal | The same toolbar dropdown; no extra code. |
| Attachment edit screen | Core taxonomy meta box with Add New Category. |
| Media > Tag Media | React screen for bulk tagging (below). |
| Media > Settings | Auto-tagging switches and the Tag existing media now tool. |

### Tag Media

Browse attachments in a grid, filter by category, untagged, search, or file type, select several, and add, remove, or replace their categories. The category input autocompletes on the term's path (`Parent / Child`) and creates a term inline if none matches. You can also drag the selection onto a category in the sidebar.

### Auto-tagging

Two rules run on `add_attachment` when enabled in Media > Settings:

- By attached post: a term named after the parent's post type (for example `Page`) and a child term for the specific post. Only public post types qualify.
- By file type: `Images`, `Documents`, `Audio`, `Video`, or `Other`, nested under a `File Type` parent.

Tag existing media now runs the enabled rules over the existing library in batches with a progress bar.

### Uninstall

Deleting the plugin removes the `smc_settings` option on each site (multisite included). Terms and assignments are kept so a reinstall picks them up again.

## REST API

The taxonomy is registered with `show_in_rest`, so core's `/wp/v2/media_category` and the `media_category` parameter on `/wp/v2/media` work as usual. The Tag Media and Settings screens use a namespace of their own.

Namespace: `simple-media-categories/v1`

| Method | Route | Capability | Purpose |
|--------|-------|------------|---------|
| GET | `/terms` | `upload_files` | List the term tree with counts and display paths |
| POST | `/terms` | `manage_categories` | Create a term |
| GET | `/media` | `upload_files` | List attachments (filters: term, untagged, search, mime) |
| POST | `/media/bulk` | `upload_files`, plus `edit_post` per item | Add, remove, or set terms across attachments |
| POST | `/maintenance/retag` | `manage_options` | Apply auto-tagging rules to a batch of existing media |

Settings are read and written through core's `/wp/v2/settings` endpoint.

Hooks: filters `smc_rest_media_query_args`, `smc_rest_term_response`, `smc_auto_tag_enabled`, `smc_mime_group`, `smc_mime_groups`; action `smc_media_terms_updated`.

## Development

```bash
npm install
composer install        # also copies Plugin Update Checker into lib/

npm start               # wp-scripts watch
npm run build           # production build to build/

npm run lint:js
npm run lint:css
composer run lint       # phpcs (npm run lint:php does the same)
composer run lint:fix   # phpcbf
composer run analyse    # phpstan

npm run test:js         # Jest
npm run env:start       # wp-env on PHP 8.2 with the plugin installed
npm run test:php        # PHPUnit in the wp-env tests container
npm run env:stop

npm run pot             # regenerate languages/simple-media-categories.pot
npm run make-json       # JSON translation files for the JS bundles
npm run plugin-zip      # build, then zip the paths listed in package.json "files"
```

CI (`.github/workflows/ci.yml`) runs phpcs and phpstan on PHP 7.4, 8.1, 8.2, and 8.3; lint, build, and Jest on Node 24; and PHPUnit through wp-env.

Source layout: `includes/` holds the PHP classes (taxonomy, REST controller, auto-tagger, settings, admin pages, walkers); `src/admin/` is the Tag Media app; `src/settings/` is the Settings app; `assets/css/admin.css` styles the core media screens.

## Releases

Bump `Version` and `SMC_VERSION` in `simple-media-categories.php`, `version` in `package.json`, and `Stable tag` in `readme.txt`, add a changelog entry under `== Changelog ==`, commit, then push a `v`-prefixed tag:

```bash
git tag v1.3.2 && git push origin v1.3.2
```

The release workflow (`.github/workflows/release.yml`) runs `npm ci`, `npm run build`, and `npm run plugin-zip`, pulls the matching `= X.Y.Z =` block out of `readme.txt` for the release notes, and attaches `simple-media-categories.zip` and `readme.txt` to the GitHub release. Plugin Update Checker serves that zip to installed sites. The checker filters release assets to `.zip` names and requires one: without the filter it takes the first asset on the release, which GitHub lists by name, so sites would download `readme.txt` and fail with `PCLZIP_ERR_BAD_FORMAT`.

## Limitations

- No front-end filtering or display, shortcodes, or blocks.
- No category colours or other term metadata.
- Renaming, merging, and deleting terms happens on the core Media > Categories screen, not in Tag Media.
- No import or export of category assignments.
- Auto-tagging by attached post only fires for uploads attached to a public post type; files uploaded from the Media screen have no parent and get no post term.
