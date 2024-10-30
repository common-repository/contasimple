<?php
/**
 * Facade class for the Contasimple API
 *
 * This file contains a facade class which makes it easier to communicate with the CS Rest APÎ.
 *
 * @link       https://wordpress.org/plugins/contasimple/
 * @since      1.0.0
 *
 * @package    contasimple
 * @subpackage contasimple/includes/common
 * @author     Contasimple S.L. <soporte@contasimple.com>
 */

use \Contasimple\Swagger\Client\ApiClient as ContasimpleApiClient;
use \Contasimple\Swagger\Client\ApiException as ContasimpleApiException;
use \Contasimple\Swagger\Client\API\AuthenticationApi as ContasimpleAuthenticationApi;
use \Contasimple\Swagger\Client\API\MeCompaniesApi as ContasimpleMeCompaniesApi;
use \Contasimple\Swagger\Client\API\EntitiesCustomersApi as ContasimpleEntitiesCustomersApi;
use \Contasimple\Swagger\Client\API\EntitiesCustomersAggregatedApi as ContasimpleEntitiesCustomersAggregatedApi;
use \Contasimple\Swagger\Client\API\AccountingInvoicesIssuedApi as ContasimpleAccountingInvoicesIssuedApi;
use \Contasimple\Swagger\Client\API\AccountingInvoicesIssuedPaymentsApi as ContasimpleAccountingInvoicesIssuedPaymentsApi;
use \Contasimple\Swagger\Client\API\ConfigurationPaymentMethodsApi as ContasimpleConfigurationPaymentMethodsApi;
use \Contasimple\Swagger\Client\API\ConfigurationCommonApi as ContasimpleConfigurationCommonApi;
use \Contasimple\Swagger\Client\API\ConfigurationNumberingFormatsApi as ContasimpleConfigurationNumberingFormatsApi;
use \Contasimple\Swagger\Client\Model\AuthenticationResponse;
use \Contasimple\Swagger\Client\Model\ConfigureCompanyApiModel;
use \Contasimple\Swagger\Client\Model\CreateIssuedInvoiceApiModel;
use \Contasimple\Swagger\Client\Model\DocumentPaymentApiModel;
use \Contasimple\Swagger\Client\Model\EntityApiModel;
use \Contasimple\Swagger\Client\Model\InvoiceApiModel;
use \Contasimple\Swagger\Client\Model\InvoicePaymentRequestApiModel;
use \Contasimple\Swagger\Client\Model\UpdateIssuedInvoiceApiModel;

const CS_EOL = "\r\n";
const CS_ROUNDING_THRESHOLD = 0.01;

// We ignore API textual error messages and we define custom constants for every scenario.
// This way the consuming PHP modules for each webapp can use the constants to define their own error messages
// based on the application reporting style, multi-language needed or not, etc.

const NOT_SYNC = 0;
const PENDING = 1;
const SYNC_OK = 2;
const CHANGED = 3;
const PAUSED = 4;
// 5 to 9 reserved for future potential 'non-error' states
const SYNCED_WITH_INVALID_VAT_NUMBER = 5;
const SYNCED_NATIONAL_WITHOUT_TAXES = 6;
const SYNCED_WITH_ROUNDING_ISSUES = 7;
const SYNCED_WITH_DISCREPANCY = 8;
// Generic error
const SYNC_ERROR = 10;
// Greater than 10 are all detailed errors
const INVOICE_REPEATED = 11;
const INVALID_MODEL = 12;
const GENERIC_ERROR = 13;
const TOTAL_AMOUNT_INVALID = 14;
const MAX_ATTEMPS_REACHED = 15;
const PAYMENT_SYNC_ERROR = 16;
const PAYMENT_TOO_COMPLEX = 17;
const INVALID_VAT = 18;
const PLAN_LIMIT_REACHED = 19;
const PAYMENT_DELETE_ERROR = 20;
const PAYMENT_RETRIEVAL_ERROR = 21;
const COMPANY_NIF_DISCREPANCY = 22;
const INVALID_LINES = 23;
const DELETE_ERROR = 24;
const MISSING_ORDER = 25;
const INVALID_NUMBER_DECIMALS = 26;
const ROUNDING_DISCREPANCY_ERROR = 27;
const CANNOT_FIND_SYNC_STRATEGY = 28;
const INVALID_CURRENCY = 29;
const TAXES_PER_LINE_TOO_COMPLEX = 30;
const OPERATION_TYPE_TOO_COMPLEX = 31;
const COUPON_TOTAL_AMOUNT_WITH_MORE_THAN_ONE_TAX = 32;
const ENTIDADES_COUNTRY_NIF_VALIDATION_ERROR_1 = 50;
const PLAN_LIMIT_REACHED_ENTITIES = 55;
const GET_COMPANIES_ERROR = 101;
const GET_CUSTOMER_ERROR = 105;
const AGGREGATED_CUSTOMER_NOT_FOUND = 110;
const AGGREGATED_CUSTOMER_CANNOT_CREATE = 111;
const CUSTOMER_CANNOT_CREATE = 150;
const VIES_TIMEOUT = 120;
const INVOICE_INSERT_PERIOD_CLOSED = 130;
const INVOICE_UPDATE_NEW_PERIOD_CLOSED = 131;
const INVOICE_UPDATE_ORIGINAL_PERIOD_CLOSED = 132;
const INVOICE_PAYMENT_INSERT_PERIOD_CLOSED = 133;
const PAYMENT_METHODS_RETRIEVAL_ERROR = 210;
const GET_COUNTRIES_ERROR = 220;
const NEXT_INVOICE_NUMBER_ERROR = 230;
const AUTH_DENIED = 401;
const HOST_UNREACHABLE = 504;
const SSL_CERT_PROBLEM = 510;
const INSUFFICIENT_RIGHTS_ERROR = 600;
const IRPF_PER_LINE_NOT_ALLOWED = 610;
const INVALID_DATE_COMPLETED = 620;
const DELETED_TAXES = 630;
const CANNOT_READ_COMPANY_DATA = 640;
const CANNOT_FIND_NUMBERING_SERIES = 650;
// ... add more as we find them relevant for the consuming apps

