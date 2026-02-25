# MU SEO

A lean, internal SEO plugin for Marshall University's WordPress network.

- **Package**: `marshallu/mu-seo`
- **Type**: WordPress MU (must-use) plugin
- **Author**: Christopher McComas

## Development Commands

```bash
# Install dependencies
composer install

# Run PHP CodeSniffer (lint)
./vendor/bin/phpcs --standard=WordPress .

# Auto-fix coding standards violations
./vendor/bin/phpcbf --standard=WordPress .
```

## WordPress Coding Standards

This project uses [WordPress Coding Standards (WPCS)](https://github.com/WordPress/WordPress-Coding-Standards) enforced via PHP_CodeSniffer. All code must pass WPCS linting before being considered complete.

### Key rules to follow

- Use tabs for indentation, not spaces
- Use single quotes for strings unless interpolation is needed
- Prefix all functions, classes, hooks, and globals with `mu_seo_` to avoid namespace collisions
- Use `snake_case` for functions and variables; `PascalCase` for class names
- Always sanitize input (`sanitize_text_field()`, `absint()`, etc.) and escape output (`esc_html()`, `esc_url()`, `esc_attr()`, etc.)
- Use nonces for any form submissions or AJAX requests
- Use `wp_enqueue_scripts` / `wp_enqueue_style` — never inline scripts or styles directly
- Avoid direct database queries; use `$wpdb->prepare()` when raw SQL is necessary
- Hook into WordPress actions/filters rather than executing logic at the top level of files
- Never use `extract()`, `eval()`, or short PHP open tags (`<?`)

### File naming

- PHP files: `class-{name}.php` for class files, `{name}.php` for functional files (all lowercase, hyphen-separated)
- The main plugin file is `mu-seo.php`

## Project Structure

```
mu-seo/
├── mu-seo.php          # Plugin entry point (header, bootstrap)
├── composer.json       # Dev dependencies (PHPCS, WPCS, ACF stubs)
├── .gitignore
└── vendor/             # Composer-managed (not committed)
```

As the plugin grows, follow this structure:

```
mu-seo/
├── mu-seo.php
├── includes/
│   ├── class-mu-seo.php        # Core plugin class
│   └── class-mu-seo-{feature}.php
├── admin/                       # Admin-only code
├── assets/
│   ├── css/
│   └── js/
└── composer.json
```

## WordPress Best Practices

- **No business logic in template files** — keep display and logic separated
- **Capability checks** before any admin action (`current_user_can()`)
- **Verify nonces** on every form/AJAX handler
- **Late escaping** — escape as close to output as possible, not at input
- **Use WordPress APIs** over native PHP equivalents where one exists (e.g., `wp_remote_get()` over `file_get_contents()`)
- **Avoid hardcoded URLs** — use `home_url()`, `admin_url()`, `plugins_url()`, etc.
- **`WP_DEBUG` compatibility** — code must run cleanly with `WP_DEBUG` and `WP_DEBUG_LOG` enabled

## ACF

ACF Pro stubs (`php-stubs/acf-pro-stubs`) are included as a dev dependency. Use ACF functions freely; PHPCS will recognize them without flagging undefined function errors.
