<?php

/**
 * Dashboard System Status Template
 */
if ( !defined( 'ABSPATH' ) ) {
	exit;
}

// Environment data
$curl_enabled = function_exists( 'curl_version' );
$gd_enabled   = extension_loaded( 'gd' );
$zip_enabled  = class_exists( 'ZipArchive' );
$dom_enabled  = extension_loaded( 'dom' );
$xml_enabled  = extension_loaded( 'xml' );

?>

<div class="vlt-masonry-grid">
	<div class="vlt-masonry-sizer"></div>

	<!-- PHP Extensions -->
	<div class="vlt-masonry-item">
		<div class="vlt-widget">
			<div class="vlt-widget__title">
				<mark><?php esc_html_e( 'PHP Extensions', 'toolkit' ); ?></mark>
			</div>

			<div class="vlt-widget__content">
				<table class="widefat" cellspacing="0">
					<tbody>
						<tr>
							<td><?php esc_html_e( 'cURL', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $curl_enabled, $curl_enabled ? esc_html__( 'Enabled', 'toolkit' ) : esc_html__( 'Disabled', 'toolkit' ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'GD Library', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $gd_enabled, $gd_enabled ? esc_html__( 'Enabled', 'toolkit' ) : esc_html__( 'Disabled', 'toolkit' ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'ZIP Archive', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $zip_enabled, $zip_enabled ? esc_html__( 'Enabled', 'toolkit' ) : esc_html__( 'Disabled', 'toolkit' ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'DOM', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $dom_enabled, $dom_enabled ? esc_html__( 'Enabled', 'toolkit' ) : esc_html__( 'Disabled', 'toolkit' ) ); ?></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'XML', 'toolkit' ); ?></td>
							<td><?php echo $this->status( $xml_enabled, $xml_enabled ? esc_html__( 'Enabled', 'toolkit' ) : esc_html__( 'Disabled', 'toolkit' ) ); ?></td>
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
				<mark><?php esc_html_e( 'Active Plugins', 'toolkit' ); ?></mark>
			</div>

			<div class="vlt-widget__content">
				<table class="widefat" cellspacing="0">
					<tbody>
						<?php
						$active_plugins = get_option( 'active_plugins' );
foreach ( $active_plugins as $plugin ) :
	$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $plugin );

	if ( !empty( $plugin_data['Name'] ) ) :
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