// This is a special type of error that allows us to bypass the Wordpress translations and print directly
// the error message returned by the API.
// It's a compromise in order to avoid changing all the code and start from scratch the error code management system.
const API_ERROR = 999;

/**
 * Class Service
 *
 * This class represents a bridge between consuming PHP web applications and the Contasimple API
 * It makes heavy use of the underlying Swagger Codegen auto-generated code to create HTTP requests
 * and simplifies the connection process for the end developer, providing a mechanism to handle
 * the connection details only once during the class instantiation.
 */
class CSService
{
	/**
	 * @var ContasimpleApiClient Instance of ContasimpleApiClient class. Allows to connect with CS API.
	 */
	protected $apiClient;

	/**
	 * @var CSConfig|mixed Instance of CSConfig class. Only for ease of access between methods. Persist config values
	 *                     via CSConfigManager interface.
	 */
	protected $config;

	/**
	 * @var CSConfigManager $configManager An instance of an ICSConfigManager interface that knows how to retrieve
	 *                                     the configuration from the DB depending on the ecommerce implementation.
	 */
	protected $configManager;


	/**
	 * CSService constructor.
	 *
	 * @param CSConfigManager $configManager An instance of a class implementing the CSConfigManager interface.
	 *                                       Deals with storing and restoring the access and refresh tokens
	 *                                       without coupling the implementation inside the service.
	 * @throws Exception
	 */
	public function __construct(CSConfigManager $configManager)
	{
		$this->configManager = $configManager;
		$this->config = $configManager->loadConfiguration();

		// If the module already has an active connection then check that the access token is not expired yet. If it is
		// expired, renew it here so that the subsequent API calls can be performed successfully.
		if ($this->config->getAccessToken() != null && $this->config->getRefreshToken() != null) {
			if ($this->config->getExpireTime() != null) {
				if (time() >= $this->config->getExpireTime()) {
					try {
						$this->renewAccessToken();
					} catch (\Exception $e) {
						//throw $e;
					}
				}
			}
		}

		$this->apiClient = new ContasimpleApiClient($this->config);
	}

	/**
	 * Calculates the new expire time from the authentication response.
	 * Typically the token will be valid for 1h.
	 *
	 * @param AuthenticationResponse $response The response we get if authentication is successful. It contains the
	 *                                         new expire time in seconds.
	 * @return int Unix epoch timestamp telling when the token expires next.
	 */
	protected function getNewExpireTime(AuthenticationResponse $response)
	{
		return time() + $response->getExpiresIn();
	}

	/**
	 * Renews access token.
	 *
	 * Since the access token expires frequently, we have to refresh it and store the new value in our config object.
	 * The refresh token is also susceptible of changing as well.
	 * This method deals with both and ensures that the API calls will be fulfilled.
	 *
	 * @throws Exception
	 */
	public function renewAccessToken()
	{
		try {
			$this->apiClient = new ContasimpleApiClient($this->config);
			$authApi = new ContasimpleAuthenticationApi($this->apiClient);

			// Request a new access token from the current refresh token.
			$response = $authApi->authenticationPost("2", "refresh_token", $this->config->getClientId(), $this->config->getClientSecret(), 'offline_access', $this->config->getRefreshToken());

			// Save new access token for further API calls.
			$this->config->setAccessToken($response->getAccessToken());
			$this->config->setRefreshToken($response->getRefreshToken());
			$this->config->setExpireTime($this->getNewExpireTime($response));
			$this->config->addDefaultHeader('Authorization', 'Bearer ' . $this->config->getAccessToken());

			// Persists the config in the client storage (ie: Database).
			$this->configManager->storeConfiguration($this->config);
		} catch (\Exception $e) {
			// It may happen that the refresh token is not valid as well, handle it for the user, try at least to reconnect again with the ApiKey.
			if (($e instanceof ContasimpleApiException) && (null !== $e->getResponseBody()) && ($e->getResponseBody()->errorCode == 'invalid_grant')) {
				try {
					$this->connect($this->config->getApiKey('apikey'));
				} catch (\Exception $e) {
					throw $e;
				}
			} else {
				return $this->handleCommonExceptions($e);
			}
		}
	}

