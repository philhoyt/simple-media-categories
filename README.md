# Simple Media Categories

[![CI](https://github.com/philhoyt/simple-media-categories/actions/workflows/ci.yml/badge.svg)](https://github.com/philhoyt/simple-media-categories/actions/workflows/ci.yml)

Registers a single hierarchical `media_category` taxonomy on the WordPress attachment post type, with filtering and bulk tagging tools in the media library admin. No front-end output.

## Features

- Hierarchical taxonomy with admin UI (`show_ui => true`)
- List view: filter dropdown above the media table
- Grid view: toolbar filter dropdown (extends `wp.media.view.AttachmentsBrowser`)
- Grid view / media modal: checkbox panel in the attachment details sidebar
- Block editor media modal: toolbar filter (same JS, no extra code needed)
- Attachment edit screen: standard taxonomy metabox (no extra code needed)
- Tag Media workspace (Media > Tag Media): a React admin page to browse, search, filter, multi-select, and bulk-tag attachments, with path-aware autocomplete, inline category creation, and drag-and-drop tagging
- Auto-tagging on upload (Media > Settings): tag by attached post and/or by file type (Images, Documents, Audio, Video, Other under a `File Type` parent)
- "Tag existing media now" tool that applies the enabled rules to a library you already have
- Bulk "Edit Categories" action on the media list view
- REST API support (`show_in_rest => true`) plus a custom `simple-media-categories/v1` namespace

## Requirements

- WordPress 6.3 or later
- PHP 7.4 or later
- Node.js 18+ and Composer (for building assets and running the dev tooling)

## Installation

1. Download a release zip, or build from source (see Development).
2. Copy the plugin folder to `wp-content/plugins/`.
3. Activate it through the Plugins screen.

Categories appear under Media > Categories. Bulk tagging lives at Media > Tag Media, and auto-tagging options at Media > Settings.

## Usage

- **Tag Media** (Media > Tag Media): select attachments and add, remove, or replace categories in bulk; type to autocomplete (or create) a category; drag a selection onto a category in the sidebar to tag it.
- **Settings** (Media > Settings): turn auto-tagging by attached post and by file type on or off, and run "Tag existing media now" to catch up an existing library.

## REST API

Namespace: `simple-media-categories/v1`

| Method | Route | Capability | Purpose |
|--------|-------|------------|---------|
| GET | `/terms` | `upload_files` | List the term tree with counts and display paths |
| POST | `/terms` | `manage_categories` | Create a term |
| GET | `/media` | `upload_files` | List attachments (filters: term, untagged, search, mime) |
| POST | `/media/bulk` | `upload_files` (+ per-item `edit_post`) | Add, remove, or set terms across attachments |
| POST | `/maintenance/retag` | `manage_options` | Apply auto-tagging rules to a batch of existing media |

Settings are read and written through core's `/wp/v2/settings` endpoint.

Filters for extension: `smc_rest_media_query_args`, `smc_rest_term_response`, `smc_auto_tag_enabled`, `smc_mime_group`, `smc_mime_groups`. Action: `smc_media_terms_updated`.

## Development

```bash
npm install            # JS dependencies
composer install       # PHP dev tools (phpcs, phpunit, phpstan)

npm start              # webpack watch (development build)
npm run build          # production build

npm run lint:js        # JavaScript linting
npm run lint:css       # SCSS/CSS linting
composer run lint      # PHP coding standards (phpcs)
composer run analyse   # PHP static analysis (phpstan)

npm run test:js        # Jest unit tests
npm run env:start      # start the wp-env Docker environment
npm run test:php       # PHPUnit via wp-env
```

## Releases

Distribution is GitHub-based via Plugin Update Checker. To cut a release:

1. Bump `Version` and `SMC_VERSION` in `simple-media-categories.php` and `Stable tag` in `readme.txt`, and add a changelog entry.
2. Run `npm run plugin-zip` to build assets and package the distributable zip (the `files` whitelist in `package.json` controls its contents).
3. Create a GitHub release and attach the zip.

There is no automated release workflow yet; CI (`.github/workflows/ci.yml`) runs lint, static analysis, and tests on pull requests and pushes to `main`.

## Out of Scope

- Front-end filtering or display
- Shortcodes or blocks
- Category colors / term metadata
- Term management (rename, merge, delete) beyond creation -- use the core Categories screen
- Import/export of category assignments
