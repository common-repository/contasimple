<?php
/**
 * 2022 Contasimple Integrations
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade your CMS to newer
 * versions in the future.
 *
 *  @author    Contasimple S.L. <soporte@contasimple.com>
 *  @copyright Contasimple S.L.
 *  @license   All rights reserved
 *  International Registered Trademark & Property of Contasimple S.L.
 */

namespace Contasimple\Plugins\Common;

use Contasimple\Plugins\Common as CS;
use Contasimple\Swagger\Client\Model\InvoiceApiModel;
use Contasimple\Swagger\Client\Model\FiscalRegionApiModel;

class InvoiceHelper {

	/**
	 * Determines the operation type of the invoice, based on a combination of many input parameters.
	 * The possible operation types are defined in the InvoiceApiModel class.
	 *
	 * @param string $nif The NIF/DNI of the customer/company.
	 * @param string $vatNumber The European VAT number of the company (to check with the VIES service).
	 * @param string $countryIsoCode The iso alpha-2 code of the customer country. Example: 'ES'.
	 * @param string $stateIsoCode The iso alpha2 code of the customer region/province. Example: 'B' (Barcelona).
	 * @param string $issuerCountryIsoCode The iso alpha-2 code of the CS issuer entity. Ex: 'ES'.
	 * @param string $issuerFiscalRegionCode The Contasimple specific code of the issuer fiscal region. Ex: 'es_Canarias'.
	 * @param bool   $hasTaxes A boolean indicating if the order applied any taxes (true) or not (false).
	 *
	 * @return string The operation type of the invoice.
	 * @throws \Exception If the operation type cannot be determined from the given parameters.
	 *
	 * @see InvoiceApiModel
	 * @see IRegionEquivalences
	 *
	 */
	public static function getOperationType(
		$nif,
		$vatNumber,
		$countryIsoCode,
		$stateIsoCode,
		$issuerCountryIsoCode,
		$issuerFiscalRegionCode,
		$hasTaxes
	)
	{
		// Check if the customer and invoice issuer countries and region are the same.
		$countriesMatch = ($countryIsoCode == $issuerCountryIsoCode);
		$regionsMatch = self::sameCountryRegionsMatch($countryIsoCode, $stateIsoCode, $issuerFiscalRegionCode);

		// If the invoice has any taxes, then it is considered a national operation type
		// (otherwise it should not have VAT applied and would be an exportation/intracommunitary etc).
		if ($hasTaxes)
			return InvoiceApiModel::OPERATION_TYPE_NACIONAL;

		// If there is no NIF or VAT number, then there is no official target customer, the aggregated customer will be
		// used. This mimics a simplified invoice and the operation is considered to have place in the fiscal region
		// of the company, thus is considered a national operation type.
		if (empty($nif) && empty($vatNumber)) {
			if (self::isFiscalRegionEsPeninsula($issuerFiscalRegionCode))
				return InvoiceApiModel::OPERATION_TYPE_NACIONAL_EXENTA;
			else
				return InvoiceApiModel::OPERATION_TYPE_NACIONAL;
		}

		// If there is a customer NIF and the invoice does not have any taxes it probably is an exportation,
		// but there are a couple of scenarios here:

		// If countries are different and not from the EU, then there is no possibility of an intracomunitary invoice,
		// so it must be an exportation.
		if (!$countriesMatch && !(self::areBothCountriesInIntracomVAT($issuerFiscalRegionCode, $countryIsoCode, $stateIsoCode)))
			return InvoiceApiModel::OPERATION_TYPE_EXPORTACION;

		// However, if there's a VAT and countries are from the EU VAT zone, it might be an intracomunitary operation,
		// but only if vat number is valid according to the VIES service.
		// Note: technically the issuer should also be registered in the VIES but right now Contasimple does not ask
		// for this so neither will be the plugin, beware though.
		if (!$countriesMatch && self::areBothCountriesInIntracomVAT($issuerFiscalRegionCode, $countryIsoCode, $stateIsoCode)) {
			if (empty($vatNumber))
				$vatNumber = $nif; // Try to use the nif if the VAT is not supplied, never know what the user is entering.

			if (self::isVatNumberValidFromVIES($vatNumber)) {
				return InvoiceApiModel::OPERATION_TYPE_INTRACUMUNITARIA;
			} else {
				if (self::isFiscalRegionEsPeninsula($issuerFiscalRegionCode))
					return InvoiceApiModel::OPERATION_TYPE_NACIONAL_EXENTA;
				else
					return InvoiceApiModel::OPERATION_TYPE_NACIONAL;
			}
		}

		// If countries match and regions are equivalent, then it is a national operation.
		// We should warn the user as this kind of invoice should have taxes applied, unless it is an exempt activity,
		// which we don't know. Since 90% of eCommerces sell goods and not services, we are not going to delve into this.
		if ($countriesMatch && $regionsMatch) {
			if (self::isFiscalRegionEsPeninsula($issuerFiscalRegionCode))
				return InvoiceApiModel::OPERATION_TYPE_NACIONAL_EXENTA;
			else
				return InvoiceApiModel::OPERATION_TYPE_NACIONAL;
		}

		// If the country matches but region does not, it must be something like Península <> Canarias.
		// This is an exportation.
		if ($countriesMatch && !$regionsMatch) {
			return InvoiceApiModel::OPERATION_TYPE_EXPORTACION;
		}

		// Should not get here, but if none of the previous scenarios work we better do not allow the invoice to sync.
		throw new \Exception('', OPERATION_TYPE_TOO_COMPLEX);
	}

