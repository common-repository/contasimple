<?php
/**
 * Handle notices in our custom post actions in the admin section.
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
 * Class Contasimple_Notice
 *
 * @since      1.0.0
 */
class Contasimple_Notice {

	private $_message;
	private $_type;
	private $_is_ajax;

	/**
	 * Contasimple_Notice constructor.
	 *
     * @since      1.0.0
	 * @param      string $message The text you want to show.
	 * @param      string $type Possible values are 'warning', 'error' and 'success' and determine the color and icons that will show.
	 * @param      bool   $is_ajax Default is 'false' and will delegate the showing to the proper wp hook. Set 'true' if calling this via AJAX and want to return json format.
	 */
	public function __construct( $message, $type, $is_ajax = false ) {

		$this->_message = $message;
		$this->_type    = $type;
		$this->_is_ajax = $is_ajax;

		if ( ! $is_ajax ) {
			add_action( 'admin_notices', array( $this, 'render' ) );
		}

	}

	/**
	 * Renders HTML for the notice with colors depending on message type.
     *
     * @since      1.0.0
	 * @return     string|null The HTML code.
	 */
	public function render() {

		switch ( $this->_type ) {
			case 'warning':
				$class = 'notice notice-cs notice-warning';
				break;
			case 'error':
				$class = 'notice notice-cs notice-error';
				break;
			case 'success':
				$class = 'notice notice-cs notice-success';
				break;
			default:
				break;
		}

		$html = sprintf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $this->_message );

		if ( ! $this->_is_ajax ) {
			echo $html; // XSS ok.
			return null;
		} else {
			return $html;
		}
	}
}