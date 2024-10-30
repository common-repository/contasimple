<?php
/**
 * Helper class for the Contasimple API
 *
 * This class defines a few constants and exposes a few config parameters which make it easier to work with user account
 * settings, mainly the API session data.
 *
 * @link       https://wordpress.org/plugins/contasimple/
 * @since      1.0.0
 *
 * @package    contasimple
 * @subpackage contasimple/includes/common
 * @author     Contasimple S.L. <soporte@contasimple.com>
 */

use \Contasimple\Swagger\Client\Configuration;

//define('CS_ENV', 'prelive');
define('CS_ENV', 'live');

switch (CS_ENV) {
	case 'live':
		define('URL_CS_API', 'https://api.contasimple.com');
		define('URL_CS_ROOT', 'https://www.contasimple.com/');
		break;

	case 'prelive':
		define('URL_CS_API', 'https://api.prelive.contasimple.com');
		define('URL_CS_ROOT', 'https://prelive.contasimple.com/');
		break;
}

define('URL_CS_WEB_SUMMARY', URL_CS_ROOT . 'Modulos/Contabilidad/Resumen/Default.aspx');
define('URL_CS_WEB_EDIT_INVOICE', URL_CS_ROOT . 'Modulos/Contabilidad/RegistroDeFacturasEmitidas/EditarFactura.aspx');
define('URL_CS_UPGRADE', URL_CS_ROOT . 'Modulos/Planes.aspx');
define('URL_CS_CONTACT', URL_CS_ROOT . 'contacto.aspx');
define('URL_CS_REGISTER', URL_CS_ROOT . 'alta-nuevo-usuario.aspx?promocode=FF5000');
define('CS_MAX_AMOUNT_DEVIATION', 0.01);
define('CS_MAX_DECIMALS', 2);

const MAX_SYNC_ATTEMPTS = 3;
const HOURS_TO_SYNC_AGAIN = 24;
const CURL_TIMEOUT = 30; // in seconds
const COUNTRIES_EXPIRATION_IN_SECONDS = 86400;

/*
 * Configuration Class
 *
 * This class closes the bridge between the API and WooCommerce.
 * It extends the Swagger auto-generated Configuration class, which brings access to parameters from the CS API like the host url, tokens, etc.
 * Its also facilitates access to a few parameters that WooCommerce will need to keep track.
 */
class CSConfig extends \Contasimple\Swagger\Client\Configuration
{
	// Swagger codegen does not create a field + setters/getters for the refresh token in config class
	protected $refreshToken;
	protected $expireTime;
	protected $companyId;
	protected $clientId;
	protected $clientSecret;
	protected $lastAutoSync;
	protected $currencyISOCode;
	protected $currencySymbol;
	protected $countryISOCode;
	protected $fiscalRegionCode;
	protected $vatName;
	protected $invoiceCulture;
	protected $paymentEquivalences;
	protected $enableLogs;
	protected $showWarnings;

	/*
	 * The original Swagger codegen set the URL directly on the parameter $host in the middle of the code and it was difficult to find when needed.
	 * We will define a constant in the uppermost section of this file and just set it on the constructor, to keep configuration centralized.
	 */
	public function __construct() {

		$this->host = URL_CS_API;
		$this->curlTimeout = CURL_TIMEOUT;

		parent::__construct();
	}

	/**
	 * @return mixed
	 */
	public function getPaymentEquivalences()
	{
		return $this->paymentEquivalences;
	}

	/**
	 * @param mixed $paymentEquivalences
	 */
	public function setPaymentEquivalences($paymentEquivalences)
	{
		$this->paymentEquivalences = $paymentEquivalences;
	}

	/**
	 * @return mixed
	 */
	public function getVatName()
	{
		return $this->vatName;
	}

	/**
	 * @param mixed $vatName
	 */
	public function setVatName($vatName)
	{
		$this->vatName = $vatName;
	}

