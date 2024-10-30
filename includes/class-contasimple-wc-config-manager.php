<?php
/**
 * Handle loading and storing all CS configuration to the WP DB.
 *
 * @link       http://www.contasimple.com
 * @since      1.0.0
 *
 * @package    contasimple
 * @subpackage contasimple/includes
 * @author     Contasimple S.L. <soporte@contasimple.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}


/**
 * Class Contasimple_WC_Config_Manager
 *
 * @since      1.11.0
 */
class Contasimple_WC_Config_Manager implements CSConfigManager {

	public function loadConfiguration() {

		// This is WP method of getting data stored in the wp_options table.
		$config = get_option( 'contasimple_settings_account' );

		if ( empty( $config ) ) {
			// If this is the first time running the plugin or the plugin settings have been reset,
			// there will be no settings in the DB. Get an empty instance of CSConfig class.
			// Dynamic info (user credentials) will be empty, but we will have the static info needed: API endpoint url, etc.
			$config = new CSConfig();
		}

		return $config;
	}

	public function storeConfiguration(CSConfig $config) {

		// Serializes all data as an instance of CSConfig class into a single wp_options field.
		update_option( 'contasimple_settings_account', $config );
	}
}