	/**
	 * Gets any invoice warnings if the invoice has invalid or suspicious VAT rates applied, given the issuer fiscal
	 * region and the target country and state invoicing data.
	 *
	 * @param string $nif The NIF/DNI of the customer/company.
	 * @param string $vatNumber The European VAT number of the company (to check with the VIES service).
	 * @param string $countryIsoCode The iso alpha-2 code of the customer country. Example: 'ES'.
	 * @param string $stateIsoCode The iso alpha2 code of the customer region/province. Example: 'B' (Barcelona).
	 * @param string $issuerCountryIsoCode The iso alpha-2 code of the CS issuer entity. Ex: 'ES'.
	 * @param string $issuerFiscalRegionCode The Contasimple specific code of the issuer fiscal region. Ex: 'es_Canarias'.
	 * @param bool   $hasTaxes A boolean indicating if the order applied any taxes (true) or not (false).
	 *
	 * @return int|null
	 * @throws \Exception If the VIES validation service is down.
	 */
	public static function getVatWarnings(
		$nif,
		$vatNumber,
		$countryIsoCode,
		$stateIsoCode,
		$issuerCountryIsoCode,
		$issuerFiscalRegionCode,
		$hasTaxes
	)
	{
		if (empty($vatNumber))
			$vatNumber = $nif; // Try to use the nif if the VAT is not supplied, never know what the user is entering.

		$countriesMatch = ($countryIsoCode == $issuerCountryIsoCode);
		$regionsMatch = self::sameCountryRegionsMatch($countryIsoCode, $stateIsoCode, $issuerFiscalRegionCode);

		$bothCountriesInIntracomVAT = self::areBothCountriesInIntracomVAT(
			$issuerFiscalRegionCode,
			$countryIsoCode,
			$stateIsoCode
		);

		if (!$hasTaxes && !$countriesMatch && $bothCountriesInIntracomVAT && $vatNumber != null) {
			if (!self::isVatNumberValidFromVIES($vatNumber))
				return SYNCED_WITH_INVALID_VAT_NUMBER;
		}

		if (!$hasTaxes && $countriesMatch && $regionsMatch)
			return SYNCED_NATIONAL_WITHOUT_TAXES;

		return null;
	}

	/**
	 * Returns if the invoice has any taxes applied.
	 *
	 * @param array $CSInvoice The CS invoice as an array of data.
	 *
	 * @return bool true if it has taxes, false otherwise.
	 */
	public static function invoiceHasTaxes($CSInvoice)
	{
		if (empty($CSInvoice) || !array_key_exists('lines', $CSInvoice))
			return false;

		foreach ($CSInvoice['lines'] as $line) {
			if ($line['vatPercentage'] > 0)
				return true;
		}

		return false;
	}

