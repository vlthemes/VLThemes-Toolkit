<?php

/**
 * Dashboard Requirements Template
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

// Minimum requirements
$min_requirements = [
	'php_version'         => '7.4',
	'memory_limit'        => 128, // MB
	'wp_memory_limit'     => 512, // MB (recommended)
	'max_execution_time'  => 300,
	'max_input_time'      => 300,
	'post_max_size'       => 512,
	'upload_max_filesize' => 512,
	'max_input_vars'      => 5000,
];

// Environment data
$php_version        = phpversion();
$wp_version         = get_bloginfo( 'version' );
$memory_limit       = ini_get( 'memory_limit' );
$memory_limit_bytes = wp_convert_hr_to_bytes( $memory_limit );
$memory_limit_mb    = $memory_limit_bytes / MB_IN_BYTES;

$max_upload     = ini_get( 'upload_max_filesize' );
$max_post       = ini_get( 'post_max_size' );
$max_exec_time  = (int) ini_get( 'max_execution_time' );
$max_input_time = (int) ini_get( 'max_input_time' );
$max_input_vars = (int) ini_get( 'max_input_vars' );

// Time limits: 0 = unlimited; max_input_time -1 = falls back to max_execution_time
$effective_input_time = -1 === $max_input_time ? $max_exec_time : $max_input_time;
$time_label           = fn( $seconds ) => 0 === $seconds ? esc_html__( 'Unlimited', 'toolkit' ) : sprintf( /* translators: %s: number of seconds */ esc_html__( '%s sec', 'toolkit' ), $seconds );

// Checks (memory_limit -1 = unlimited; sizes compared in bytes, so 1G / 512M / 2048K all work)
$php_ok         = version_compare( $php_version, $min_requirements['php_version'], '>=' );
$memory_ok      = $memory_limit_bytes < 0 || $memory_limit_mb >= $min_requirements['memory_limit'];
$wp_memory_ok   = $memory_limit_bytes < 0 || $memory_limit_mb >= $min_requirements['wp_memory_limit'];
$exec_time_ok   = 0 === $max_exec_time || $max_exec_time >= $min_requirements['max_execution_time'];
$input_time_ok  = 0 === $effective_input_time || $effective_input_time >= $min_requirements['max_input_time'];
$post_size_ok   = wp_convert_hr_to_bytes( $max_post ) >= $min_requirements['post_max_size'] * MB_IN_BYTES;
$upload_size_ok = wp_convert_hr_to_bytes( $max_upload ) >= $min_requirements['upload_max_filesize'] * MB_IN_BYTES;
$input_vars_ok  = $max_input_vars >= $min_requirements['max_input_vars'];
$wp_debug_ok    = !WP_DEBUG;

// Overall statuses
$server_ok = $php_ok && $exec_time_ok && $input_time_ok && $post_size_ok && $upload_size_ok && $input_vars_ok && $memory_ok;
$wp_ok     = $wp_debug_ok && $wp_memory_ok;
$theme_ok  = is_child_theme();

?>

