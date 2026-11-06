=== Simple Media Categories ===
Contributors: philhoyt
Tags: media, categories, taxonomy, attachments
Requires at least: 6.3
Tested up to: 7.1
Stable tag: 1.3.2
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Adds a hierarchical category taxonomy to the media library, with filters in the list and grid views, bulk tagging, and auto-tagging on upload

== Description ==

Simple Media Categories registers one hierarchical taxonomy, `media_category`, on attachments. It has no front-end output; everything happens in the media library admin.

Where the categories show up:

* Media > Categories: the standard taxonomy screen for adding, renaming, and deleting terms.
* Media library list view: a category dropdown above the table, and an Edit Categories bulk action that opens a form for the selected files.
* Media library grid view and the media modal: a category dropdown in the toolbar, and a checkbox list in the attachment details sidebar.
* Block editor media modal: the same toolbar dropdown.
* Attachment edit screen: the usual taxonomy meta box with Add New Category.

Media > Tag Media is a separate screen for tagging in bulk. It lists attachments in a grid, filters by category, untagged, search, or file type, and selects several files at once to add, remove, or replace their categories. Type to autocomplete a category by its path (or create one on the spot), or drag the selection onto a category in the sidebar.

Media > Settings turns auto-tagging on or off. Two rules run when a file is uploaded:

* By attached post: the file gets a term named after the post type (Page, Post, and so on) and a child term for the specific post. Only public post types count.
* By file type: the file gets Images, Documents, Audio, Video, or Other under a File Type parent category.

A Tag existing media now button on the same screen applies the enabled rules to files already in the library, with a progress bar.

The taxonomy is available in the REST API, and the Tag Media screen uses its own `simple-media-categories/v1` namespace.

Deleting the plugin removes its settings option. The categories and their assignments are left in place, so they survive a reinstall.

== Installation ==

1. Upload the plugin folder to `/wp-content/plugins/`, or install the zip from the latest GitHub release.
2. Activate the plugin on the Plugins screen.
3. Add categories under Media > Categories, or turn on auto-tagging under Media > Settings.

Updates come from GitHub releases through the bundled Plugin Update Checker.

== Frequently Asked Questions ==

= Does this show categories on the front end? =

No. There are no templates, shortcodes, or blocks. The taxonomy is for organising the library in wp-admin, though `media_category` is queryable through the REST API and `WP_Query` if your theme wants to use it.

= What happens to my categories if I delete the plugin? =

The terms and assignments stay in the database. Only the plugin's own settings option is removed.

== Changelog ==

= 1.3.2 =
* Fixed dashboard updates failing with "PCLZIP_ERR_BAD_FORMAT" because the plugin downloaded the release's readme.txt instead of its zip. Sites on 1.3.1 or older need to install this version once by uploading the zip; later updates work from the dashboard.

= 1.3.1 =
* Fixed update checks failing with a missing class: the update checker's bundled Markdown and readme parsers now ship with the plugin.

= 1.3.0 =
* Added an uninstall routine that removes the plugin's stored option.
* Tested up to WordPress 7.1.

= 1.2.0 =
* Added a Settings page (Media > Settings) to turn auto-tagging on or off.
* Added file-type auto-tagging: Images, Documents, Audio, Video, or Other under a File Type category.
* Added a Tag existing media now tool that applies the enabled rules to an existing library, with a progress bar.
* Added the smc_auto_tag_enabled, smc_mime_group, and smc_mime_groups filters.
* Added updates from GitHub releases through Plugin Update Checker.

= 1.1.0 =
* Added the Tag Media screen under Media: a React page to browse, search, filter, multi-select, and bulk-tag attachments.
* Added a path-aware category autocomplete with inline term creation, and drag-and-drop tagging onto the category sidebar.
* Added the simple-media-categories/v1 REST namespace for listing and creating terms, listing filtered media, and bulk term assignment.

= 1.0.0 =
* Initial release.
