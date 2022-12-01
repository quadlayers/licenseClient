<?php

namespace LicenseClient\Api\Fetch\Activation;

use LicenseClient\Api\Fetch\Base;

/**
 * API_Fetch_Activation_Create Class
 *
 * @since 1.0.0
 */
class Create extends Base {

	/**
	 * Get rest route path
	 *
	 * @return string
	 */
	public function get_rest_path() {
		return 'activation';
	}

	/**
	 * Get rest method
	 *
	 * @return string POST
	 */
	public static function get_rest_method() {
		return 'POST';
	}

}