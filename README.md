# Simple Media Categories

[![CI](https://github.com/philhoyt/simple-media-categories/actions/workflows/ci.yml/badge.svg)](https://github.com/philhoyt/simple-media-categories/actions/workflows/ci.yml)

Registers a single hierarchical `media_category` taxonomy on the WordPress attachment post type. Adds category filtering to both the media library list view and grid view, and a checkbox assignment panel in the attachment details sidebar.

## Features

- Hierarchical taxonomy with admin UI (`show_ui => true`)
- List view: filter dropdown above the media table
- Grid view: toolbar filter dropdown (extends `wp.media.view.AttachmentsBrowser`)
- Grid view / media modal: checkbox panel in the attachment details sidebar
- Block editor media modal: toolbar filter (same JS, no extra code needed)
- Attachment edit screen: standard taxonomy metabox (no extra code needed)
- **Tag Media workspace** (Media > Tag Media): a React admin page to browse, search, filter, multi-select, and bulk-tag attachments — with path-aware autocomplete, inline category creation, and drag-and-drop tagging
- REST API support (`show_in_rest => true`), plus a custom `simple-media-categories/v1` namespace powering the Tag Media workspace

## Requirements

- WordPress 6.3+
- PHP 7.4+
- Node.js / npm (for building JS assets)

## Development

```bash
npm install
npm run build
```

## Out of Scope

- Front-end filtering or display
- Shortcodes or blocks
- Settings or options UI
- Category colors / term metadata
- Term management (rename/merge/delete) beyond creation — use the core Categories screen
- Import/export of category assignments