	/**
	 * Connects to the Contasimple API.
	 *
	 * Connects to CS API via authentication with provided user ApiKey.
	 *
	 * @param string      $apikey    The ApiKey provided by CS to the end user/3rd party app.
	 * @param null|string $userAgent The user agent. Used to know from which integration we are connecting from.
	 *
	 * @return string    JSON encoded response with access token, expiration time, refresh token, Client ID and
	 *                   Client Secret.
	 * @throws Exception ApiException instance with error info in responseBody field, if could not connect.
	 *                   @see \Contasimple\Swagger\Client\ApiException in Swagger Codegen for more detail.
	 */
	public function connect($apikey, $userAgent = null)
	{
		try {
			$apiClient = new ContasimpleApiClient();
			$apiClient->getConfig()->setHost(URL_CS_API);
			$apiClient->getConfig()->setCurlTimeout(CURL_TIMEOUT);

			if (isset($userAgent)) {
				$apiClient->getConfig()->setUserAgent($userAgent);
			}

			$authApi = new ContasimpleAuthenticationApi($apiClient);
			$response = $authApi->authenticationPost("2", "authentication_key", null, null, 'offline_access', null, null, null, null, null, null, $apikey);

			$this->config->setApiKey('apikey', $apikey);
			$this->config->setUsername($response->getUsername());
			$this->config->setAccessToken($response->getAccessToken());
			$this->config->setRefreshToken($response->getRefreshToken());
			$this->config->setClientId($response->getClientId());
			$this->config->setClientSecret($response->getClientSecret());
			$this->config->addDefaultHeader('Authorization', 'Bearer ' . $this->config->getAccessToken());
			$this->config->setExpireTime($this->getNewExpireTime($response));

			if (isset($userAgent)) {
				$this->config->setUserAgent($userAgent);
			}

			$this->configManager->storeConfiguration($this->config);

			$this->apiClient = new ContasimpleApiClient($this->config);

			return $response;
		} catch (\Exception $e) {
			return $this->handleCommonExceptions($e);
		}
	}

	/**
	 * Gets user available companies.
	 *
	 * Obtains an array of Objects of type CompanyApiModel with getters/setters available for all company attributes.
	 * Ex: getId(), getOrganizationName().
	 *
	 * @see \Contasimple\Swagger\Client\Model\CompanyApiModel
	 *
	 * @return array     An array of CompanyApiModel object types.
	 * @throws Exception Exception with error message provided by the API responseBody if could not connect,
	 *                   ApiException is provided as the previous exception for more detail.
	 */
	public function getCompanies()
	{
		try {
			$companiesApi = new ContasimpleMeCompaniesApi($this->apiClient);
			$companies = $companiesApi->myCompaniesGetCompanies('2')->getData();
			return $companies;
		} catch (\Exception $e) {
			return $this->handleCommonExceptions($e);
		}
	}

	/**
	 * Gets the current company for the user.
	 *
	 * The user will have at least one active company in CS, which he can change. Gets the currently active one.
	 *
	 * @return \Contasimple\Swagger\Client\Model\CompanyApiModel
	 * @throws Exception
	 */
	public function getCurrentCompany()
	{
		try {
			$companiesApi = new ContasimpleMeCompaniesApi($this->apiClient);
			$company = $companiesApi->myCompaniesGetCurrent('2')->getData();
			return $company;
		} catch (\Exception $e) {
			return $this->handleCommonExceptions($e);
		}
	}

	/**
	 * Selects the active company for the user.
	 *
	 * Based on a previous requested list of available companies, the user can switch the desired working company by
	 * providing the company ID.
	 *
	 * @see getCompanies()
	 *
	 * @param int $companyId The company ID of the company to switch to.
	 *
	 * @return AuthenticationResponse  The response from the API, will contain a new pair of access/refresh tokens as
	 *                                 these are set at the company level.
	 * @throws Exception
	 */
	public function selectCompany($companyId)
	{
		try {
			$authApi = new ContasimpleAuthenticationApi($this->apiClient); // TODO verify that this is correct, until now we didn't pass the apiClient and seemed to work fine...
			$response = $authApi->authenticationPost("2", "change_company", $this->config->getClientId(), $this->config->getClientSecret(), 'offline_access', $this->config->getRefreshToken(), null, null, null, $companyId, null, null);

			// Changing company implies new refreshToken, save it now, otherwise if the code
			// breaks for some reason, reconnecting will trigger an 'invalid refresh token'
			// error.
			$this->config->setAccessToken($response->getAccessToken());
			$this->config->setRefreshToken($response->getRefreshToken());
			$this->config->setUsername($response->getUsername());
			$this->config->setExpireTime($this->getNewExpireTime($response));
			$this->config->setCompanyId($companyId);
			$this->config->addDefaultHeader('Authorization', 'Bearer ' . $this->config->getAccessToken());

			$this->configManager->storeConfiguration($this->config);

			$this->apiClient = new ContasimpleApiClient($this->config);

			return $response;
		} catch (\Exception $e) {
			return $this->handleCommonExceptions($e);
		}
	}

