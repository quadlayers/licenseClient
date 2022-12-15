<?php

namespace QuadLayers\LicenseClient\Backend\Plugin;

use QuadLayers\LicenseClient\Models\Plugin as Model_Plugin;
use QuadLayers\LicenseClient\Models\Activation as Model_Activation;
use QuadLayers\LicenseClient\Api\Fetch\Product\Update as API_Fetch_Product_Update;

/**
 * Controller_Plugin_Information Class
 *
 * Implement plugin automatic updates.
 *
 * @since 1.0.2
 */
class Update {

	/**
	 * Instantiated Model_Plugin in the constructor.
	 *
	 * @var Model_Plugin
	 */
	private $plugin;

	/**
	 * Instantiated Model_Activation in the constructor.
	 *
	 * @var Model_Activation
	 */
	private $activation;

	/**
	 * Setup class
	 *
	 * @param Model_Plugin     $plugin
	 * @param Model_Activation $activation
	 */
	public function __construct( Model_Plugin $plugin, Model_Activation $activation ) {

		$this->plugin     = $plugin;
		$this->activation = $activation;

		add_action(
			'admin_init',
			function() {
				add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'add_fetch_data' ) );
			}
		);
	}

	/**
	 * Add fetch data from the server API to the plugin transient.
	 *
	 * @param object $transient
	 * @return object
	 */
	public function add_fetch_data( $transient ) {

		if ( empty( $transient->checked ) ) {
			return $transient;
		}

		$plugin = $transient->no_update[ $this->plugin->get_plugin_base() ];

		/**
		 * Check if there is higher version available.
		 */

		$is_higher_version = version_compare( $plugin->version, $this->plugin->get_plugin_version(), '>' );

		if ( ! $is_higher_version ) {
			return $transient;
		}

		/**
		 * Get the license activation data.
		 */

		$activation = $this->activation->get();

		/**
		 * Check if the license is activated. If not, show a notice.
		 */
		if ( ! isset( $activation['license_key'], $activation['activation_instance'] ) ) {
			$plugin->upgrade_notice = sprintf(
				'</p></div><span class="notice notice-error notice-alt" style="display:block; padding: 10px;"><b>%s</b> %s</span>',
				__ql_translate( 'Activate your license.' ),
				sprintf(
					__ql_translate( 'Please visit %1$s to activate the license or %2$s in our website.' ),
					sprintf(
						'<a href="%s" target="_blank">%s</a>',
						esc_url( $this->plugin->get_menu_license_url() ),
						__ql_translate( 'settings' ),
					),
					sprintf(
						'<a href="%s" target="_blank">%s</a>',
						esc_url( $this->plugin->get_plugin_url() ),
						__ql_translate( 'purchase' )
					)
				)
			);
			/**
			 * Set the download link true to display the notice.
			 */
			$transient->response[ $this->plugin->get_plugin_base() ] = $plugin;
			return $transient;
		}

		/**
		 * Fetch the download link from the server API.
		 */
		$fetch = new API_Fetch_Product_Update( $this->plugin );

		$update_link = $fetch->get_data(
			array(
				'license_key'         => isset( $activation['license_key'] ) ? $activation['license_key'] : null,
				'activation_instance' => isset( $activation['activation_instance'] ) ? $activation['activation_instance'] : null,
			)
		);

		/**
		 * Check if there is an error. If yes, show a notice.
		 */
		if ( isset( $update_link->error ) || filter_var( $update_link, FILTER_VALIDATE_URL ) === false ) {
			$plugin->upgrade_notice                                  = sprintf(
				'</p></div><span class="notice notice-error notice-alt" style="display:block; padding: 10px;"><b>%s</b> %s</span>',
				__ql_translate( 'Automatic updates are disabled.' ),
				sprintf(
					__ql_translate( 'Please contact the plugin author %1$s.' ),
					sprintf(
						'<a href="%s" target="_blank">%s</a>',
						esc_url( $this->plugin->get_plugin_url() ),
						__ql_translate( 'here' )
					)
				)
			);
			$transient->response[ $this->plugin->get_plugin_base() ] = $plugin;
			return $transient;
		}

		/**
		 * Check if the user has the permission to update plugins. If not, show a notice.
		 */
		if ( ! current_user_can( 'update_plugins' ) ) {
			return $transient;
		}

		$plugin->package       = $update_link;
		$plugin->download_link = $update_link;

		$transient->response[ $this->plugin->get_plugin_base() ] = $plugin;

		return $transient;
	}

}