	/**
	 * @return mixed
	 */
	public function getInvoiceCulture()
	{
		return $this->invoiceCulture;
	}

	/**
	 * @param mixed $invoiceCulture
	 */
	public function setInvoiceCulture($invoiceCulture)
	{
		$this->invoiceCulture = $invoiceCulture;
	}

	/**
	 * @return mixed
	 */
	public function getRefreshToken()
	{
		return $this->refreshToken;
	}

	/**
	 * @param mixed $refreshToken
	 */
	public function setRefreshToken($refreshToken)
	{
		$this->refreshToken = $refreshToken;
	}

	/**
	 * @return mixed
	 */
	public function getExpireTime()
	{
		return $this->expireTime;
	}

	/**
	 * @param mixed $expireTime
	 */
	public function setExpireTime($expireTime)
	{
		$this->expireTime = $expireTime;
	}

	/**
	 * @return mixed
	 */
	public function getCompanyId()
	{
		return $this->companyId;
	}

	/**
	 * @param mixed $companyId
	 */
	public function setCompanyId($companyId)
	{
		$this->companyId = $companyId;
	}

	/**
	 * @return mixed
	 */
	public function getClientId()
	{
		return $this->clientId;
	}

	/**
	 * @param mixed $clientId
	 */
	public function setClientId($clientId)
	{
		$this->clientId = $clientId;
	}

	/**
	 * @return mixed
	 */
	public function getClientSecret()
	{
		return $this->clientSecret;
	}

	/**
	 * @param mixed $clientSecret
	 */
	public function setClientSecret($clientSecret)
	{
		$this->clientSecret = $clientSecret;
	}

	/**
	 * @return mixed
	 */
	public function getLastAutoSync()
	{
		return $this->lastAutoSync;
	}

	/**
	 * @param mixed $lastSync
	 */
	public function setLastAutoSync($lastSync)
	{
		$this->lastAutoSync = $lastSync;
	}

	/**
	 * @return mixed
	 */
	public function getCurrencyISOCode()
	{
		return $this->currencyISOCode;
	}

	/**
	 * @param mixed $currencyISOCode
	 */
	public function setCurrencyISOCode($currencyISOCode)
	{
		$this->currencyISOCode = $currencyISOCode;
	}

	/**
	 * @return mixed
	 */
	public function getCurrencySymbol()
	{
		return $this->currencySymbol;
	}

	/**
	 * @param mixed $currencyISOCode
	 */
	public function setCurrencySymbol($currencySymbol)
	{
		$this->currencySymbol = $currencySymbol;
	}

	/**
	 * @return mixed
	 */
	public function getCountryISOCode()
	{
		return $this->countryISOCode;
	}

	/**
	 * @param mixed $countryISOCode
	 */
	public function setCountryISOCode($countryISOCode)
	{
		$this->countryISOCode = $countryISOCode;
	}

	/**
	 * @return mixed
	 */
	public function getFiscalRegionCode()
	{
		return $this->fiscalRegionCode;
	}

	/**
	 * @param mixed $fiscalRegionCode
	 */
	public function setFiscalRegionCode($fiscalRegionCode)
	{
		$this->fiscalRegionCode = $fiscalRegionCode;
	}

	/**
	 * @return mixed
	 */
	public function getEnableLogs()
	{
		if (empty($this->enableLogs)) {
			return true; // default
		} else {
			return $this->enableLogs;
		}
	}

	/**
	 * @param mixed $fiscalRegionCode
	 */
	public function setEnableLogs($enableLogs)
	{
		$this->enableLogs = $enableLogs;
	}

	/**
	 * @return mixed
	 */
	public function getShowWarnings()
	{
		if (empty($this->showWarnings)) {
			return true; // default
		} else {
			return $this->showWarnings;
		}
	}

	/**
	 * @param mixed $fiscalRegionCode
	 */
	public function setShowWarnings($showWarnings)
	{
		$this->showWarnings = $showWarnings;
	}
}