<div class="vlt-masonry-grid">
	<div class="vlt-masonry-sizer"></div>

	<!-- Server Settings -->
	<div class="vlt-masonry-item">
		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<mark><?php esc_html_e( 'Server Settings', 'toolkit' ); ?></mark>
				<?php if ( $server_ok ) : ?>
					<span class="vlt-badge true"><?php esc_html_e( 'No Problems', 'toolkit' ); ?></span>
				<?php else : ?>
					<span class="vlt-badge false"><?php esc_html_e( 'Can be improved', 'toolkit' ); ?></span>
				<?php endif; ?>
			</div>

			<div class="vlt-widget__content">

				<div class="notice notice-info inline mb-sm">
					<p><?php /* translators: %1$s-%6$s: <strong> tags */ printf( esc_html__( '%1$sNote:%2$s These settings affect %3$sonly the speed of demo content import%4$s. Low values will not break the site — they are %5$snot critical%6$s.', 'toolkit' ), '<strong>', '</strong>', '<strong>', '</strong>', '<strong>', '</strong>' ); ?></p>
				</div>

				<table class="widefat" cellspacing="0">
					<tbody>
						<tr>
							<td><?php esc_html_e( 'PHP Version:', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $php_ok, $php_version ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Memory Limit:', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $memory_ok, $memory_limit_bytes < 0 ? esc_html__( 'Unlimited', 'toolkit' ) : $memory_limit ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Max Execution Time:', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $exec_time_ok, $time_label( $max_exec_time ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Max Input Time:', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $input_time_ok, $time_label( $effective_input_time ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Post Max Size:', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $post_size_ok, $max_post ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Upload Max Filesize:', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $upload_size_ok, $max_upload ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Max Input Vars:', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $input_vars_ok, $max_input_vars ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- WordPress Settings -->
	<div class="vlt-masonry-item">
		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<mark><?php esc_html_e( 'WordPress Settings', 'toolkit' ); ?></mark>
				<?php if ( $wp_ok ) : ?>
					<span class="vlt-badge true"><?php esc_html_e( 'No Problems', 'toolkit' ); ?></span>
				<?php else : ?>
					<span class="vlt-badge false"><?php esc_html_e( 'Can be improved', 'toolkit' ); ?></span>
				<?php endif; ?>
			</div>

			<div class="vlt-widget__content">
				<table class="widefat" cellspacing="0">
					<tbody>
						<tr>
							<td><?php esc_html_e( 'Home URL:', 'toolkit' ); ?></td>
							<td><a href="<?php echo esc_url( home_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_url( home_url( '/' ) ); ?></a></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Site URL:', 'toolkit' ); ?></td>
							<td><a href="<?php echo esc_url( site_url( '/' ) ); ?>" target="_blank" rel="noopener"><?php echo esc_url( site_url( '/' ) ); ?></a></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'WP Version:', 'toolkit' ); ?></td>
							<td><?php echo esc_html( $wp_version ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Memory Limit (WP):', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $wp_memory_ok, $memory_limit_bytes < 0 ? esc_html__( 'Unlimited', 'toolkit' ) : $memory_limit ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'WP Debug:', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $wp_debug_ok, WP_DEBUG ? esc_html__( 'Enabled', 'toolkit' ) : esc_html__( 'Disabled', 'toolkit' ) ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>


	<!-- Theme Config -->
	<div class="vlt-masonry-item">
		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<mark><?php esc_html_e( 'Theme Config', 'toolkit' ); ?></mark>
				<?php if ( $theme_ok ) : ?>
					<span class="vlt-badge true"><?php esc_html_e( 'No Problems', 'toolkit' ); ?></span>
				<?php else : ?>
					<span class="vlt-badge false"><?php esc_html_e( 'Can be improved', 'toolkit' ); ?></span>
				<?php endif; ?>
			</div>

			<div class="vlt-widget__content">
				<table class="widefat" cellspacing="0">
					<tbody>
						<tr>
							<td><?php esc_html_e( 'Theme Name:', 'toolkit' ); ?></td>
							<td><?php echo esc_html( $this->theme_name ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Child Theme:', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $theme_ok, $theme_ok ? esc_html__( 'Enabled', 'toolkit' ) : esc_html__( 'Disabled', 'toolkit' ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Version:', 'toolkit' ); ?></td>
							<td><?php echo esc_html( $this->theme_version ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Author:', 'toolkit' ); ?></td>
							<td><?php echo wp_kses_post( $this->theme_author ); ?></td>
						</tr>
					</tbody>
				</table>

				<!-- Child Theme Prompt -->
				<?php if ( !is_child_theme() ) : ?>
					<div class="notice notice-info inline mt-sm">
						<p><?php /* translators: %1$s-%4$s: <strong> tags */ printf( esc_html__( '%1$sRecommendation:%2$s We recommend working with a %3$schild theme%4$s to safely customize styles and functions without losing changes on theme updates.', 'toolkit' ), '<strong>', '</strong>', '<strong>', '</strong>' ); ?></p>
					</div>
				<?php endif; ?>
			</div>
		</div>
	</div>

</div>