	/**
	 * Check VAT number against VIES.
	 *
	 * Inspired by PrestaShop European VAT Module.
	 *
	 * @param string $vat_number    The VAT number to check. Ex: FR12345678901.
	 * @return bool                 true if VAT number is valid, false otherwise.
	 *
	 * @throws \Exception           If web request times out.
	 */
	public static function isVatNumberValidFromVIES($vat_number) {
		if (empty($vat_number)) {
			return false;
		}

		$vat_number = str_replace(' ','', $vat_number);
		$prefix     = substr($vat_number, 0, 2);

		if (array_search( $prefix, self::getPrefixIntracomVAT()) === false) {
			return false;
		}

		$vat = substr($vat_number, 2);
		$url = 'https://ec.europa.eu/taxation_customs/vies/rest-api/ms/%s/vat/%s?requesterMemberStateCode=%s&requesterNumber=%s';
		$url = sprintf($url, urlencode($prefix), urlencode($vat), 'ES', 'B67119594');

		@ini_set('default_socket_timeout',2);

		for ($i = 0; $i < 3; $i ++) {
			if ($page_res = file_get_contents($url)) {
				if (preg_match('/"isValid"\s*:\s*false/i', $page_res)) {
					@ini_restore('default_socket_timeout');

					return false;
				} else if (preg_match('/"isValid"\s*:\s*true/i', $page_res)) {
					@ini_restore('default_socket_timeout');

					return true;
				} else {
					++ $i;
				}
			} else {
				sleep(1);
			}
		}

		@ini_restore('default_socket_timeout');
		throw new \Exception('', VIES_TIMEOUT);
	}

	/**
	 * Checks if the eCommerce customer region code and the Contasimple fiscal region code are equivalent.
	 * This is needed to determine the operation type of the invoice when the issuer and receiver country are the same,
	 * but there is a chance that intra-country special rules apply (ex: España península <> Canarias).
	 *
	 * Note: Since we cannot right now have all country <> region equivalences worldwide, we will asume that any
	 * entry not found in this algorithm pertains to the same region, so that an invoice from the same country and
	 * unknown region equivalence can be set as 'Nacional'.
	 *
	 * @see IRegionEquivalences
	 * @see ContasimpleHelper::getOperationType()
	 *
	 * @param string $customerCountryIsoCode   The customer country iso2- code. Ex: 'ES' (Spain).
	 * @param string $customerStateIsoCode     The customer state iso-2 code. Ex: 'TF' (Tenerife in Canary islands).
	 * @param string $issuerFiscalRegionCode   The Contasimple issue fiscal region code that should be equivalent. Ex: 'es_Canarias'.
	 *
	 * @return bool true if the regions match, false otherwise.
	 */
	public static function sameCountryRegionsMatch($customerCountryIsoCode, $customerStateIsoCode, $issuerFiscalRegionCode)
	{
		$customerCountryIsoCode = strtoupper($customerCountryIsoCode);
		$customerStateIsoCode   = strtoupper($customerStateIsoCode);

		// Set any other non-canary or non-ceuta-melilla case as a wildcard to simplify things.
		if ($customerCountryIsoCode === 'ES' && !in_array($customerStateIsoCode, array('TF', 'GC', 'CE', 'ML'))) {
			$customerStateIsoCode = '*';
		}

		$arrayKey = $customerCountryIsoCode . '-' . $customerStateIsoCode;

		$equivalencyArray = array(
			'ES-TF' => 'es_Canarias', // 'España - Canarias'
			'ES-GC' => 'es_Canarias', // 'España - Canarias'
			'ES-CE' => 'es_CeutaYMelilla', // 'España - Ceuta y Melilla'
			'ES-ML' => 'es_CeutaYMelilla', // 'España - Ceuta y Melilla'
			'ES-*' => 'es_Peninsula', // España - Península y Baleares
		);

		// What about non contemplated cases?

		// If fiscal region is 'Otra', ignore this and return 'true', just compare country against country.
		if ($issuerFiscalRegionCode == FiscalRegionApiModel::CODE_OTRA)
			return true;

		// If there is no defined equivalency between the given state and CS fiscal region, then return also 'true'
		// as the default option as the most common scenario is that if the country is the same,
		// then the operation is 'National' (at least in Spain).
		if (!isset($equivalencyArray[$arrayKey]))
			return true;

		// Otherwise if we have a mapping, then check if the regions DO actually match.
		return $equivalencyArray[$arrayKey] === $issuerFiscalRegionCode;
	}

