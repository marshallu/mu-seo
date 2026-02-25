# MU SEO — Work in Progress

## Completed

### v1.0 — Initial Plugin
- Plugin bootstrap (`mu-seo.php`)
- Core singleton class (`class-mu-seo.php`)
- ACF field group for SEO fields on all public post types (`class-mu-seo-fields.php`)
  - SEO Title, Meta Description, Canonical URL
- Head output class (`class-mu-seo-head.php`)
  - Filters `pre_get_document_title` for custom title
  - Outputs `<meta name="description">` and `<link rel="canonical">`

---

## In Progress

### Open Graph / Twitter Card Tags
Adding social meta tag support to the MU SEO plugin.

**Files created / modified:**

| File | Status |
|---|---|
| `includes/class-mu-seo-fields.php` | Modified — added Social tab with og_image, og_type, twitter_card fields |
| `includes/class-mu-seo-options.php` | New — ACF options sub-page (Twitter handle, default OG image) |
| `includes/class-mu-seo-social.php` | New — outputs og: and twitter: tags with image fallback chain |
| `includes/class-mu-seo.php` | Modified — loads and instantiates the two new classes |

**Image fallback chain (in priority order):**
1. `mu_seo_og_image` ACF field on the post
2. Featured image (`get_post_thumbnail_id()`)
3. Hero block image parsed from `acf/hero` block in post content
4. Site default from options page (`mu_seo_default_og_image`)

**Verification checklist:**
- [ ] Edit any post → "SEO" and "Social / Open Graph" tabs appear in the field group
- [ ] Set an OG image on a post, view source → `og:image` tag present with correct URL
- [ ] Leave OG image blank, set featured image → fallback to featured image works
- [ ] Leave both blank on a page with `acf/hero` block → hero image is used
- [ ] Check Settings → SEO Settings → options page renders
- [ ] Set Twitter handle → `twitter:site` appears in source
- [ ] No tags output on archive / home / 404 pages
