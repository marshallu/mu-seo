# MU SEO

A lean SEO plugin for Marshall University's WordPress sites.

- **Package:** `marshallu/mu-seo`
- **Type:** WordPress plugin
- **Requires:** ACF Pro

---

## Features

- Custom SEO title and meta description per post/page
- Canonical URL override
- Robots meta tag control (noindex / nofollow) per post/page
- Open Graph and Twitter Card meta tags with a multi-step image fallback chain
- Site-wide options page for Twitter handle and default social image
- JSON-LD schema markup for posts (Article) and pages (WebPage)
- `mu_seo_schema` filter for adding or modifying schema on custom post types
- `mu_seo_og_type` filter for overriding the OG type on custom post types
- `mu_seo_og_image_id` filter for providing a social image on custom post types
- Yoast SEO migration tool (WP-CLI command + admin UI)

---

## Installation

### Manual Installation

Upload the plugin directory to `wp-content/plugins/` and activate it from the WordPress admin.

```
wp-content/
└── plugins/
    └── mu-seo/
        ├── mu-seo.php
        └── includes/
            └── ...
```

Then go to **Plugins** in the WordPress admin and activate **MU SEO**.

### Composer Installation

```bash
composer require marshallu/mu-seo
```

Composer will install the plugin to `wp-content/plugins/mu-seo/`. Activate it from the WordPress admin or via WP-CLI:

```bash
wp plugin activate mu-seo
```

Composer dependencies (PHPCS, WPCS, ACF stubs) are dev-only and not required in production.

---

## ACF Field Reference

All per-post fields appear in the **SEO** meta box on every public post type edit screen. The box has two tabs.

### SEO Tab

| Field | ACF Name | Type | Notes |
|---|---|---|---|
| SEO Title | `mu_seo_title` | Text | Overrides the `<title>` tag and og:title. Falls back to the post title. |
| Meta Description | `mu_seo_description` | Textarea | Overrides the meta description and og:description. Falls back to the post excerpt. |
| Canonical URL | `mu_seo_canonical` | URL | Overrides the canonical link and og:url. Falls back to the permalink. |
| Robots | `mu_seo_robots` | Checkbox | Check `noindex`, `nofollow`, or both to output a robots meta tag. Leave blank for default crawl behavior. |

### Social / Open Graph Tab

| Field | ACF Name | Type | Notes |
|---|---|---|---|
| Social Image | `mu_seo_og_image` | Image (returns ID) | Overrides the image used in og:image and Twitter card. See fallback chain below. |
| OG Type | `mu_seo_og_type` | Select | `article` or `website`. When left blank, defaults to `article` for posts and `website` for all other post types. Can be overridden per post type via the `mu_seo_og_type` filter. |
| Twitter Card Style | `mu_seo_twitter_card` | Select | `summary_large_image` (default) or `summary`. |

### Options Page

Located at **Settings > SEO Settings**.

| Field | ACF Name | Type | Notes |
|---|---|---|---|
| Twitter / X Handle | `mu_seo_twitter_handle` | Text | Include the `@` symbol, e.g. `@MarshallU`. Populates `twitter:site`. |
| Default Social Image | `mu_seo_default_og_image` | Image (returns ID) | Fallback image when a post has no featured image or hero image. |

---

## Social Image Fallback Chain

When resolving the image for `og:image`, `twitter:image`, and JSON-LD schema, the plugin walks this chain and uses the first match:

1. **Post-level ACF override** — `mu_seo_og_image` field on the post
2. **Featured image** — `get_post_thumbnail_id()`
3. **Hero block image** — parsed from the first `acf/hero` block in post content (see below)
4. **`mu_seo_og_image_id` filter** — lets themes/plugins provide an image for custom post types
5. **Site default** — `mu_seo_default_og_image` from the options page

If no image is found, the image tags are omitted entirely.

### Hero Block Image Extraction

The plugin parses the `acf/hero` block's saved `attrs.data` to find the image ID. The `hero_type` field determines which key is read:

| `hero_type` | Image source key |
|---|---|
| `static` | `hero_image_image` |
| `random` | `hero_images_0_image` (first row) |
| `video` / `videourl` | `video_video_thumbnail` |
| `none` / `color` | No image |

---

## Head Output

The following tags are output in `wp_head` on singular pages only. Nothing is output on archives, the home page, or 404s.

WordPress core's `rel_canonical` hook is removed — the canonical link is managed entirely by MU SEO.

### Meta Tags (`MU_SEO_Head`, priority 2)

```html
<meta name="description" content="...">
<meta name="robots" content="noindex,nofollow">   <!-- only when directives are set -->
<link rel="canonical" href="...">
```

### Open Graph and Twitter Card (`MU_SEO_Social`, priority 1)