	/**
	 * Returns a list of valid European VAT country prefixes.
	 * Taken from the PS vatnumber module.
	 *
	 * @return array
	 */
	public static function getPrefixIntracomVAT()
	{
		$intracom_array = array(
			'AT' => 'AT',
			//Austria
			'BE' => 'BE',
			//Belgium
			'DK' => 'DK',
			//Denmark
			'FI' => 'FI',
			//Finland
			'FR' => 'FR',
			//France
			'FX' => 'FR',
			//France métropolitaine
			'DE' => 'DE',
			//Germany
			'GR' => 'EL',
			//Greece
			'IE' => 'IE',
			//Irland
			'IT' => 'IT',
			//Italy
			'LU' => 'LU',
			//Luxembourg
			'NL' => 'NL',
			//Netherlands
			'PT' => 'PT',
			//Portugal
			'ES' => 'ES',
			//Spain
			'SE' => 'SE',
			//Sweden
			'GB' => 'GB',
			//United Kingdom
			'CY' => 'CY',
			//Cyprus
			'EE' => 'EE',
			//Estonia
			'HU' => 'HU',
			//Hungary
			'LV' => 'LV',
			//Latvia
			'LT' => 'LT',
			//Lithuania
			'MT' => 'MT',
			//Malta
			'PL' => 'PL',
			//Poland
			'SK' => 'SK',
			//Slovakia
			'CZ' => 'CZ',
			//Czech Republic
			'SI' => 'SI',
			//Slovenia
			'RO' => 'RO',
			//Romania
			'BG' => 'BG',
			//Bulgaria
			'HR' => 'HR',
			//Croatia
		);

		return $intracom_array;
	}

	/**
	 * Given iso-2 country and state codes, returns if the country pertains to the European VAT zone.
	 * Note: The state code is used to account for special scenarios inside pertaining countries where this does not
	 * apply. Example: Spain ('ES') is in the VAT zone, however applies to Barcelona ('B') but not Tenerife ('TF').
	 *
	 * @param $isoCountryCode
	 * @param $stateCode
	 *
	 * @return bool
	 */
	public static function isCountryInIntracomVAT($isoCountryCode, $stateCode)
	{
		$isoCountryCode = strtoupper($isoCountryCode);
		$stateCode = strtoupper($stateCode);
		$fullCode = $isoCountryCode . '-' . $stateCode;

		$prefixes = self::getPrefixIntracomVAT();
		$exceptions = array('ES-TF', 'ES-GC', 'ES-CE', 'ES-ML');

		if (isset($prefixes[$isoCountryCode]) && !in_array($fullCode, $exceptions)) {
			return true;
		}

		return false;
	}

	/**
	 * Given a Contasimple fiscal region, returns if this region can apply to Intracommunitary invoices.
	 */
	public static function isFiscalRegionInIntracomVAT($fiscalRegion)
	{
		// Right now only España - Península has the intracommunitary invoice type as it is the only fiscal
		// region in European VAT at the moment.
		return self::isFiscalRegionEsPeninsula($fiscalRegion);
	}

	/**
	 * Checks if both countries pertain to the intracomunitary VAT zone.
	 * Note: Issuer is always the Contasimple fiscal region of the eCommerce integration.
	 *       Receiver is always the eCommerce customer based on the billing address provided during checkout.
	 *
	 * @param $issuerFiscalRegion
	 * @param $isoCountryCode
	 * @param $isoStateCode
	 *
	 * @return bool true if both issuer and receiver are in the VAT intracomunitary zone, false otherwise.
	 */
	public static function areBothCountriesInIntracomVAT($issuerFiscalRegion, $isoCountryCode, $isoStateCode)
	{
		return
			self::isFiscalRegionInIntracomVAT($issuerFiscalRegion) &&
			self::isCountryInIntracomVAT($isoCountryCode, $isoStateCode);
	}

	public static function isFiscalRegionEsPeninsula($fiscalRegion) {
		return $fiscalRegion == FiscalRegionApiModel::CODE_ES_PENINSULA;
	}
}
