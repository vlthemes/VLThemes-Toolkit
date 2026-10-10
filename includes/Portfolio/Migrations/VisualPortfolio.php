<?php

namespace VLT\Toolkit\Portfolio\Migrations;

use VLT\Toolkit\Portfolio\Portfolio;

if ( !defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Visual Portfolio → toolkit portfolio
 *
 * Converts in place: posts of "portfolio" become vlt_portfolio, terms of portfolio_category / portfolio_tag
 * move to our taxonomies. IDs, content, meta, featured images, comments and revisions stay, so nothing is
 * duplicated and layouts that pick items by ID keep working. Menu items pointing to them follow.
 *
 * - A term whose slug already exists in our taxonomy is merged into that term (its items get the existing one).
 * - A post whose slug is taken by one of our items gets a unique slug.
 * - Converted posts and terms are marked, so undo() puts them back (merged terms stay merged).
 * - Visual Portfolio layouts (vp_lists) are not converted: their options don't map 1:1; rebuild them as Grid Layouts.
 *
 * Works with Visual Portfolio active or not. Run it from the Migration tab of the Grid Builder settings, or:
 *
 *     wp vlt-portfolio migrate-vp [--dry-run]
 *     wp vlt-portfolio migrate-vp --undo
 */
class VisualPortfolio {
	const POST_TYPE = 'portfolio';
	const MARK      = '_vlt_migrated_from_vp';
	const ACTION    = 'vlt_toolkit_portfolio_migrate_vp';

	/**
	 * Visual Portfolio taxonomy => ours
	 *
	 * @return array
	 */
	private static function taxonomies() {
		return [
			'portfolio_category' => Portfolio::CATEGORY,
			'portfolio_tag'      => Portfolio::TAG,
		];
	}

	/**
	 * Init
	 */
	public static function init() {
		add_filter( 'vlt_toolkit_grid_settings_tabs', [ __CLASS__, 'tab' ] );
		add_action( 'admin_post_' . self::ACTION, [ __CLASS__, 'handle' ] );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'vlt-portfolio migrate-vp', [ __CLASS__, 'cli' ] );
		}
	}

	/**
	 * What there is to convert, and what was converted
	 *
	 * @return array [ 'posts' => int, 'terms' => [ taxonomy => int ], 'layouts' => int, 'migrated' => int ]
	 */
	public static function stats() {
		global $wpdb;

		$terms = [];

		foreach ( array_keys( self::taxonomies() ) as $taxonomy ) {
			$terms[ $taxonomy ] = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s", $taxonomy ) );
		}

		return [
			'posts'    => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_status <> 'auto-draft'", self::POST_TYPE ) ),
			'terms'    => $terms,
			'layouts'  => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'vp_lists' AND post_status <> 'auto-draft'" ),
			'migrated' => (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->postmeta} WHERE meta_key = %s", self::MARK ) ),
		];
	}

	/**
	 * Convert
	 *
	 * @param bool $dry_run Only count
	 *
	 * @return array [ 'posts' => int, 'terms' => int, 'merged' => int, 'renamed' => int, 'menu_items' => int ]
	 */
	public static function run( $dry_run = false ) {
		global $wpdb;

		$result = [
			'posts'      => 0,
			'terms'      => 0,
			'merged'     => 0,
			'renamed'    => 0,
			'menu_items' => 0,
		];

		// Terms first: post counts are recounted at the end
		foreach ( self::taxonomies() as $from => $to ) {
			$rows = $wpdb->get_results( $wpdb->prepare(
				"SELECT tt.term_taxonomy_id, tt.term_id, t.slug FROM {$wpdb->term_taxonomy} tt JOIN {$wpdb->terms} t ON t.term_id = tt.term_id WHERE tt.taxonomy = %s",
				$from
			) );

			foreach ( $rows as $row ) {
				$existing = $wpdb->get_row( $wpdb->prepare(
					"SELECT tt.term_taxonomy_id, tt.term_id FROM {$wpdb->term_taxonomy} tt JOIN {$wpdb->terms} t ON t.term_id = tt.term_id WHERE tt.taxonomy = %s AND t.slug = %s",
					$to,
					$row->slug
				) );

				++$result[ $existing ? 'merged' : 'terms' ];

				if ( $dry_run ) {
					continue;
				}

				if ( $existing ) {
					// Same slug in ours: give its items the existing term, re-parent its children, drop it
					$wpdb->query( $wpdb->prepare( "INSERT IGNORE INTO {$wpdb->term_relationships} (object_id, term_taxonomy_id, term_order) SELECT object_id, %d, term_order FROM {$wpdb->term_relationships} WHERE term_taxonomy_id = %d", $existing->term_taxonomy_id, $row->term_taxonomy_id ) );
					$wpdb->delete( $wpdb->term_relationships, [ 'term_taxonomy_id' => $row->term_taxonomy_id ] );
					$wpdb->delete( $wpdb->term_taxonomy, [ 'term_taxonomy_id' => $row->term_taxonomy_id ] );

					// Children already moved or still waiting
					foreach ( [ $from, $to ] as $taxonomy ) {
						$wpdb->update( $wpdb->term_taxonomy, [ 'parent' => $existing->term_id ], [ 'parent' => $row->term_id, 'taxonomy' => $taxonomy ] );
					}

					$result['menu_items'] += self::menu_items( 'taxonomy', $from, $to, (int) $row->term_id, (int) $existing->term_id );
					continue;
				}

				$wpdb->update( $wpdb->term_taxonomy, [ 'taxonomy' => $to ], [ 'term_taxonomy_id' => $row->term_taxonomy_id ] );
				update_term_meta( (int) $row->term_id, self::MARK, $from );
			}

			if ( !$dry_run ) {
				// Moved terms keep their IDs, so parents and the other menu items only change the taxonomy
				$result['menu_items'] += self::menu_items( 'taxonomy', $from, $to );
			}
		}

		$ids             = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND post_status <> 'auto-draft'", self::POST_TYPE ) ) );
		$result['posts'] = count( $ids );

		if ( $dry_run ) {
			$result['renamed'] = self::slug_conflicts( $ids, true );

			return $result;
		}

		foreach ( $ids as $id ) {
			update_post_meta( $id, self::MARK, self::POST_TYPE );
		}

		if ( $ids ) {
			$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_type = %s WHERE post_type = %s", Portfolio::POST_TYPE, self::POST_TYPE ) );
		}

		$result['renamed']     = self::slug_conflicts( $ids );
		$result['menu_items'] += self::menu_items( 'post_type', self::POST_TYPE, Portfolio::POST_TYPE );
		$result['menu_items'] += self::menu_items( 'post_type_archive', self::POST_TYPE, Portfolio::POST_TYPE );

		self::refresh( $ids );

		return $result;
	}

	/**
	 * Put converted posts and terms back to Visual Portfolio
	 *
	 * @return array [ 'posts' => int, 'terms' => int ]
	 */
	public static function undo() {
		global $wpdb;

		$ids = array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s", self::MARK ) ) );

		foreach ( $ids as $id ) {
			set_post_type( $id, self::POST_TYPE );
			delete_post_meta( $id, self::MARK );
		}

		$terms = $wpdb->get_results( $wpdb->prepare( "SELECT term_id, meta_value FROM {$wpdb->termmeta} WHERE meta_key = %s", self::MARK ) );

		foreach ( $terms as $term ) {
			$to = self::taxonomies()[ $term->meta_value ] ?? null;

			if ( $to ) {
				$wpdb->update( $wpdb->term_taxonomy, [ 'taxonomy' => $term->meta_value ], [ 'term_id' => $term->term_id, 'taxonomy' => $to ] );
				self::menu_items( 'taxonomy', $to, $term->meta_value, (int) $term->term_id );
			}

			delete_term_meta( (int) $term->term_id, self::MARK );
		}

		if ( $ids ) {
			self::menu_items( 'post_type', Portfolio::POST_TYPE, self::POST_TYPE, 0, 0, $ids );
		}

		self::refresh( $ids );

		return [
			'posts' => count( $ids ),
			'terms' => count( $terms ),
		];
	}

	/**
	 * Give converted posts a unique slug where one of our items already uses it
	 *
	 * @param array $ids     Converted post IDs
	 * @param bool  $dry_run Only count
	 *
	 * @return int
	 */
	private static function slug_conflicts( array $ids, $dry_run = false ) {
		global $wpdb;

		if ( !$ids ) {
			return 0;
		}

		$in      = implode( ',', $ids );
		$clashes = $wpdb->get_results( $wpdb->prepare(
			"SELECT p.ID, p.post_name, p.post_status, p.post_parent FROM {$wpdb->posts} p
			WHERE p.ID IN ({$in}) AND p.post_name <> '' AND EXISTS (
				SELECT 1 FROM {$wpdb->posts} o WHERE o.post_type = %s AND o.post_name = p.post_name AND o.ID NOT IN ({$in})
			)",
			Portfolio::POST_TYPE
		) );

		if ( !$dry_run ) {
			foreach ( $clashes as $post ) {
				$wpdb->update( $wpdb->posts, [ 'post_name' => wp_unique_post_slug( $post->post_name, $post->ID, $post->post_status, Portfolio::POST_TYPE, $post->post_parent ) ], [ 'ID' => $post->ID ] );
			}
		}

		return count( $clashes );
	}

	/**
	 * Point nav menu items at the new post type / taxonomy
	 *
	 * @param string $type    _menu_item_type: taxonomy | post_type | post_type_archive
	 * @param string $from    Old object
	 * @param string $to      New object
	 * @param int    $term    Only items of this term (0 = all of $from)
	 * @param int    $term_to New term ID for $term (merged term), 0 = same
	 * @param array  $posts   Only items of these posts
	 *
	 * @return int Updated items
	 */
	private static function menu_items( $type, $from, $to, $term = 0, $term_to = 0, array $posts = [] ) {
		global $wpdb;

		$sql = $wpdb->prepare(
			"SELECT o.post_id FROM {$wpdb->postmeta} o JOIN {$wpdb->postmeta} t ON t.post_id = o.post_id AND t.meta_key = '_menu_item_type' AND t.meta_value = %s WHERE o.meta_key = '_menu_item_object' AND o.meta_value = %s",
			$type,
			$from
		);

		$items = array_map( 'intval', $wpdb->get_col( $sql ) );

		foreach ( $items as $i => $item ) {
			$object_id = (int) get_post_meta( $item, '_menu_item_object_id', true );

			if ( ( $term && $object_id !== $term ) || ( $posts && !in_array( $object_id, $posts, true ) ) ) {
				unset( $items[ $i ] );
				continue;
			}

			update_post_meta( $item, '_menu_item_object', $to );

			if ( $term_to ) {
				update_post_meta( $item, '_menu_item_object_id', $term_to );
			}
		}

		return count( $items );
	}

	/**
	 * Caches, term counts and permalinks after a change
	 *
	 * @param array $ids Changed post IDs
	 */
	private static function refresh( array $ids ) {
		foreach ( $ids as $id ) {
			clean_post_cache( $id );
		}

		wp_cache_flush();

		foreach ( array_merge( array_keys( self::taxonomies() ), array_values( self::taxonomies() ) ) as $taxonomy ) {
			if ( taxonomy_exists( $taxonomy ) ) {
				$terms = get_terms( [
					'taxonomy'   => $taxonomy,
					'hide_empty' => false,
					'fields'     => 'tt_ids',
				] );

				wp_update_term_count_now( is_array( $terms ) ? $terms : [], $taxonomy );
			}
		}

		flush_rewrite_rules( false );
	}

	/**
	 * Migration tab of the settings page
	 *
	 * @param array $tabs Tabs
	 *
	 * @return array
	 */
	public static function tab( array $tabs ) {
		$tabs['migration'] = [
			'label'  => __( 'Migration', 'toolkit' ),
			'render' => [ __CLASS__, 'render' ],
		];

		return $tabs;
	}

	/**
	 * Tab content: what there is to convert, and the buttons
	 */
	public static function render() {
		if ( !current_user_can( 'manage_options' ) ) {
			return;
		}

		$stats  = self::stats();
		$done   = sanitize_key( wp_unslash( $_GET['done'] ?? '' ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$report = get_transient( self::ACTION . '_' . get_current_user_id() );
		$plan   = self::run( true );

		$button = function ( $mode, $label, $class ) {
			?>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>">
				<input type="hidden" name="mode" value="<?php echo esc_attr( $mode ); ?>">
				<?php wp_nonce_field( self::ACTION ); ?>
				<button type="submit" class="button <?php echo esc_attr( $class ); ?>"><?php echo esc_html( $label ); ?></button>
			</form>
			<?php
		};
		?>
		<?php if ( $done && is_array( $report ) ) : ?>
			<div class="notice notice-success inline"><p>
					<?php
					echo esc_html( 'undo' === $done
						/* translators: 1: posts, 2: terms */
						? sprintf( __( 'Restored %1$d items and %2$d terms to Visual Portfolio.', 'toolkit' ), $report['posts'], $report['terms'] )
						/* translators: 1: posts, 2: terms, 3: merged terms, 4: renamed slugs, 5: menu items */
						: sprintf( __( 'Converted %1$d items and %2$d terms (%3$d merged into existing terms), %4$d slugs made unique, %5$d menu items updated.', 'toolkit' ), $report['posts'], $report['terms'], $report['merged'], $report['renamed'], $report['menu_items'] ) );
					?>
			</p></div>
		<?php endif; ?>

		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<mark><?php esc_html_e( 'Visual Portfolio', 'toolkit' ); ?></mark>
			</div>

			<div class="vlt-widget__content">
				<div class="vlt-grid-settings__section">
					<p><?php esc_html_e( 'Moves Visual Portfolio items and their categories and tags into the toolkit portfolio. Items keep their IDs, content, featured images and custom fields; nothing is copied.', 'toolkit' ); ?></p>
				</div>

				<div class="vlt-grid-settings__section">
					<h3 class="vlt-grid-settings__heading"><?php esc_html_e( 'Found', 'toolkit' ); ?></h3>
					<ul class="vlt-grid-settings__stats">
						<?php
						foreach ( [
							[ $stats['posts'], __( 'Portfolio items', 'toolkit' ) ],
							[ $stats['terms']['portfolio_category'], __( 'Categories', 'toolkit' ) ],
							[ $stats['terms']['portfolio_tag'], __( 'Tags', 'toolkit' ) ],
						] as [ $count, $label ] ) :
							?>
							<li class="vlt-grid-settings__stat<?php echo $count ? '' : ' is-empty'; ?>">
								<strong><?php echo esc_html( number_format_i18n( $count ) ); ?></strong>
								<span><?php echo esc_html( $label ); ?></span>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>

				<div class="vlt-grid-settings__section">
					<h3 class="vlt-grid-settings__heading"><?php esc_html_e( 'What changes', 'toolkit' ); ?></h3>
					<ul class="vlt-grid-settings__rows">
						<li><?php esc_html_e( 'Terms merged into existing ones (same slug)', 'toolkit' ); ?> <strong><?php echo esc_html( number_format_i18n( $plan['merged'] ) ); ?></strong></li>
						<li><?php esc_html_e( 'Slugs already used by toolkit items (renamed)', 'toolkit' ); ?> <strong><?php echo esc_html( number_format_i18n( $plan['renamed'] ) ); ?></strong></li>
						<li><?php esc_html_e( 'Already converted (can be undone)', 'toolkit' ); ?> <strong><?php echo esc_html( number_format_i18n( $stats['migrated'] ) ); ?></strong></li>
					</ul>

					<?php if ( $stats['layouts'] ) : ?>
						<div class="notice notice-info inline">
							<p>
								<?php
								/* translators: %d: number of Visual Portfolio layouts */
								echo esc_html( sprintf( __( '%d Visual Portfolio layouts are not converted: rebuild them as Grid Layouts.', 'toolkit' ), $stats['layouts'] ) );
								?>
							</p>
						</div>
					<?php endif; ?>
				</div>

				<div class="vlt-grid-settings__section">
					<?php if ( !$stats['posts'] && !array_sum( $stats['terms'] ) && !$stats['migrated'] ) : ?>
						<div class="notice notice-success inline"><p><?php esc_html_e( 'No Visual Portfolio items found — nothing to convert.', 'toolkit' ); ?></p></div>
					<?php else : ?>
						<div class="notice notice-warning inline"><p><?php esc_html_e( 'Back up the database first. Old item links change to the toolkit portfolio URLs.', 'toolkit' ); ?></p></div>
					<?php endif; ?>
				</div>
			</div>
		</div>

		<div class="vlt-grid-settings__actions">
			<?php
			if ( $stats['posts'] || array_sum( $stats['terms'] ) ) {
				$button( 'run', __( 'Convert now', 'toolkit' ), 'button-primary' );
			}

			if ( $stats['migrated'] ) {
				$button( 'undo', __( 'Undo conversion', 'toolkit' ), '' );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Admin form handler
	 */
	public static function handle() {
		if ( !current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'toolkit' ), 403 );
		}

		check_admin_referer( self::ACTION );

		$mode   = 'undo' === sanitize_key( wp_unslash( $_POST['mode'] ?? '' ) ) ? 'undo' : 'run';
		$report = 'undo' === $mode ? self::undo() : self::run();

		set_transient( self::ACTION . '_' . get_current_user_id(), $report, MINUTE_IN_SECONDS );

		// Back to the tab the form was sent from
		wp_safe_redirect( add_query_arg( 'done', $mode, wp_get_referer() ?: admin_url() ) );
		exit;
	}

	/**
	 * WP-CLI: convert Visual Portfolio items
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Only show what would change.
	 *
	 * [--undo]
	 * : Put converted items back to Visual Portfolio.
	 *
	 * @param array $args       Positional args
	 * @param array $assoc_args Flags
	 */
	public static function cli( $args, $assoc_args ) {
		if ( !empty( $assoc_args['undo'] ) ) {
			$report = self::undo();
			\WP_CLI::success( sprintf( 'Restored %d items and %d terms.', $report['posts'], $report['terms'] ) );
			return;
		}

		$dry    = !empty( $assoc_args['dry-run'] );
		$report = self::run( $dry );

		$summary = sprintf( '%d items, %d terms (%d merged), %d slugs renamed', $report['posts'], $report['terms'], $report['merged'], $report['renamed'] );

		\WP_CLI::success( $dry ? "Would convert {$summary}." : "Converted {$summary}, {$report['menu_items']} menu items." );
	}
}