	/**
	 * Updates fiscal information fot the given company.
	 *
	 * Calling modules can update the company info with this API method so that they match.
	 *
	 * @param int $companyId             The company ID.
	 * @param string $registrationNumber The registration number, which in spain is the NIF/DNI.
	 *
	 * @return mixed
	 * @throws Exception
	 *
	 */
	public function updateCompany($companyId, $registrationNumber)
	{
		try {
			$companiesApi = new ContasimpleMeCompaniesApi($this->apiClient);
			$companiesList = $companiesApi->myCompaniesGetCompanies('2')->getData();
			foreach ($companiesList as $company) {
				if ($company->getId() == $companyId) {
					if (!empty($company->getExtraInformation())) {
						$entity = $company->getExtraInformation()->getEntity();
						$entity->setNif($registrationNumber);
						$configureModel = new ConfigureCompanyApiModel();
						$configureModel->setCountryId($company->getCountryId());
						$configureModel->setFiscalRegionId($company->getFiscalRegionId());
						$configureModel->setCompanyTypeId($company->getCompanyTypeId());
						$configureModel->setIrpfModeId($company->getIrpfModeId());
						$configureModel->setVatModeId($company->getVatTypeId());
						$configureModel->setEntity($entity);
						$companiesApi->myCompaniesConfigureCompany($configureModel, "2");
					}

					break;
				}
			}
		} catch (\Exception $e) {
			return $this->handleCommonExceptions($e);
		}
	}

	/**
	 * Checks if the given company needs fiscal configuration at the CS level.
	 *
	 * Before a calling module can use the API to sync invoices, the CS company must have been correctly
	 * configured, for example via the provided CS wizard in the main contasimple.com site.
	 *
	 * @param int $companyId    The company ID.
	 *
	 * @return bool             true if needs configuration (module cannot be used),
	 *                          false otherwise (module can be used).
	 * @throws Exception
	 */
	public function companyRequiresConfiguration($companyId)
	{
		try {
			$companiesApi = new ContasimpleMeCompaniesApi($this->apiClient);
			$companiesList = $companiesApi->myCompaniesGetCompanies('2')->getData();//myCompaniesGetCompanies
			foreach ($companiesList as $company) {
				if ($company->getId() == $companyId) {
					return $company->getRequiresConfiguration();
				}
			}
		} catch (\Exception $e) {
			return $this->handleCommonExceptions($e);
		} finally {
			return true;
		}
	}

	/**
	 * Creates an invoice.
	 *
	 * The calling module needs to gather the invoice information as an InvoiceApiModel object and
	 * format a valid period for the invoice, as the fiscal period is always mandatory for a CS invoice.
	 *
	 * @param string          $period   A CS compliant period string. Ex: '2017-1T'.
	 * @param InvoiceApiModel $invoice  An instance of InvoiceApiModel with the data filled.
	 *
	 * @see InvoiceApiModel
	 *
	 * @return InvoiceApiModel The resulting invoice entity as an instance of an InvoiceApiModel filled with CS data.
	 * @throws Exception If any of the required fields is missing or validation checks are not fulfilled at the API server side.
	 */
	public function createInvoice($period, $invoice)
	{
		try {
			$invoiceModel = new CreateIssuedInvoiceApiModel($invoice);
			$invoiceApi = new ContasimpleAccountingInvoicesIssuedApi($this->apiClient);
			$response = $invoiceApi->accountingInvoicesIssuedCreateInvoice($period, $invoiceModel, "2");
			return $response->getData();
		} catch (ContasimpleApiException $e) {
			if ((null !== $e->getResponseBody()) && !empty($e->getResponseBody()->errorCode)) {
				// Handle particular cases for incorrect invoice creation into WP translated messages.
				switch ($e->getResponseBody()->errorCode) {
					case 'Api_Invoices_InvalidVatPercentage':
						$errorCode = INVALID_VAT;
						break;
					case 'Invoice_RepeatedNumber':
						$errorCode = INVOICE_REPEATED;
						break;
					case 'Common_InvalidArgument':
						if (strpos($e->getResponseBody()->errorMessage, 'VAT') !== false) {
							$errorCode = INVALID_VAT;
						} elseif (strpos($e->getResponseBody()->errorMessage, 'Lines') !== false) {
							$errorCode = INVALID_LINES;
						} elseif (strpos($e->getResponseBody()->errorMessage, 'Invalid number of decimals') !== false) {
							$errorCode = INVALID_NUMBER_DECIMALS;
						} else {
							$errorCode = INVALID_MODEL;
						}
						break;
					case 'Common_PlanLimitReached':
						$errorCode = PLAN_LIMIT_REACHED;
						break;
					case 'Invoice_Insert_PeriodClosed':
						$errorCode = INVOICE_INSERT_PERIOD_CLOSED;
						break;
					case 'InvoiceLine_TaxableAmountDiscrepancy':
						$errorCode = ROUNDING_DISCREPANCY_ERROR;
						break;
					case 'entidades_country_nif_validation_error_1':
						$errorCode = ENTIDADES_COUNTRY_NIF_VALIDATION_ERROR_1;
						break;
					default:
						$errorCode = API_ERROR;
						break;
				}
				if ($errorCode == API_ERROR) {
					// Handle other scenarios with the default catch system.
					return $this->handleCommonExceptions($e);
				} else {
					// We know what is happening and can inform via the plugin system.
					throw new \Exception($e->getResponseBody()->errorMessage, $errorCode, $e);
				}
			} else {
				// Handle other scenarios with the default catch system.
				return $this->handleCommonExceptions($e);
			}
		} catch (Exception $e) {
			// Handle other scenarios with the default catch system.
			return $this->handleCommonExceptions($e);
		}
	}

