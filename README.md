# Simple Media Categories

Registers a single hierarchical `media_category` taxonomy on the WordPress attachment post type. Adds category filtering to both the media library list view and grid view, and a checkbox assignment panel in the attachment details sidebar.

## Features

- Hierarchical taxonomy with admin UI (`show_ui => true`)
- List view: filter dropdown above the media table
- Grid view: toolbar filter dropdown (extends `wp.media.view.AttachmentsBrowser`)
- Grid view / media modal: checkbox panel in the attachment details sidebar
- Block editor media modal: toolbar filter (same JS, no extra code needed)
- Attachment edit screen: standard taxonomy metabox (no extra code needed)
- REST API support (`show_in_rest => true`)

## Requirements

- WordPress 6.3+
- PHP 7.4+
- Node.js / npm (for building JS assets)

## Development

```bash
npm install
npm run build
```

## Out of Scope (v1)

- Front-end filtering or display
- Shortcodes or blocks
- Settings or options UI
- Bulk edit support
- Import/export of category assignments
