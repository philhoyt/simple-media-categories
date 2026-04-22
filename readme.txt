=== Simple Media Categories ===
Contributors: philhoyt
Tags: media, categories, taxonomy, attachments
Requires at least: 6.3
Tested up to: 6.7
Stable tag: 1.0.0
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
* Auto-assigns a post-type term and a post-specific child term on upload when an attachment has a parent post
* Bulk "Edit Categories" action on the media list view
* REST API support

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`.
2. Activate the plugin through the Plugins screen.
3. Categories will appear under Media > Categories.

== Changelog ==

= 1.0.0 =
* Initial release.