	/**
	 * Updates an existing invoice.
	 *
	 * The existing invoice ID must be provided, alongside the desired new invoice parameters as an InvoiceApiModel object,
	 * and the period as in the createInvoice() method.
	 *
	 * @param string          $period       A CS compliant period string. Ex: '2017-1T'.
	 * @param InvoiceApiModel $invoice      See \Swagger\Client\Model\InvoiceApiModel.
	 *
	 * @see InvoiceApiModel
	 *
	 * @param int $id           The ID of the invoice to update.
	 *
	 * @return InvoiceApiModel
	 * @throws Exception If any of the required fields is missing or validation checks are not fulfilled at the API server side.
	 */
	public function updateInvoice($period, $invoice, $id)
	{
		try {
			$invoiceModel = new UpdateIssuedInvoiceApiModel($invoice);
			$invoiceApi = new ContasimpleAccountingInvoicesIssuedApi($this->apiClient);
			$response = $invoiceApi->accountingInvoicesIssuedUpdateInvoice($period, $id, $invoiceModel, "2");
			return $response->getData();
		} catch (ContasimpleApiException $e) {
			// TODO merge this with return $this->handleCommonExceptions($e);
			// Not critical right now since the WP plugin does not use this endpoint.
			$errorCode = SYNC_ERROR;
			switch ($e->getResponseBody()->errorCode) {
				case 'Common_InvalidArgument':
					if (preg_match('(VAT)', $e->getResponseBody()->errorMessage) === 1) {
						$errorCode = INVALID_VAT;
					} else {
						$errorCode = INVALID_MODEL;
					}
					break;

				case 'Invoice_Update_OriginalPeriodClosed':
					$errorCode = INVOICE_UPDATE_ORIGINAL_PERIOD_CLOSED;
					break;

				case 'Invoice_Update_NewPeriodClosed':
					$errorCode = INVOICE_UPDATE_NEW_PERIOD_CLOSED;
					break;
			}
			throw new \Exception($e->getResponseBody()->errorMessage, $errorCode, $e);
		}
	}

	/**
	 * Deletes an invoice given its ID and fiscal period.
	 *
	 * @param string $period   A CS compliant period string. Ex: '2017-1T'.
	 * @param int    $id       The ID of the invoice to delete.
	 *
	 * @return int      See \Swagger\Client\Model\ApiResultInt64
	 * @throws Exception
	 */
	public function deleteInvoice($period, $id)
	{
		try {
			$invoiceApi = new ContasimpleAccountingInvoicesIssuedApi($this->apiClient);
			$response = $invoiceApi->accountingInvoicesIssuedDeleteInvoice($period, $id, "2");
			return $response->getData();
		} catch (ContasimpleApiException $e) {
			// TODO merge this with return $this->handleCommonExceptions($e);
			// Not critical right now since the WP plugin does not use this endpoint.
			$errorCode = DELETE_ERROR;
			switch ($e->getResponseBody()->errorCode) {
				case 'Common_InvalidArgument':
					break;
			}
			throw new \Exception($e->getResponseBody()->errorMessage, $errorCode, $e);
		}
	}

	/**
	 * Gets all invoices from the current company and fiscal period.
	 *
	 * @param string $period A CS compliant period string. Ex: '2017-1T'.
	 *
	 * @param null $notes A text that is contained in the notes section. Useful to look for invoices synced by order id.
	 *
	 * @return InvoiceApiModel[]|mixed
	 * @throws Exception
	 */
	public function getInvoices($period, $notes = null)
	{
		try {
			$invoiceApi = new ContasimpleAccountingInvoicesIssuedApi($this->apiClient);
			$response = $invoiceApi->accountingInvoicesIssuedList($period, "2", null, null, null, null,  null,  null, $notes);
			return $response->getData();
		} catch (ContasimpleApiException $e) {
			return $this->handleCommonExceptions($e);
		}
	}

	/**
	 * Gets the customer details based on the DNI/NIF number.
	 *
	 * Useful to check whether a new client must be synced via the API.
	 *
	 * @param string $taxNumber Identity Number which can be the DNI/NIF, VAT number if company has it, etc.
	 *
	 * @return EntityApiModel An Entity model with customer data if it existed, or null if the customer does not exist
	 *                        as a CS client yet.
	 * @throws Exception
	 */
	public function getCustomerByNIF($taxNumber)
	{
		try {
			$customersApi = new ContasimpleEntitiesCustomersApi($this->apiClient);
			$response = $customersApi->entityCustomersSearchByNif("2", $taxNumber);
			if ($response->getCount() > 0) {
				if (!empty($response->getData())) {
					return $response->getData()[0];
				} else {
					return null;
				}
			} else {
				return null;
			}
		} catch (\Exception $e) {
			return $this->handleCommonExceptions($e);
		}
	}

	/**
	 * Gets the 'aggregated' customer, also known as 'Clientes varios'.
	 *
	 * Useful when an invoice has to be created but not all the needed info is known to be able to create a customer.
	 * The aggregated customer can be used as a 'cul-de-sac' customer to group all those invoices, which fiscally emulates
	 * simplified invoices/shipping tickets.
	 *
	 * @return EntityApiModel|mixed
	 * @throws Exception
	 */
	public function getCustomerAggregated()
	{
		try {
			$customersApi = new ContasimpleEntitiesCustomersAggregatedApi($this->apiClient);
			$response = $customersApi->aggregatedCustomersEntityGet("2");
			return $response->getData();
		} catch (\Exception $e) {
			if (($e instanceof ContasimpleApiException) && (empty($e->getResponseBody())) && ($e->getCode() == 404)) {
				// If not configured, try to create it
				return $this->createCustomerAggregated();
			} else {
				return $this->handleCommonExceptions($e);
			}
		}
	}