```html
<meta property="og:type" content="article">
<meta property="og:title" content="...">
<meta property="og:description" content="...">
<meta property="og:url" content="...">
<meta property="og:site_name" content="...">
<meta property="og:image" content="...">          <!-- if image found -->
<meta property="og:image:width" content="...">
<meta property="og:image:height" content="...">
<meta property="og:image:alt" content="...">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@MarshallU">   <!-- if handle set in options -->
<meta name="twitter:title" content="...">
<meta name="twitter:description" content="...">
<meta name="twitter:image" content="...">         <!-- if image found -->
```

### JSON-LD Schema (`MU_SEO_Schema`, priority 2)

**Posts** receive `Article` schema:

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "...",
  "description": "...",
  "url": "...",
  "datePublished": "2024-01-01T00:00:00+00:00",
  "dateModified": "2024-01-01T00:00:00+00:00",
  "author": { "@type": "Person", "name": "..." },
  "publisher": { "@type": "Organization", "name": "..." },
  "image": { "@type": "ImageObject", "url": "...", "width": 1200, "height": 630 }
}
```

**Pages** receive `WebPage` schema:

```json
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "name": "...",
  "description": "...",
  "url": "...",
  "datePublished": "2024-01-01T00:00:00+00:00",
  "dateModified": "2024-01-01T00:00:00+00:00",
  "publisher": { "@type": "Organization", "name": "..." },
  "primaryImageOfPage": { "@type": "ImageObject", "url": "...", "width": 1200, "height": 630 }
}
```

---

## Developer Hooks

### `mu_seo_post_types`

Filters the list of post types that receive the SEO and Social field group. The default is all post types registered with `public => true`. Use this to add post types with a UI but no public archive, or to remove post types that should not have SEO fields.

**Parameters:**

| Parameter | Type | Description |
|---|---|---|
| `$post_types` | `string[]` | Array of post type slugs. |

**Examples:**

Add a non-public CPT:

```php
add_filter( 'mu_seo_post_types', function( $post_types ) {
    $post_types[] = 'faculty';
    $post_types[] = 'program';
    return $post_types;
} );
```

Remove a post type:

```php
add_filter( 'mu_seo_post_types', function( $post_types ) {
    return array_diff( $post_types, array( 'attachment' ) );
} );
```

---

### `mu_seo_og_image_id`

Filters the resolved social image attachment ID after the built-in fallback chain (ACF override → featured image → hero block) and before the site-wide default. Use this to provide a post-type-specific image source for CPTs that don't use featured images or the hero block.

The filter receives the ID resolved so far — return it unchanged to pass through, or return a different attachment ID to override.

**Parameters:**

| Parameter | Type | Description |
|---|---|---|
| `$image_id` | `int` | Attachment ID resolved so far, or `0` if nothing found yet. |
| `$post_id` | `int` | The current post ID. |

**Example** — use a custom ACF field as the social image for a profiles CPT:

```php
add_filter( 'mu_seo_og_image_id', function( $image_id, $post_id ) {
    if ( $image_id || ! is_singular( 'mu_profile' ) ) {
        return $image_id;
    }
    $headshot = get_field( 'profile_headshot', $post_id );
    return $headshot ? absint( $headshot ) : $image_id;
}, 10, 2 );
```

---

### `mu_seo_og_type`

Filters the default `og:type` for the current post. Runs only when the per-post ACF field is blank. Use this to assign the correct OG type to custom post types without editing MU SEO directly.

Valid OG types include `article`, `website`, and `profile`. See [ogp.me](https://ogp.me/#types) for the full list.

**Parameters:**

| Parameter | Type | Description |
|---|---|---|
| `$type` | `string` | The default type. `article` for posts, `website` for everything else. |
| `$post_id` | `int` | The current post ID. |

**Example** — set `profile` for a people/profiles CPT:

```php
add_filter( 'mu_seo_og_type', function( $type, $post_id ) {
    if ( is_singular( 'mu_profile' ) ) {
        return 'profile';
    }
    return $type;
}, 10, 2 );
```

---

### `mu_seo_schema`

Filters the JSON-LD schema array before it is encoded and output. Runs on every singular page. For unhandled post types (not `post` or `page`) the initial `$schema` value is an empty array, giving you a clean slate to build from.

**Parameters:**

| Parameter | Type | Description |
|---|---|---|
| `$schema` | `array` | The schema array. Empty for unhandled post types. |
| `$post_id` | `int` | The current post ID. |
| `$post_type` | `string` | The current post type slug. |

**Return `array`** — return an empty array to suppress output entirely.

**Examples:**

Add schema for a custom post type:

```php
add_filter( 'mu_seo_schema', function( $schema, $post_id, $post_type ) {
    if ( 'event' !== $post_type ) {
        return $schema;
    }

    return array(
        '@context'  => 'https://schema.org',
        '@type'     => 'Event',
        'name'      => get_the_title( $post_id ),
        'startDate' => get_field( 'event_start_date', $post_id ),
        'location'  => array(
            '@type' => 'Place',
            'name'  => get_field( 'event_location', $post_id ),
        ),
    );
}, 10, 3 );
```

Append a property to the default schema:

```php
add_filter( 'mu_seo_schema', function( $schema, $post_id, $post_type ) {
    if ( ! empty( $schema ) && 'post' === $post_type ) {
        $schema['articleSection'] = get_field( 'category_label', $post_id );
    }
    return $schema;
}, 10, 3 );
```

Suppress schema on a specific page:

```php
add_filter( 'mu_seo_schema', function( $schema, $post_id, $post_type ) {
    return 42 === $post_id ? array() : $schema;
}, 10, 3 );
```

---

## Yoast SEO Migration

MU SEO includes a migration tool for moving Yoast SEO post meta and global options into MU SEO's ACF fields. Existing MU SEO values are never overwritten.

### What gets migrated

**Per-post meta:**

| Yoast meta key | MU SEO field |
|---|---|
| `_yoast_wpseo_title` | `mu_seo_title` |
| `_yoast_wpseo_metadesc` | `mu_seo_description` |
| `_yoast_wpseo_canonical` | `mu_seo_canonical` |
| `_yoast_wpseo_meta-robots-noindex` / `nofollow` | `mu_seo_robots` |
| `_yoast_wpseo_opengraph-image-id` | `mu_seo_og_image` |

Values containing Yoast template variables (`%%title%%`, etc.) are skipped. If a URL-only OG image is stored, the tool attempts to resolve it to a WordPress attachment ID via `attachment_url_to_postid()`.

**Global options** (from `wpseo_social`):

| Yoast option | MU SEO field |
|---|---|
| `twitter_site` | `mu_seo_twitter_handle` (options page) |
| `og_default_image_id` | `mu_seo_default_og_image` (options page) |

### WP-CLI

The migration command requires a site ID, making it safe for multisite use.

```bash
wp mu-seo migrate-yoast <site-id> [--dry-run] [--post-type=<type>] [--per-page=<n>] [--verbose]
```

**Arguments:**

| Argument | Description |
|---|---|
| `<site-id>` | **Required.** Numeric ID of the site to migrate. |

**Options:**

| Option | Description |
|---|---|
| `--dry-run` | Preview changes without writing anything. |
| `--post-type=<type>` | Comma-separated list of post types to migrate. Defaults to all public post types. |
| `--per-page=<n>` | Batch size for post queries. Default: `100`. |
| `--verbose` | Print a line for every field action (migrated, conflict, skipped). |

**Examples:**

```bash
# Dry run on site 2
wp mu-seo migrate-yoast 2 --dry-run

