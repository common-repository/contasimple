<?php

/**
 * Helper functions for the Contasimple plugin
 *
 * @link       http://www.contasimple.com
 * @since      1.10.0
 *
 * @package    contasimple
 * @subpackage contasimple/includes
 * @author     Contasimple S.L. <soporte@contasimple.com>
 */
class Contasimple_WC_Helpers {

	/**
	 * Validate that both CS and WC currencies are equivalent.
	 *
	 * @param $wc_order_currency_iso string     The WC order currency iso-3 code. Ex: "EUR"
	 * @param $wc_order_currency_symbol string  The WC order currency symbol. Ex: "€"
	 * @param $cs_currency_symbol string        The CS currency symbol as defined in company extra information. Ex: "€"
	 * @throws Exception If currencies do not match.
	 */
	public static function validate_equivalent_currencies( $wc_order_currency_iso, $wc_order_currency_symbol, $cs_currency_symbol ) {

		// Best case scenario: both symbols are equal (ie: € and €)
		if ( $cs_currency_symbol == $wc_order_currency_symbol ) {
			return;
		}

		// If that's not the case it can still be correct to allow the sync, for example sometimes an html entity (ex: '&euro;')
		// makes it here due to some bug or wrong charset. Let's perform some custom checks:

		// Both CS and WC are euros.
		if (
			( $wc_order_currency_iso == "EUR" || $wc_order_currency_symbol == "€" || $wc_order_currency_symbol == "&euro;" || $wc_order_currency_symbol == "&#x20AC;" )
			&&
			( $cs_currency_symbol == "EUR" || $cs_currency_symbol == "€" || $cs_currency_symbol == "&euro;" || $cs_currency_symbol == "&#x20AC;" )
		) {
			return;
		}

		// Both are UK pounds.
		if (
			( $wc_order_currency_iso == "GBP" || $wc_order_currency_symbol == "£" || $wc_order_currency_symbol == "&pound;" || $wc_order_currency_symbol == "&#xA3;" )
			&&
			( $cs_currency_symbol == "GBP" || $cs_currency_symbol == "£" || $cs_currency_symbol == "&pound;" || $cs_currency_symbol == "&#xA3;" )
		) {
			return;
		}

		// TODO Place here other custom currency checks.

		// Otherwise do not allow the sync process to continue.
		throw new \Exception( 'Currency symbol of the WC order is ' . $wc_order_currency_symbol . ', currency symbol of the CS fiscal region is ' . $cs_currency_symbol, INVALID_CURRENCY );
	}

	/**
	 * Gets the Contasimple locale equivalent to the currently set Wordpress locale.
	 * This is needed because Contasimple has fewer supported locales and with different ISO code formatting.
	 *
	 * @return string The most fitting locale to use in Contasimple. Example: 'es-ES'
	 */
	public static function get_contasimple_equivalent_current_wp_locale() {

		switch (get_locale()) {

			case 'es_ES':
				$cs_language = 'es-ES';
				break;

			default:
				$cs_language = 'en-US';
				break;
		}

		return $cs_language;
	}
}