	/**
	 * Creates the aggregated customer.
	 *
	 * If the aggregated customer is not configured in CS this method will create it.
	 * Try to assess first wheter it exists it or not with getCustomerAggregated.
	 *
	 * @see getCustomerAggregated()
	 *
	 * @return EntityApiModel
	 * @throws Exception
	 */
	public function createCustomerAggregated()
	{
		try {
			$customersApi = new ContasimpleEntitiesCustomersAggregatedApi($this->apiClient);
			$response = $customersApi->aggregatedCustomersEntityPost("2");
			return $response->getData();
		} catch (ContasimpleApiException $e) {
			// TODO Merge with return $this->handleCommonExceptions($e);
			throw new \Exception($e->getResponseBody()->errorMessage, AGGREGATED_CUSTOMER_CANNOT_CREATE, $e);
		}
	}

	/**
	 * Creates a customer in CS.
	 *
	 * A customer entity is always needed before being able to sync an issued invoice (to that customer).
	 *
	 * @param mixed $customer
	 *
	 * @return EntityApiModel
	 * @throws Exception
	 */
	public function createCustomer($customer)
	{
		try {
			$customersApi = new ContasimpleEntitiesCustomersApi($this->apiClient);
			$response = $customersApi->entityCustomersPost($customer, "2");
			return $response->getData();
		} catch (\Exception $e) {
			if (($e instanceof ContasimpleApiException) && (null !== $e->getResponseBody()) && ('Common_PlanLimitReached' == $e->getResponseBody()->errorCode)) {
				throw new \Exception($e->getResponseBody()->errorMessage, PLAN_LIMIT_REACHED_ENTITIES, $e);
			} else {
				return $this->handleCommonExceptions($e);
			}
		}
	}

	/**
	 * Sends an invoice payment.
	 *
	 * Invoices are not marked as paid in CS on creation time, thus this method allows to attach a payment to the invoice.
	 *
	 * @param int                           $id      The ID of the invoice to assign a payment.
	 * @param InvoicePaymentRequestApiModel $payment Object with the payment info.
	 * @param string                        $period  A CS compliant period string. Ex: '2017-1T'.
	 *
	 * @return DocumentPaymentApiModel
	 * @throws Exception
	 */
	public function assignPayment($id, $payment, $period)
	{
		try {
			$paymentApi = new ContasimpleAccountingInvoicesIssuedPaymentsApi($this->apiClient);
			$response = $paymentApi->accountingInvoicesIssuedPaymentsCreatePayment($id, $payment, "2", $period);
			return $response->getData();
		} catch (\Exception $e) {
			if (($e instanceof ContasimpleApiException) && (null !== $e->getResponseBody()) && ('InvoicePayment_Insert_PeriodClosed' == $e->getResponseBody()->errorCode)) {
				throw new \Exception($e->getResponseBody()->errorMessage, INVOICE_PAYMENT_INSERT_PERIOD_CLOSED, $e);
			} elseif (($e instanceof ContasimpleApiException) && (null !== $e->getResponseBody()) && ('InvoicePayment_Cant_load_payment' == $e->getResponseBody()->errorCode)) {
				throw new \Exception($e->getResponseBody()->errorMessage, PAYMENT_METHODS_RETRIEVAL_ERROR, $e);
			} else {
				return $this->handleCommonExceptions($e);
			}
		}
	}

	/**
	 * Gets invoice payments.
	 *
	 * Returns a list of payments attached to the given invoice.
	 *
	 * @param int    $idInvoice The invoice ID.
	 * @param string $period    A CS compliant period string. Ex: '2017-1T'.
	 *
	 * @return InvoicePaymentApiModel[]
	 * @throws Exception
	 */
	public function getInvoicePayments($idInvoice, $period)
	{
		try {
			$paymentApi = new ContasimpleAccountingInvoicesIssuedPaymentsApi($this->apiClient);
			$response = $paymentApi->accountingInvoicesIssuedPaymentsGetPayments($idInvoice, "2", $period);
			return $response->getData();
		} catch (ContasimpleApiException $e) {
			if (!empty($e->getResponseBody()->message) && stripos($e->getResponseBody()->message, 'Authorization has been denied') !== false) {
				throw new \Exception($e->getResponseBody()->message, AUTH_DENIED, $e);
			}
			$errorCode = PAYMENT_RETRIEVAL_ERROR;
			throw new \Exception($e->getResponseBody()->errorMessage, $errorCode, $e);
		} catch (\Exception $e) {
			throw new \Exception('Internal Exception', -1, $e);
		}
	}

