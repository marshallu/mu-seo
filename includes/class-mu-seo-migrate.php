<?php
/**
 * Yoast SEO to MU SEO migration tools.
 *
 * Provides a WP-CLI command and a Tools admin page for migrating Yoast SEO
 * post meta and options into MU SEO's ACF fields.
 *
 * @package MU_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * MU_SEO_Migrate class.
 */
class MU_SEO_Migrate {

	/**
	 * Register the WP-CLI command if WP-CLI is available.
	 */
	public function register_cli() {
		if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
			return;
		}
		WP_CLI::add_command( 'mu-seo migrate-yoast', array( $this, 'cli_migrate_yoast' ) );
	}

	/**
	 * Register admin hooks if in the admin context.
	 */
	public function register_admin_hooks() {
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'admin_menu', array( $this, 'add_tools_page' ) );
		add_action( 'admin_post_mu_seo_migrate_yoast', array( $this, 'handle_form_submit' ) );
	}

	// -------------------------------------------------------------------------
	// WP-CLI
	// -------------------------------------------------------------------------

	/**
	 * Migrate Yoast SEO data into MU SEO ACF fields.
	 *
	 * ## ARGUMENTS
	 *
	 * <site-id>
	 * : The numeric ID of the site to migrate (required for multisite).
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Preview changes without writing anything.
	 *
	 * [--post-type=<type>]
	 * : Comma-separated list of post types to migrate (default: all public).
	 *
	 * [--per-page=<n>]
	 * : Number of posts to process per batch (default: 100).
	 *
	 * [--verbose]
	 * : Print a line for every field action (migrated, conflict, skipped).
	 *
	 * ## EXAMPLES
	 *
	 *     wp mu-seo migrate-yoast 2 --dry-run
	 *     wp mu-seo migrate-yoast 5 --post-type=post,page
	 *     wp mu-seo migrate-yoast 3 --verbose
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Associative arguments.
	 */
	public function cli_migrate_yoast( $args, $assoc_args ) {
		if ( empty( $args[0] ) ) {
			WP_CLI::error( 'Please provide a site ID as the first argument.' );
		}

		$site_id = absint( $args[0] );

		if ( ! get_site( $site_id ) ) {
			WP_CLI::error( sprintf( 'Site ID %d does not exist.', $site_id ) );
		}

		$dry_run  = isset( $assoc_args['dry-run'] );
		$verbose  = isset( $assoc_args['verbose'] );
		$per_page = isset( $assoc_args['per-page'] ) ? absint( $assoc_args['per-page'] ) : 100;

		if ( isset( $assoc_args['post-type'] ) ) {
			$post_types = array_filter( array_map( 'trim', explode( ',', $assoc_args['post-type'] ) ) );
		} else {
			$post_types = array();
		}

		if ( $dry_run ) {
			WP_CLI::line( 'Dry run — no data will be written.' );
		}

		switch_to_blog( $site_id );

		$post_stats = $this->run_posts(
			array(
				'dry_run'    => $dry_run,
				'post_types' => $post_types,
				'per_page'   => $per_page,
				'cli_output' => true,
				'verbose'    => $verbose,
			)
		);

		if ( $verbose ) {
			WP_CLI::line( 'Options:' );
		}

		$option_stats = $this->run_options( $dry_run, $verbose );

		restore_current_blog();

		WP_CLI::success(
			sprintf(
				'Done. Posts: %d migrated, %d skipped conflicts, %d skipped empty/variables. Options: %d migrated, %d skipped.',
				$post_stats['migrated'],
				$post_stats['skipped_conflict'],
				$post_stats['skipped_empty'],
				$option_stats['migrated'],
				$option_stats['skipped_empty'] + $option_stats['skipped_conflict']
			)
		);
	}

	// -------------------------------------------------------------------------
	// Admin UI
	// -------------------------------------------------------------------------

	/**
	 * Add the Tools submenu page.
	 */
	public function add_tools_page() {
		add_management_page(
			__( 'MU SEO Migration', 'mu-seo' ),
			__( 'MU SEO Migration', 'mu-seo' ),
			'manage_options',
			'mu-seo-migration',
			array( $this, 'render_tools_page' )
		);
	}

	/**
	 * Render the Tools admin page.
	 */
	public function render_tools_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'mu-seo' ) );
		}

		$migrated         = isset( $_GET['migrated'] ) ? absint( $_GET['migrated'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$skipped_conflict = isset( $_GET['skipped_conflict'] ) ? absint( $_GET['skipped_conflict'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$skipped_empty    = isset( $_GET['skipped_empty'] ) ? absint( $_GET['skipped_empty'] ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'MU SEO Migration', 'mu-seo' ); ?></h1>
			<p><?php esc_html_e( 'Migrate Yoast SEO post meta and options into MU SEO ACF fields. Existing MU SEO values will never be overwritten.', 'mu-seo' ); ?></p>

			<?php if ( null !== $migrated ) : ?>
				<div class="notice notice-success is-dismissible">
					<p>
						<?php
						printf(
							/* translators: 1: migrated count, 2: skipped conflicts count, 3: skipped empty count */
							esc_html__( 'Migration complete. Migrated: %1$d, Skipped (conflicts): %2$d, Skipped (empty/variables): %3$d.', 'mu-seo' ),
							esc_html( $migrated ),
							esc_html( $skipped_conflict ),
							esc_html( $skipped_empty )
						);
						?>
					</p>
				</div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="mu_seo_migrate_yoast">
				<?php wp_nonce_field( 'mu_seo_migrate_yoast', 'mu_seo_migrate_nonce' ); ?>
				<?php submit_button( __( 'Run Migration', 'mu-seo' ), 'primary', 'submit', false ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Handle the admin form submission.
	 */
	public function handle_form_submit() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this action.', 'mu-seo' ) );
		}

		check_admin_referer( 'mu_seo_migrate_yoast', 'mu_seo_migrate_nonce' );

		$post_stats   = $this->run_posts( array( 'dry_run' => false ) );
		$option_stats = $this->run_options( false );

		$redirect_url = add_query_arg(
			array(
				'page'             => 'mu-seo-migration',
				'migrated'         => $post_stats['migrated'] + $option_stats['migrated'],
				'skipped_conflict' => $post_stats['skipped_conflict'] + $option_stats['skipped_conflict'],
				'skipped_empty'    => $post_stats['skipped_empty'] + $option_stats['skipped_empty'],
			),
			admin_url( 'tools.php' )
		);

		wp_safe_redirect( $redirect_url );
		exit;
	}

	// -------------------------------------------------------------------------
	// Core migration methods
	// -------------------------------------------------------------------------

	/**
	 * Migrate Yoast post meta across all qualifying posts in batches.
	 *
	 * @param array $args {
	 *     Optional run arguments.
	 *
	 *     @type bool   $dry_run    Whether to skip writing. Default false.
	 *     @type array  $post_types Post types to include. Default all public.
	 *     @type int    $per_page   Batch size. Default 100.
	 *     @type bool   $cli_output Whether to emit per-post WP_CLI lines. Default false.
	 *     @type bool   $verbose    Whether to emit per-field WP_CLI lines. Default false.
	 * }
	 * @return array { migrated: int, skipped_conflict: int, skipped_empty: int }
	 */
	public function run_posts( $args = array() ) {
		$dry_run    = ! empty( $args['dry_run'] );
		$per_page   = isset( $args['per_page'] ) ? absint( $args['per_page'] ) : 100;
		$cli_output = ! empty( $args['cli_output'] );
		$verbose    = ! empty( $args['verbose'] );

		if ( ! empty( $args['post_types'] ) ) {
			$post_types = $args['post_types'];
		} else {
			$post_types = array_values(
				get_post_types( array( 'public' => true ) )
			);
		}

		$stats = array(
			'migrated'         => 0,
			'skipped_conflict' => 0,
			'skipped_empty'    => 0,
		);

		$page = 1;

		do {
			$query = new WP_Query(
				array(
					'post_type'              => $post_types,
					'post_status'            => 'any',
					'posts_per_page'         => $per_page,
					'paged'                  => $page,
					'fields'                 => 'ids',
					'no_found_rows'          => false,
					'update_post_meta_cache' => true,
					'update_post_term_cache' => false,
				)
			);

			if ( ! $query->have_posts() ) {
				break;
			}

			foreach ( $query->posts as $post_id ) {
				$post_stats = $this->migrate_post( $post_id, $dry_run );

				$stats['migrated']         += $post_stats['migrated'];
				$stats['skipped_conflict'] += $post_stats['skipped_conflict'];
				$stats['skipped_empty']    += $post_stats['skipped_empty'];

				if ( $verbose && ! empty( $post_stats['log'] ) ) {
					WP_CLI::line( sprintf( 'Post %d:', $post_id ) );
					foreach ( $post_stats['log'] as $entry ) {
						WP_CLI::line( sprintf( '  %-30s %s', $entry['field'] . ':', $entry['status'] ) );
					}
				} elseif ( $cli_output && ( $post_stats['migrated'] > 0 || $post_stats['skipped_conflict'] > 0 ) ) {
					WP_CLI::line(
						sprintf(
							'Post %d — migrated: %d, skipped conflicts: %d, skipped empty: %d',
							$post_id,
							$post_stats['migrated'],
							$post_stats['skipped_conflict'],
							$post_stats['skipped_empty']
						)
					);
				}
			}

			$max_pages = $query->max_num_pages;
			wp_cache_flush();
			++$page;

		} while ( $page <= $max_pages );

		return $stats;
	}

	/**
	 * Migrate Yoast global options (wpseo_social) into MU SEO ACF option fields.
	 *
	 * @param bool $dry_run Whether to skip writing.
	 * @param bool $verbose Whether to emit per-field WP_CLI lines.
	 * @return array { migrated: int, skipped_conflict: int, skipped_empty: int }
	 */
	public function run_options( $dry_run, $verbose = false ) {
		$stats = array(
			'migrated'         => 0,
			'skipped_conflict' => 0,
			'skipped_empty'    => 0,
		);

		$wpseo_social = get_option( 'wpseo_social', array() );

		// Twitter handle.
		$twitter_site = isset( $wpseo_social['twitter_site'] ) ? $wpseo_social['twitter_site'] : '';
		if ( ! empty( $twitter_site ) && ! $this->contains_yoast_variable( $twitter_site ) ) {
			$existing = get_field( 'mu_seo_twitter_handle', 'option' );
			if ( ! empty( $existing ) ) {
				++$stats['skipped_conflict'];
				$this->verbose_line( $verbose, 'mu_seo_twitter_handle', 'skipped (conflict)' );
			} elseif ( ! $dry_run ) {
				update_field( 'mu_seo_twitter_handle', sanitize_text_field( $twitter_site ), 'option' );
				++$stats['migrated'];
				$this->verbose_line( $verbose, 'mu_seo_twitter_handle', 'migrated → ' . $twitter_site );
			} else {
				++$stats['migrated'];
				$this->verbose_line( $verbose, 'mu_seo_twitter_handle', 'would migrate → ' . $twitter_site );
			}
		} else {
			++$stats['skipped_empty'];
			$this->verbose_line( $verbose, 'mu_seo_twitter_handle', 'skipped (empty or variable)' );
		}

		// Default OG image (attachment ID).
		$og_image_id = isset( $wpseo_social['og_default_image_id'] ) ? absint( $wpseo_social['og_default_image_id'] ) : 0;
		if ( $og_image_id > 0 ) {
			$existing = get_field( 'mu_seo_default_og_image', 'option' );
			if ( ! empty( $existing ) ) {
				++$stats['skipped_conflict'];
				$this->verbose_line( $verbose, 'mu_seo_default_og_image', 'skipped (conflict)' );
			} elseif ( ! $dry_run ) {
				update_field( 'mu_seo_default_og_image', $og_image_id, 'option' );
				++$stats['migrated'];
				$this->verbose_line( $verbose, 'mu_seo_default_og_image', 'migrated → attachment ' . $og_image_id );
			} else {
				++$stats['migrated'];
				$this->verbose_line( $verbose, 'mu_seo_default_og_image', 'would migrate → attachment ' . $og_image_id );
			}
		} else {
			++$stats['skipped_empty'];
			$this->verbose_line( $verbose, 'mu_seo_default_og_image', 'skipped (empty)' );
		}

		return $stats;
	}

	/**
	 * Migrate a single post's Yoast meta into MU SEO ACF fields.
	 *
	 * @param int  $post_id Post ID.
	 * @param bool $dry     Whether to skip writing.
	 * @return array { migrated: int, skipped_conflict: int, skipped_empty: int, log: array }
	 */
	public function migrate_post( $post_id, $dry ) {
		$stats = array(
			'migrated'         => 0,
			'skipped_conflict' => 0,
			'skipped_empty'    => 0,
			'log'              => array(),
		);

		// --- Text fields ---
		$text_map = array(
			'_yoast_wpseo_title'     => 'mu_seo_title',
			'_yoast_wpseo_metadesc'  => 'mu_seo_description',
			'_yoast_wpseo_canonical' => 'mu_seo_canonical',
		);

		foreach ( $text_map as $yoast_key => $acf_field ) {
			$value = $this->yoast_value( $post_id, $yoast_key );
			if ( '' === $value ) {
				++$stats['skipped_empty'];
				$stats['log'][] = array(
					'field'  => $acf_field,
					'status' => 'skipped (empty or variable)',
				);
				continue;
			}
			if ( $this->mu_seo_has_value( $post_id, $acf_field ) ) {
				++$stats['skipped_conflict'];
				$stats['log'][] = array(
					'field'  => $acf_field,
					'status' => 'skipped (conflict)',
				);
				continue;
			}
			if ( ! $dry ) {
				update_field( $acf_field, sanitize_text_field( $value ), $post_id );
				$stats['log'][] = array(
					'field'  => $acf_field,
					'status' => 'migrated → ' . $value,
				);
			} else {
				$stats['log'][] = array(
					'field'  => $acf_field,
					'status' => 'would migrate → ' . $value,
				);
			}
			++$stats['migrated'];
		}

		// --- Robots array ---
		$noindex  = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
		$nofollow = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', true );

		if ( '1' === $noindex || '1' === $nofollow ) {
			$existing_robots = get_field( 'mu_seo_robots', $post_id );
			if ( ! empty( $existing_robots ) ) {
				++$stats['skipped_conflict'];
				$stats['log'][] = array(
					'field'  => 'mu_seo_robots',
					'status' => 'skipped (conflict)',
				);
			} else {
				$robots = array();
				if ( '1' === $noindex ) {
					$robots[] = 'noindex';
				}
				if ( '1' === $nofollow ) {
					$robots[] = 'nofollow';
				}
				if ( ! $dry ) {
					update_field( 'mu_seo_robots', $robots, $post_id );
					$stats['log'][] = array(
						'field'  => 'mu_seo_robots',
						'status' => 'migrated → ' . implode( ', ', $robots ),
					);
				} else {
					$stats['log'][] = array(
						'field'  => 'mu_seo_robots',
						'status' => 'would migrate → ' . implode( ', ', $robots ),
					);
				}
				++$stats['migrated'];
			}
		}

		// --- OG image ---
		$og_image_id = absint( get_post_meta( $post_id, '_yoast_wpseo_opengraph-image-id', true ) );

		if ( $og_image_id > 0 ) {
			if ( $this->mu_seo_has_value( $post_id, 'mu_seo_og_image' ) ) {
				++$stats['skipped_conflict'];
				$stats['log'][] = array(
					'field'  => 'mu_seo_og_image',
					'status' => 'skipped (conflict)',
				);
			} else {
				if ( ! $dry ) {
					update_field( 'mu_seo_og_image', $og_image_id, $post_id );
					$stats['log'][] = array(
						'field'  => 'mu_seo_og_image',
						'status' => 'migrated → attachment ' . $og_image_id,
					);
				} else {
					$stats['log'][] = array(
						'field'  => 'mu_seo_og_image',
						'status' => 'would migrate → attachment ' . $og_image_id,
					);
				}
				++$stats['migrated'];
			}
		} else {
			// Fallback: resolve the image URL to an attachment ID.
			$og_image_url = get_post_meta( $post_id, '_yoast_wpseo_opengraph-image', true );
			if ( ! empty( $og_image_url ) && ! $this->contains_yoast_variable( $og_image_url ) ) {
				$resolved_id = attachment_url_to_postid( esc_url_raw( $og_image_url ) );
				if ( $resolved_id > 0 ) {
					if ( $this->mu_seo_has_value( $post_id, 'mu_seo_og_image' ) ) {
						++$stats['skipped_conflict'];
						$stats['log'][] = array(
							'field'  => 'mu_seo_og_image',
							'status' => 'skipped (conflict)',
						);
					} else {
						if ( ! $dry ) {
							update_field( 'mu_seo_og_image', $resolved_id, $post_id );
							$stats['log'][] = array(
								'field'  => 'mu_seo_og_image',
								'status' => 'migrated → attachment ' . $resolved_id . ' (resolved from URL)',
							);
						} else {
							$stats['log'][] = array(
								'field'  => 'mu_seo_og_image',
								'status' => 'would migrate → attachment ' . $resolved_id . ' (resolved from URL)',
							);
						}
						++$stats['migrated'];
					}
				} else {
					++$stats['skipped_empty'];
					$stats['log'][] = array(
						'field'  => 'mu_seo_og_image',
						'status' => 'skipped (URL could not be resolved to attachment)',
					);
				}
			} elseif ( ! empty( $og_image_url ) ) {
					++$stats['skipped_empty'];
					$stats['log'][] = array(
						'field'  => 'mu_seo_og_image',
						'status' => 'skipped (empty or variable)',
					);
			}
		}

		return $stats;
	}

	// -------------------------------------------------------------------------
	// Helpers
	// -------------------------------------------------------------------------

	/**
	 * Get a Yoast post meta value, returning '' if empty or contains variables.
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Yoast meta key.
	 * @return string The raw value, or '' if it should be skipped.
	 */
	public function yoast_value( $post_id, $meta_key ) {
		$value = get_post_meta( $post_id, $meta_key, true );
		if ( empty( $value ) ) {
			return '';
		}
		if ( $this->contains_yoast_variable( $value ) ) {
			return '';
		}
		return $value;
	}

	/**
	 * Check whether an MU SEO ACF field already has a non-empty value on a post.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $field   ACF field name.
	 * @return bool
	 */
	public function mu_seo_has_value( $post_id, $field ) {
		$value = get_field( $field, $post_id );
		return ! empty( $value );
	}

	/**
	 * Check whether a string contains a Yoast template variable (%%...%%).
	 *
	 * @param string $value The string to inspect.
	 * @return bool
	 */
	public function contains_yoast_variable( $value ) {
		return false !== strpos( $value, '%%' );
	}

	/**
	 * Emit a WP_CLI line for an option field action when verbose mode is on.
	 *
	 * @param bool   $verbose Whether verbose mode is enabled.
	 * @param string $field   ACF field name.
	 * @param string $status  Human-readable status string.
	 */
	private function verbose_line( $verbose, $field, $status ) {
		if ( $verbose ) {
			WP_CLI::line( sprintf( '  %-30s %s', $field . ':', $status ) );
		}
	}
}
