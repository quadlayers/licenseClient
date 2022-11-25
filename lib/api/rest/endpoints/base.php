<?php

namespace QUADLAYERS\LicenseClient\Api\Rest\Endpoints;

use QUADLAYERS\LicenseClient\Api\Rest\RoutesLibrary;
use QUADLAYERS\LicenseClient\Models\Plugin as Model_Plugin;
use QUADLAYERS\LicenseClient\Models\UserData as Model_User_Data;
use QUADLAYERS\LicenseClient\Models\Activation as Model_Activation;

/**
 * Base Class
 */

abstract class Base implements RouteInterface {

	// protected static $rest_namespace = 'ql/licenseClient';
	protected $routes_library;
	protected $rest_route;

	public function __construct( array $plugin_data, RoutesLibrary $routes_library ) {

		$this->routes_library = $routes_library;

		add_action(
			'rest_api_init',
			function() use ( $plugin_data ) {

				register_rest_route(
					$this->routes_library->get_rest_namespace(),
					$this->get_rest_route(),
					array(
						'args'                => static::get_rest_args(),
						'methods'             => static::get_rest_method(),
						'callback'            => function( $request ) use ( $plugin_data ) {

							$model_plugin     = new Model_Plugin( $plugin_data );
							$model_activation = new Model_Activation( $model_plugin );
							$model_user_data = new Model_User_Data( $model_plugin );

							return $this->callback(
								$request,
								$model_plugin,
								$model_activation,
								$model_user_data
							);
						},
						'permission_callback' => array( static::class, 'get_rest_permission' ),
					)
				);
			}
		);

		$routes_library->register( $this );
	}

	public function get_name() {
		$rest_route = $this->get_rest_route();
		$method     = strtolower( static::get_rest_method() );
		return "$rest_route/$method";
	}

	public function get_rest_route() {
		return $this->rest_route;
	}

	public function get_rest_path() {

		$rest_namespace = $this->routes_library->get_rest_namespace();
		$rest_route     = $this->get_rest_route();

		return "{$rest_namespace}/{$rest_route}";

	}

	public function get_rest_args() {
		return array();
	}

	public function get_rest_permission() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return false;
		}
		return true;
	}

	public function get_rest_url() {

		$blog_id   = get_current_blog_id();
		$rest_path = $this->get_rest_path();

		return get_rest_url( $blog_id, $rest_path );

	}

	private static function get_error( $code, $message ) {
		return array(
			'code'    => $code,
			'message' => $message,
		);
	}

	public static function handle_response( $response ) {

		$response = (array) $response;

		if ( isset( $response['code'], $response['message'] ) ) {
			return rest_ensure_response(
				self::get_error(
					$response['code'],
					$response['message']
				)
			);
		}

		return $response;
	}
}