	/**
	 * Deletes payment of a given invoice.
	 *
	 * CS will not mark an invoice as 'Unpaid' if the invoice details (total price) are modified via an invoice update.
	 * Thus, we need a method to delete the invoice so that we can upload payments again.
	 * This method allows to delete one payment at a time, which we can get via getInvoicePayments().
	 *
	 * @param int    $idInvoice The invoice ID.
	 * @param int    $idPayment The ID for the desired payment to delete.
	 * @param string $period    A CS compliant period string. Ex: '2017-1T'.
	 *
	 * @return int
	 * @throws Exception
	 */
	public function deletePayment($idInvoice, $idPayment, $period)
	{
		try {
			$paymentApi = new ContasimpleAccountingInvoicesIssuedPaymentsApi($this->apiClient);
			$response = $paymentApi->accountingInvoicesIssuedPaymentsDeletePayment($idInvoice, $idPayment, "2", $period);
			return $response->getData();
		} catch (ContasimpleApiException $e) {
			if (!empty($e->getResponseBody()->message) && stripos($e->getResponseBody()->message, 'Authorization has been denied') !== false) {
				throw new \Exception($e->getResponseBody()->message, AUTH_DENIED, $e);
			}
			$errorCode = PAYMENT_DELETE_ERROR;
			throw new \Exception($e->getResponseBody()->errorMessage, $errorCode, $e);
		} catch (\Exception $e) {
			throw new \Exception('Internal Exception', -1, $e);
		}
	}

	/**
	 * Gets the company payment methods available.
	 *
	 * @return \Contasimple\Swagger\Client\Model\PaymentMethodApiModel[]
	 * @throws Exception
	 */
	public function getPaymentMethods()
	{
		try {
			$configurationPaymentMethodsApi = new ContasimpleConfigurationPaymentMethodsApi($this->apiClient);
			$response = $configurationPaymentMethodsApi->paymentMethodsConfigurationGetPaymentMethods("2");
			return $response->getData();
		} catch (\Exception $e) {
			//$errorCode = PAYMENT_METHODS_RETRIEVAL_ERROR;
			return $this->handleCommonExceptions($e);
		}
	}

	/**
	 * Returns the next invoice number available for the given fiscal period and mask format.
	 *
	 * @param string $period A CS compliant period string. Ex: '2017-1T'.
	 * @param int    $numberingFormatId  The id of the numbering series in Contasimple. Ex: 2142
	 *
	 * @return string The invoice number formatted as a string based on the $mask format passed as an argument.
	 *                Ex: 2021-000001.
	 * @throws Exception
	 */
	public function getNextInvoiceNumber($period, $numberingFormatId)
	{
		try {
			$invoiceApi = new ContasimpleAccountingInvoicesIssuedApi($this->apiClient);
			$response = $invoiceApi->accountingInvoicesIssuedGetNextInvoiceNumber($period, $numberingFormatId, 2);
			return $response->getData();
		} catch (\Exception $e) {
			return $this->handleCommonExceptions($e);
		}
	}

	/**
	 * Downloads (as a browser download) the invoice PDF for the given document id & fiscal period.
	 *
	 * @param int    $id     The invoice ID.
	 * @param string $period A CS compliant period string. Ex: '2017-1T'.
	 * @param string $number The invoice number that will be used as the name of the file to be downloaded.
	 *                       This typically matches the invoice number. Ex: 2021-000001.
	 *
	 * @return mixed A download action from the web browser with the PDF document named after the invoice number.
	 *               Ex: 2021-000001.pdf.
	 * @throws Exception
	 */
	public function getInvoicePDF($id, $period, $number)
	{
		try {
			$invoiceApi = new ContasimpleAccountingInvoicesIssuedApi($this->apiClient);
			$response = $invoiceApi->accountingInvoicesIssuedPdf($id, 2, $period);

			header("Content-Disposition: attachment; filename=\"" . basename($number . ".pdf") . "\"");
			header("Content-Type: application/octet-stream");
			header("Content-Length: " . strlen($response[0]));
			header("Connection: close");

			echo $response[0];
		} catch (\Exception $e) {
			return $this->handleCommonExceptions($e);
		}
	}

	/**
	 * Gets the invoice PDF (as a blob) for the given document id & fiscal period.
	 * Useful to attach to an email.
	 *
	 * @param int    $id     The invoice ID.
	 * @param string $period A CS compliant period string. Ex: '2017-1T'.
	 * @param string $number
	 *
	 * @return mixed The invoice as an encoded string.
	 * @throws Exception
	 */
	public function getInvoicePDFasString($id, $period, $number)
	{
		try {
			$invoiceApi = new ContasimpleAccountingInvoicesIssuedApi($this->apiClient);
			$response = $invoiceApi->accountingInvoicesIssuedPdf($id, 2, $period);
			return $response[0];
		} catch (\Exception $e) {
			return $this->handleCommonExceptions($e);
		}
	}

	/**
	 * Gets all countries available in CS.
	 *
	 * @param bool|null $all
	 * @return \Contasimple\Swagger\Client\Model\CountryApiModel[]
	 * @throws Exception
	 */
	public function getCountries($all = null)
	{
		try {
			$configurationCommonApi = new ContasimpleConfigurationCommonApi($this->apiClient);
			$response = $configurationCommonApi->countriesConfigurationGetCountries("2", $all);
			return $response->getData();
		} catch (\Exception $e) {
			return $this->handleCommonExceptions($e);
		}
	}

	public function getInvoiceNumberingFormats($lang = 'es-ES')
	{
		try {
			$configurationNumberingFormatsApi = new ContasimpleConfigurationNumberingFormatsApi($this->apiClient);
			$response = $configurationNumberingFormatsApi->invoiceNumberingFormatsList("2", null, null, null, $lang);
			return $response->getData();
		} catch (\Exception $e) {
			return $this->handleCommonExceptions($e);
		}
	}