# Migrate only posts and pages on site 5
wp mu-seo migrate-yoast 5 --post-type=post,page

# Full migration with per-field output
wp mu-seo migrate-yoast 3 --verbose
```

**Verbose output example:**

```
Post 42:
  mu_seo_title:                  migrated → My Page Title
  mu_seo_description:            skipped (conflict)
  mu_seo_canonical:              skipped (empty or variable)
  mu_seo_robots:                 migrated → noindex
  mu_seo_og_image:               migrated → attachment 187
Options:
  mu_seo_twitter_handle:         migrated → @MarshallU
  mu_seo_default_og_image:       skipped (empty)
Success: Done. Posts: 3 migrated, 1 skipped conflicts, ...
```

### Admin UI

The migration tool is also available at **Tools > MU SEO Migration**. It runs the same migration logic without any options — all public post types, no dry run. Results are shown on the same page after completion.

---

## Development

```bash
# Install dev dependencies
composer install

# Check coding standards
./vendor/bin/phpcs --standard=WordPress .

# Auto-fix coding standards violations
./vendor/bin/phpcbf --standard=WordPress .
```

All code follows [WordPress Coding Standards](https://github.com/WordPress/WordPress-Coding-Standards). Functions, hooks, and globals are prefixed `mu_seo_`.

---

## File Structure

```
mu-seo/
├── mu-seo.php                      # Plugin entry point
├── includes/
│   ├── class-mu-seo.php            # Core singleton, bootstraps all classes
│   ├── class-mu-seo-fields.php     # ACF field group (SEO + Social tabs)
│   ├── class-mu-seo-head.php       # Outputs title, description, robots, canonical
│   ├── class-mu-seo-options.php    # ACF options page (Settings > SEO Settings)
│   ├── class-mu-seo-social.php     # Outputs OG and Twitter Card tags
│   ├── class-mu-seo-schema.php     # Outputs JSON-LD schema
│   └── class-mu-seo-migrate.php    # Yoast SEO migration (WP-CLI + admin UI)
└── composer.json
```
