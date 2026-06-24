# Knowledge Base — Simple Media Categories

A per-project lookup table for API constraints, version-specific gotchas, and findings from external docs. Not prose — one line per entry.

Format: `topic — finding — source URL`

## WordPress

- `wp_mime_type_icon()` — accepts a MIME type string **or** an attachment ID — https://developer.wordpress.org/reference/functions/wp_mime_type_icon/
- Custom term-count callback needed for non-public taxonomies — core's `_update_post_term_count()` is skipped, so counts stay 0 without `update_count_callback`.

## Block editor

- (none yet)

## Third-party libraries

- `@dnd-kit/core` — `DragOverlay` is the supported way to show a custom drag preview (vs. transforming the source node) — used for stacked multi-drag.

## Local environment

- wp-env PHPUnit runs in the `tests-cli` container; use `--env-cwd` so PHPUnit finds `phpunit.xml.dist`. PHP runtime is the `.wp-env.json` `phpVersion`, not the host.
- Composer lock must be resolved against `config.platform.php = 7.4` — a lock built on PHP 8.5 pulls `doctrine/instantiator` 2.x (PHP ^8.4) and breaks `composer install` on 7.4–8.3.