	public function getInvoiceNumberingFormat($id, $lang = 'es-ES')
	{
		try {
			$configurationNumberingFormatsApi = new ContasimpleConfigurationNumberingFormatsApi($this->apiClient);
			$response = $configurationNumberingFormatsApi->invoiceNumberingFormatsGet($id, "2", $lang);
			return $response->getData();
		} catch (\Exception $e) {
			if ($e->getCode() == 404) {
				throw new \Exception($e->getMessage(), CANNOT_FIND_NUMBERING_SERIES, $e);
			} else {
				return $this->handleCommonExceptions($e);
			}
		}
	}

	public function createInvoiceNumberingFormat($data, $lang = 'es-ES')
	{
		try {
			$configurationNumberingFormatsApi = new ContasimpleConfigurationNumberingFormatsApi($this->apiClient);
			$response = $configurationNumberingFormatsApi->invoiceNumberingFormatsCreate($data, "2", $lang);
			return $response->getData();
		} catch (\Exception $e) {
			return $this->handleCommonExceptions($e);
		}
	}

	/**
	 * Tries to call the caller function again. Useful if the token was expired and we wanted to call again after
	 * renewal.
	 *
	 * @throws Exception If the method cannot be retried successfully.
	 */
	protected function retry() {
		// Get the call stack.
		$trace = debug_backtrace();

		// Get all parameters needed to call the function again: object class name, object method and parameters (if any).
		$method_name = $trace[2]['function'];
		$method_params = $trace[2]['args'];
		$obj = $trace[2]['class'];

		// In order to avoid infinite recursion, return if the method has been already 'retried' once.
		if ($trace[3]['function'] === "retry") {
			throw new \Exception();
		}

		// Call the function.
		if (is_callable(array($obj, $method_name))) {
			return call_user_func_array( array($obj, $method_name), $method_params );
		} else {
			throw new \Exception();
		}
	}

	/**
	 * Common place to handle exceptions that might occur in most of the class methods.
	 *
	 * @param Exception $e An exception thrown by the API.
	 *
	 * @return mixed The result of the retry() method if the session expired and we are retrying to call the original
	 *               method, otherwise an exception is thrown.
	 * @throws Exception An exception adapted to be used by the plugin. This allows to use errors defined by the plugin
	 *                   following the eCommerce culture and translation system.
	 *                   An exception to this is the API_ERROR, where the original ContasimpleApiException error is passed
	 *                   down and the plugin should favour displaying this message to the user, as not all errors
	 *                   can be contemplated by the plugin because the API can change at any time.
	 */
	protected function handleCommonExceptions(\Exception $e) {
		if ($e instanceof ContasimpleApiException) {
			if (empty($e->getResponseBody())) {
				if (stripos($e->getMessage(), 'Could not resolve') !== false || stripos($e->getMessage(), 'Connection refused') !== false) {
					throw new \Exception($e->getMessage(), HOST_UNREACHABLE, $e);
				} else {
					throw new \Exception($e->getMessage(), GENERIC_ERROR, $e);
				}
			} else {
				if (!is_object( $e->getResponseBody())) {
					// Fixes trying to connect during maintenance.
					throw new \Exception($e->getMessage(), GENERIC_ERROR, $e);

				} elseif (!empty($e->getResponseBody()->errorCode) && false !== stripos($e->getResponseBody()->errorCode, "Api_Session_Overwritten")) {
					// If the access token is expired, try to renew it and recover by calling the original API endpoint again.
					try {
						$this->renewAccessToken();
						return $this->retry();
					} catch (\Exception $ignore) {
						// Better throw the original exception since we could not get past through it.
						throw new \Exception($e->getResponseBody()->errorMessage, API_ERROR, $e);;
					}

				} elseif (!empty($e->getResponseBody()->errorCode) && 'Common_InsuficientRightsForService' === $e->getResponseBody()->errorCode) {
					throw new \Exception($e->getResponseBody()->errorMessage, INSUFFICIENT_RIGHTS_ERROR, $e);

				} elseif (!empty($e->getResponseBody()->errorCode) && 'Api_Session_Company_Access_Removed' === $e->getResponseBody()->errorCode) {
                    throw new \Exception($e->getResponseBody()->errorMessage, INSUFFICIENT_RIGHTS_ERROR, $e);

                } elseif (!empty($e->getResponseBody()->message) && false !== stripos($e->getResponseBody()->message, 'Authorization has been denied')) {
					// If it is an authorization denied error, handle it via WP code and according translation.
					throw new \Exception($e->getResponseBody()->message, AUTH_DENIED, $e);

				} elseif (!empty($e->getResponseBody()->errorMessage)) {
					// If were are unsure of the error, favor the API error.
					throw new \Exception($e->getResponseBody()->errorMessage, API_ERROR, $e);

				}  else {
					// If there is no responde body something is wrong with the API, return a generic WP error.
					throw new \Exception($e->getMessage(), GENERIC_ERROR, $e);
				}
			}
		} else {
			// An error for whatever other reason, the generic error can be a cul-the-sac for the CMS to display
			// in-built error messages with the internal translation mechanism of the CMS.
			throw new \Exception($e->getMessage(), GENERIC_ERROR, $e);
		}
	}
}
