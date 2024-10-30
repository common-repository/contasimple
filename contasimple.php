<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.contasimple.com
 * @since             1.0.0
 * @package           Contasimple
 *
 * @wordpress-plugin
 * Plugin Name:       Contasimple
 * Plugin URI:        https://www.contasimple.com/tutorial-como-configurar-plugin-woocommerce
 * Description:       This plugin allows you to generate invoices for all your WooCommerce orders.
 * Version:           1.30.0
 * Author:            Contasimple S.L. <soporte@contasimple.com>
 * Author URI:        http://www.contasimple.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       contasimple
 * Domain Path:       /languages
 *
 * WC tested up to:   8.7.0
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'CS_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-contasimple-activator.php
 */
function activate_contasimple() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-contasimple-activator.php';
	Contasimple_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-contasimple-deactivator.php
 */
function deactivate_contasimple() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-contasimple-deactivator.php';
	Contasimple_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_contasimple' );
register_deactivation_hook( __FILE__, 'deactivate_contasimple' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-contasimple.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_contasimple() {

	new Contasimple();

	// The loader->run() has been moved to the WooCommerce WC_Integration init process since we must first determine if the config wizard has been completed in order to load most hooks.
	/* $plugin->run(); */
}

run_contasimple();
