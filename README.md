# MU SEO

A lean, internal SEO plugin for Marshall University's WordPress network.

- **Package:** `marshallu/mu-seo`
- **Type:** WordPress MU (must-use) plugin
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

---

## Installation

Drop the plugin directory into `wp-content/mu-plugins/`. It loads automatically as a must-use plugin — no activation step required.

```
wp-content/
└── mu-plugins/
    └── mu-seo/
        ├── mu-seo.php
        └── includes/
            └── ...
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
| OG Type | `mu_seo_og_type` | Select | `article` (default) or `website`. Use `website` for the homepage or section landing pages. |
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
4. **Site default** — `mu_seo_default_og_image` from the options page

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

### Meta Tags (`MU_SEO_Head`, priority default)

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
│   └── class-mu-seo-schema.php     # Outputs JSON-LD schema
└── composer.json
```
