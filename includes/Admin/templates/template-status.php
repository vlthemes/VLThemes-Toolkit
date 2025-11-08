<?php
/**
 * Dashboard System Status Template
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Environment data
$curl_enabled        = function_exists( 'curl_version' );
$gd_enabled          = extension_loaded( 'gd' );
$zip_enabled         = class_exists( 'ZipArchive' );
$dom_enabled         = extension_loaded( 'dom' );
$xml_enabled         = extension_loaded( 'xml' );

// Simple check function: checkmark or cross only
function vlt_status( $condition, $value = '' ) {
	return $condition
		? '<mark class="true">✅ ' . esc_html( $value ) . '</mark>'
		: '<mark class="false">❌ ' . esc_html( $value ) . '</mark>';
}

?>

<div class="vlt-masonry-grid">
	<div class="vlt-masonry-sizer"></div>

	<!-- PHP Extensions -->
	<div class="vlt-masonry-item">
		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<?php esc_html_e( 'PHP Extensions', 'vlt-helper' ); ?>
			</div>

			<div class="vlt-widget__content">
				<table class="widefat" cellspacing="0">
					<tbody>
						<tr>
							<td><?php esc_html_e( 'cURL', 'vlt-helper' ); ?></td>
							<td><?php echo vlt_status( $curl_enabled, esc_html__( 'Enabled', 'vlt-helper' ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'GD Library', 'vlt-helper' ); ?></td>
							<td><?php echo vlt_status( $gd_enabled, esc_html__( 'Enabled', 'vlt-helper' ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'ZIP Archive', 'vlt-helper' ); ?></td>
							<td><?php echo vlt_status( $zip_enabled, esc_html__( 'Enabled', 'vlt-helper' ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'DOM', 'vlt-helper' ); ?></td>
							<td><?php echo vlt_status( $dom_enabled, esc_html__( 'Enabled', 'vlt-helper' ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'XML', 'vlt-helper' ); ?></td>
							<td><?php echo vlt_status( $xml_enabled, esc_html__( 'Enabled', 'vlt-helper' ) ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>
	</div>

	<!-- Active Plugins -->
	<div class="vlt-masonry-item">
		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<?php esc_html_e( 'Active Plugins', 'vlt-helper' ); ?>
			</div>

			<div class="vlt-widget__content">
				<table class="widefat" cellspacing="0">
					<tbody>
						<?php
						$active_plugins = get_option( 'active_plugins' );
						foreach ( $active_plugins as $plugin ) :
							$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );
							if ( ! empty( $plugin_data['Name'] ) ) :
								?>
								<tr>
									<td><?php echo esc_html( $plugin_data['Name'] ); ?></td>
									<td><?php echo esc_html( $plugin_data['Version'] ); ?></td>
								</tr>
							<?php
							endif;
						endforeach;
						?>
					</tbody>
				</table>
			</div>
		</div>
	</div>

</div>