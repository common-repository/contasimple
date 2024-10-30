<?php
/**
 * The file that defines the public functionality of the plugin.
 *
 * @link       http://contasimple.com
 * @since      1.0.0
 *
 * @package    contasimple
 * @subpackage contasimple/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The public-facing functionality of the plugin class.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @since             1.0.0
 */
class Contasimple_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    /**
     * Custom file logger for CS actions
     *
     * @var CSLogger
     */
    protected $logger;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string $plugin_name  The name of the plugin.
	 * @param    string $version      The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;
        $this->logger = CSLogger::getDailyLogger();
    }

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

	}

	/**
	 * Injects NIF field into checkout page.
	 *
	 * @since    1.0.0
	 * @param    object $checkout   Checkout data.
	 */
	public function add_custom_field_nif_checkout( $checkout ) {

        // If user already exists we need to get the value requested during a previous order.
        $current_user = wp_get_current_user();

		if ( isset($current_user) && $current_user->NIF ) {
			$saved_nif = $current_user->NIF;
		} else {
			$saved_nif = '';
		}

		$config_company_identifier_name = null;
		$contasimple_admin = Contasimple_Admin::getInstance();

		if ( $contasimple_admin->properly_configured() ) {
			$contasimple_admin = Contasimple_Admin::getInstance();
			$config_company_identifier_name = $contasimple_admin->get_company_identifier_name();
		}

		if ( !empty( get_option( 'woocommerce_integration-contasimple_settings' ) )
			&& !empty( get_option( 'woocommerce_integration-contasimple_settings' )['nif_custom_text'])
		) {
			$nif_label = get_option( 'woocommerce_integration-contasimple_settings' )['nif_custom_text'];
		} elseif ( !empty( $config_company_identifier_name ) ) {
			$nif_label = sprintf( __('%s / Company identifier', 'contasimple'), $config_company_identifier_name );
		} else {
			$nif_label = __( 'Company identifier', 'contasimple' );
		}

		if ( ! empty( get_option( 'woocommerce_integration-contasimple_settings' ) )
			&& isset( get_option( 'woocommerce_integration-contasimple_settings' )['nif_required'] )
			&& 'yes' === get_option( 'woocommerce_integration-contasimple_settings' )['nif_required'] ) {
			$nif_required = true;
		} else {
			$nif_required = false;
		}

		echo '<div id="additional_checkout_field">';

		woocommerce_form_field( 'nif', array(
			'type'        => 'text',
			'class'       => array( 'my-field-class form-row-wide' ),
			'required'    => $nif_required,
			'label'       => $nif_label,
            'default'     => $saved_nif,
			'placeholder' => __( 'Example: 12345678X' ),
		), $checkout->get_value( 'nif' ));

		echo '</div>';
	}

	/**
	 * Saves the NIF value into the order meta
	 *
	 * @param int $order_id The order id.
	 */
	public function add_custom_field_nif_checkout_update_order( $order_id ) {
		if ( isset( $_POST['nif'] ) && '' !== $_POST['nif'] ) { // Input var okay.
            Contasimple_WC_Backward_Compatibility::update_order_meta( $order_id, 'NIF', sanitize_text_field( wp_unslash( $_POST['nif'] ) ), null,true ); // Input var okay.
		}
	}

    /**
     * Saves the NIF value into the user meta
     *
     * We also need to save it into the user meta just in case the user buys again, it's easier to retrieve the NIF
     * from the user data than searching for a previous order.
     *
     * @param int $order_id The order id.
     */
    public function add_custom_field_nif_checkout_update_user( $user_id ) {
        if ( $user_id && isset( $_POST['nif'] ) && '' !== $_POST['nif'] ) { // Input var okay.
            update_user_meta( $user_id, 'NIF', sanitize_text_field( wp_unslash( $_POST['nif'] ) ) ); // Input var okay.
        }
    }

	/**
	 * Validates customer NIF during checkout
	 *
	 * Empty NIF is allowed; if NIF has a value, it is checked against Contasimple's defined country format,
	 * by using REGEX. Since the allowed countries might be updated, the list is fetched via the CS API, but we will use
	 * WP transients to catch the results based on a custom constant 'COUNTRIES_EXPIRATION_IN_SECONDS' defined in
	 * Config.php
	 *
	 * @throws Exception If something goes wrong.
	 */
	public function validate_valid_nif() {
		// Get from POST and filter
		$nif = filter_input( INPUT_POST, 'nif' );

		$config_company_identifier_name = null;
		$contasimple_admin = Contasimple_Admin::getInstance();

		if ( $contasimple_admin->properly_configured() ) {
			$contasimple_admin = Contasimple_Admin::getInstance();
			$config_company_identifier_name = $contasimple_admin->get_company_identifier_name();
		}

		if ( !empty( $config_company_identifier_name ) ) {
			$nif_warning = sprintf( __('<b>%s / Company identifier</b> is a required field.', 'contasimple'), $config_company_identifier_name );
		} else {
			$nif_warning = __( '<b>Company identifier</b> is a required field.', 'contasimple' );
		}

		if ( ! empty( get_option( 'woocommerce_integration-contasimple_settings' ) )
			&& isset( get_option( 'woocommerce_integration-contasimple_settings' )['nif_required'] )
			&& 'yes' === get_option( 'woocommerce_integration-contasimple_settings' )['nif_required'] ) {
			$nif_required = true;
		} else {
			$nif_required = false;
		}

		if ( empty( $nif ) && $nif_required && $contasimple_admin->properly_configured()) {
			wc_add_notice( $nif_warning, 'error' );
		}

		// No NIF entered is fine, without valid fiscal data it will be dealt as a simplified invoice (ticket).
		if ( ! empty( $nif ) && $contasimple_admin->properly_configured() ) {
			// But if a NIF is entered, we must check if the format is valid.
			try {
                $contasimple_admin = Contasimple_Admin::getInstance();
                $countries = $contasimple_admin->get_cached_cs_countries();

				// Customer's country ISO code comes in this POST field, fortunately it's present in both sides
				// so we can use it to compare CS vs WC.
				$customer_country = filter_input( INPUT_POST, 'billing_country' );

				foreach ( $countries as $country ) {
					if ( $country->getIsoCodeAlpha2() == $customer_country ) {
						$pattern = '/' . $country->getNifValidationRegex() . '/';

						if ( ! preg_match( $pattern, $nif ) ) {
							$common_message = __( 'The format of the company identifier number entered is not correct for the selected billing country.', 'contasimple' );

							// Give a little bit more descriptive message for the most typical countries.
							switch ( $customer_country ) {
								case 'ES':
									$common_message .= ' ' . __( 'For Spain, only letters and numbers are allowed,
									without hyphens, periods or spaces. Correct formats: 00000000X / X0000000Y /
									X00000000', 'contasimple' );
									break;
								// TODO Add more cases as needed.
							}

							// In WooCommerce, this method allow us to output an error message
							// and locks the user into the checkout screen until fulfilled.
							wc_add_notice( $common_message, 'error' );
						}

						break;
					}
				}
			} catch ( Exception $e ) {
				// We cannot break the checkout process, but log any issue to keep track.
				$this->logger = CSLogger::getDailyLogger();
				$this->logger->logTransactionStart( 'Called: ' . __METHOD__ );
				$this->logger->logTransactionEnd( 'Exception thrown trying to validate customer NIF: ' . $e->getMessage() );
			}
		}
	}
}
