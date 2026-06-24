=== Simple Media Categories ===
Contributors: philhoyt
Tags: media, categories, taxonomy, attachments
Requires at least: 6.3
Tested up to: 7.0
Stable tag: 1.2.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a hierarchical category taxonomy to the WordPress media library with filtering in both list and grid views.

== Description ==

Registers a single hierarchical `media_category` taxonomy on the attachment post type with no front-end output and no settings page.

**Features**

* Hierarchical taxonomy with full admin UI
* List view: filter dropdown above the media table
* Grid view: toolbar filter dropdown
* Media modal: checkbox panel in the attachment details sidebar
* Block editor media modal: toolbar filter (no extra code)
* Attachment edit screen: standard taxonomy meta box with Add New Category support
* Auto-tagging on upload, configurable in Media > Settings: by attached post (post-type + post-specific terms) and/or by file type (Images, Documents, Audio, Video, Other under a File Type category)
* Settings page with a "Tag existing media now" tool to retroactively tag a library you already have
* Bulk "Edit Categories" action on the media list view
* Tag Media workspace (Media > Tag Media): a dedicated screen to browse, filter, multi-select, and tag media in bulk, with autocomplete, inline category creation, and drag-and-drop tagging
* REST API support, including a custom simple-media-categories/v1 namespace for the Tag Media workspace

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Categories will appear under Media > Categories.

== Changelog ==

= 1.2.0 =
* Add a Settings page (Media > Settings) to turn auto-tagging on or off.
* Add file-type auto-tagging: tag media by broad type (Images, Documents, Audio, Video, Other) under a File Type category.
* Add a "Tag existing media now" tool to retroactively apply the enabled rules across an existing library, with a progress bar.
* Add the smc_auto_tag_enabled, smc_mime_group, and smc_mime_groups filters for customization.

= 1.1.0 =
* Add the Tag Media workspace under Media: a React admin page to browse, search, filter, multi-select, and bulk-tag attachments.
* Add a path-aware category autocomplete with inline term creation, and drag-and-drop tagging onto the category sidebar.
* Add a custom REST API (simple-media-categories/v1) for term listing/creation, filtered media listing, and bulk term assignment.

= 1.0.0 =
* Initial release.
