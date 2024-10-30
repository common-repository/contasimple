<?php
/**
 * Contasimple module performing actions on installation
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
 * Class Contasimple_Install
 */
class Contasimple_Install {

	/**
	 * Trigger pre-installation stuff
	 */
	public static function install() {
		// Not needed anymore since adopting WP Custom Post Types meta.
		// self::create_tables();
	}
}
