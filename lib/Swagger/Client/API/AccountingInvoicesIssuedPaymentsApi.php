<?php
/**
 * AccountingInvoicesIssuedPaymentsApi
 * PHP version 5
 *
 * @category Class
 * @package  Contasimple\Swagger\Client
 * @author   http://github.com/swagger-api/swagger-codegen
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache License v2
 * @link     https://github.com/swagger-api/swagger-codegen
 */

/**
 * Contasimple API
 *
 * No description provided (generated by Swagger Codegen https://github.com/swagger-api/swagger-codegen)
 *
 * OpenAPI spec version: v2
 * 
 * Generated by: https://github.com/swagger-api/swagger-codegen.git
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

/**
 * NOTE: This class is auto generated by the swagger code generator program.
 * https://github.com/swagger-api/swagger-codegen
 * Do not edit the class manually.
 */

namespace Contasimple\Swagger\Client\Api;

use \Contasimple\Swagger\Client\ApiClient;
use \Contasimple\Swagger\Client\ApiException;
use \Contasimple\Swagger\Client\Configuration;
use \Contasimple\Swagger\Client\ObjectSerializer;

/**
 * AccountingInvoicesIssuedPaymentsApi Class Doc Comment
 *
 * @category Class
 * @package  Contasimple\Swagger\Client
 * @author   http://github.com/swagger-api/swagger-codegen
 * @license  http://www.apache.org/licenses/LICENSE-2.0 Apache License v2
 * @link     https://github.com/swagger-api/swagger-codegen
 */
class AccountingInvoicesIssuedPaymentsApi
{
    /**
     * API Client
     *
     * @var \Contasimple\Swagger\Client\ApiClient instance of the ApiClient
     */
    protected $apiClient;

    /**
     * Constructor
     *
     * @param \Contasimple\Swagger\Client\ApiClient|null $apiClient The api client to use
     */
    public function __construct(\Contasimple\Swagger\Client\ApiClient $apiClient = null)
    {
        if ($apiClient === null) {
            $apiClient = new ApiClient();
            $apiClient->getConfig()->setHost('http://api.prelive.contasimple.com');
        }

        $this->apiClient = $apiClient;
    }

    /**
     * Get API client
     *
     * @return \Contasimple\Swagger\Client\ApiClient get the API client
     */
    public function getApiClient()
    {
        return $this->apiClient;
    }

    /**
     * Set the API client
     *
     * @param \Contasimple\Swagger\Client\ApiClient $apiClient set the API client
     *
     * @return AccountingInvoicesIssuedPaymentsApi
     */
    public function setApiClient(\Contasimple\Swagger\Client\ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
        return $this;
    }

    /**
     * Operation accountingInvoicesIssuedPaymentsCreatePayment
     *
     * Gets the payments information for the given invoice id
     *
     * @param int $invoice_id The identifier of the invoice to retrieve the payments (required)
     * @param \Contasimple\Swagger\Client\Model\InvoicePaymentRequestApiModel $payment The information of the payment (required)
     * @param string $version  (required)
     * @param string $period  (required)
     * @throws \Contasimple\Swagger\Client\ApiException on non-2xx response
     * @return \Contasimple\Swagger\Client\Model\ApiResultDocumentPaymentApiModel
     */
    public function accountingInvoicesIssuedPaymentsCreatePayment($invoice_id, $payment, $version, $period)
    {
        list($response) = $this->accountingInvoicesIssuedPaymentsCreatePaymentWithHttpInfo($invoice_id, $payment, $version, $period);
        return $response;
    }

    /**
     * Operation accountingInvoicesIssuedPaymentsCreatePaymentWithHttpInfo
     *
     * Gets the payments information for the given invoice id
     *
     * @param int $invoice_id The identifier of the invoice to retrieve the payments (required)
     * @param \Contasimple\Swagger\Client\Model\InvoicePaymentRequestApiModel $payment The information of the payment (required)
     * @param string $version  (required)
     * @param string $period  (required)
     * @throws \Contasimple\Swagger\Client\ApiException on non-2xx response
     * @return array of \Contasimple\Swagger\Client\Model\ApiResultDocumentPaymentApiModel, HTTP status code, HTTP response headers (array of strings)
     */
    public function accountingInvoicesIssuedPaymentsCreatePaymentWithHttpInfo($invoice_id, $payment, $version, $period)
    {
        // verify the required parameter 'invoice_id' is set
        if ($invoice_id === null) {
            throw new \InvalidArgumentException('Missing the required parameter $invoice_id when calling accountingInvoicesIssuedPaymentsCreatePayment');
        }
        // verify the required parameter 'payment' is set
        if ($payment === null) {
            throw new \InvalidArgumentException('Missing the required parameter $payment when calling accountingInvoicesIssuedPaymentsCreatePayment');
        }
        // verify the required parameter 'version' is set
        if ($version === null) {
            throw new \InvalidArgumentException('Missing the required parameter $version when calling accountingInvoicesIssuedPaymentsCreatePayment');
        }
        // verify the required parameter 'period' is set
        if ($period === null) {
            throw new \InvalidArgumentException('Missing the required parameter $period when calling accountingInvoicesIssuedPaymentsCreatePayment');
        }
        // parse inputs
        $resourcePath = "/api/v{version}/accounting/{period}/invoices/issued/{invoiceId}/payments";
        $httpBody = '';
        $queryParams = [];
        $headerParams = [];
        $formParams = [];
        $_header_accept = $this->apiClient->selectHeaderAccept(['application/json', 'text/json', 'text/html']);
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType(['application/json', 'text/json', 'text/html', 'application/xml', 'text/xml', 'application/x-www-form-urlencoded']);

        // path params
        if ($invoice_id !== null) {
            $resourcePath = str_replace(
                "{" . "invoiceId" . "}",
                $this->apiClient->getSerializer()->toPathValue($invoice_id),
                $resourcePath
            );
        }
        // path params
        if ($version !== null) {
            $resourcePath = str_replace(
                "{" . "version" . "}",
                $this->apiClient->getSerializer()->toPathValue($version),
                $resourcePath
            );
        }
        // path params
        if ($period !== null) {
            $resourcePath = str_replace(
                "{" . "period" . "}",
                $this->apiClient->getSerializer()->toPathValue($period),
                $resourcePath
            );
        }
        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        // body params
        $_tempBody = null;
        if (isset($payment)) {
            $_tempBody = $payment;
        }

        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        // this endpoint requires OAuth (access token)
        if (strlen($this->apiClient->getConfig()->getAccessToken()) !== 0) {
            $headerParams['Authorization'] = 'Bearer ' . $this->apiClient->getConfig()->getAccessToken();
        }
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'POST',
                $queryParams,
                $httpBody,
                $headerParams,
                '\Contasimple\Swagger\Client\Model\ApiResultDocumentPaymentApiModel',
                '/api/v{version}/accounting/{period}/invoices/issued/{invoiceId}/payments'
            );

            return [$this->apiClient->getSerializer()->deserialize($response, '\Contasimple\Swagger\Client\Model\ApiResultDocumentPaymentApiModel', $httpHeader), $statusCode, $httpHeader];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Contasimple\Swagger\Client\Model\ApiResultDocumentPaymentApiModel', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
            }

            throw $e;
        }
    }

    /**
     * Operation accountingInvoicesIssuedPaymentsDeletePayment
     *
     * Gets the payments information for the given invoice id
     *
     * @param int $invoice_id The identifier of the invoice to retrieve the payments (required)
     * @param int $invoice_payment_id The identifier of the invoice payment to remove (required)
     * @param string $version  (required)
     * @param string $period  (required)
     * @throws \Contasimple\Swagger\Client\ApiException on non-2xx response
     * @return \Contasimple\Swagger\Client\Model\ApiResultInt64
     */
    public function accountingInvoicesIssuedPaymentsDeletePayment($invoice_id, $invoice_payment_id, $version, $period)
    {
        list($response) = $this->accountingInvoicesIssuedPaymentsDeletePaymentWithHttpInfo($invoice_id, $invoice_payment_id, $version, $period);
        return $response;
    }

    /**
     * Operation accountingInvoicesIssuedPaymentsDeletePaymentWithHttpInfo
     *
     * Gets the payments information for the given invoice id
     *
     * @param int $invoice_id The identifier of the invoice to retrieve the payments (required)
     * @param int $invoice_payment_id The identifier of the invoice payment to remove (required)
     * @param string $version  (required)
     * @param string $period  (required)
     * @throws \Contasimple\Swagger\Client\ApiException on non-2xx response
     * @return array of \Contasimple\Swagger\Client\Model\ApiResultInt64, HTTP status code, HTTP response headers (array of strings)
     */
    public function accountingInvoicesIssuedPaymentsDeletePaymentWithHttpInfo($invoice_id, $invoice_payment_id, $version, $period)
    {
        // verify the required parameter 'invoice_id' is set
        if ($invoice_id === null) {
            throw new \InvalidArgumentException('Missing the required parameter $invoice_id when calling accountingInvoicesIssuedPaymentsDeletePayment');
        }
        // verify the required parameter 'invoice_payment_id' is set
        if ($invoice_payment_id === null) {
            throw new \InvalidArgumentException('Missing the required parameter $invoice_payment_id when calling accountingInvoicesIssuedPaymentsDeletePayment');
        }
        // verify the required parameter 'version' is set
        if ($version === null) {
            throw new \InvalidArgumentException('Missing the required parameter $version when calling accountingInvoicesIssuedPaymentsDeletePayment');
        }
        // verify the required parameter 'period' is set
        if ($period === null) {
            throw new \InvalidArgumentException('Missing the required parameter $period when calling accountingInvoicesIssuedPaymentsDeletePayment');
        }
        // parse inputs
        $resourcePath = "/api/v{version}/accounting/{period}/invoices/issued/{invoiceId}/payments/{invoicePaymentId}";
        $httpBody = '';
        $queryParams = [];
        $headerParams = [];
        $formParams = [];
        $_header_accept = $this->apiClient->selectHeaderAccept(['application/json', 'text/json', 'text/html']);
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType([]);

        // path params
        if ($invoice_id !== null) {
            $resourcePath = str_replace(
                "{" . "invoiceId" . "}",
                $this->apiClient->getSerializer()->toPathValue($invoice_id),
                $resourcePath
            );
        }
        // path params
        if ($invoice_payment_id !== null) {
            $resourcePath = str_replace(
                "{" . "invoicePaymentId" . "}",
                $this->apiClient->getSerializer()->toPathValue($invoice_payment_id),
                $resourcePath
            );
        }
        // path params
        if ($version !== null) {
            $resourcePath = str_replace(
                "{" . "version" . "}",
                $this->apiClient->getSerializer()->toPathValue($version),
                $resourcePath
            );
        }
        // path params
        if ($period !== null) {
            $resourcePath = str_replace(
                "{" . "period" . "}",
                $this->apiClient->getSerializer()->toPathValue($period),
                $resourcePath
            );
        }
        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        
        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        // this endpoint requires OAuth (access token)
        if (strlen($this->apiClient->getConfig()->getAccessToken()) !== 0) {
            $headerParams['Authorization'] = 'Bearer ' . $this->apiClient->getConfig()->getAccessToken();
        }
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'DELETE',
                $queryParams,
                $httpBody,
                $headerParams,
                '\Contasimple\Swagger\Client\Model\ApiResultInt64',
                '/api/v{version}/accounting/{period}/invoices/issued/{invoiceId}/payments/{invoicePaymentId}'
            );

            return [$this->apiClient->getSerializer()->deserialize($response, '\Contasimple\Swagger\Client\Model\ApiResultInt64', $httpHeader), $statusCode, $httpHeader];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Contasimple\Swagger\Client\Model\ApiResultInt64', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
            }

            throw $e;
        }
    }

    /**
     * Operation accountingInvoicesIssuedPaymentsGetPayments
     *
     * Gets the payments information for the given invoice id
     *
     * @param int $invoice_id The identifier of the invoice to retrieve the payments (required)
     * @param string $version  (required)
     * @param string $period  (required)
     * @throws \Contasimple\Swagger\Client\ApiException on non-2xx response
     * @return \Contasimple\Swagger\Client\Model\ApiListResultDocumentPaymentApiModel
     */
    public function accountingInvoicesIssuedPaymentsGetPayments($invoice_id, $version, $period)
    {
        list($response) = $this->accountingInvoicesIssuedPaymentsGetPaymentsWithHttpInfo($invoice_id, $version, $period);
        return $response;
    }

    /**
     * Operation accountingInvoicesIssuedPaymentsGetPaymentsWithHttpInfo
     *
     * Gets the payments information for the given invoice id
     *
     * @param int $invoice_id The identifier of the invoice to retrieve the payments (required)
     * @param string $version  (required)
     * @param string $period  (required)
     * @throws \Contasimple\Swagger\Client\ApiException on non-2xx response
     * @return array of \Contasimple\Swagger\Client\Model\ApiListResultDocumentPaymentApiModel, HTTP status code, HTTP response headers (array of strings)
     */
    public function accountingInvoicesIssuedPaymentsGetPaymentsWithHttpInfo($invoice_id, $version, $period)
    {
        // verify the required parameter 'invoice_id' is set
        if ($invoice_id === null) {
            throw new \InvalidArgumentException('Missing the required parameter $invoice_id when calling accountingInvoicesIssuedPaymentsGetPayments');
        }
        // verify the required parameter 'version' is set
        if ($version === null) {
            throw new \InvalidArgumentException('Missing the required parameter $version when calling accountingInvoicesIssuedPaymentsGetPayments');
        }
        // verify the required parameter 'period' is set
        if ($period === null) {
            throw new \InvalidArgumentException('Missing the required parameter $period when calling accountingInvoicesIssuedPaymentsGetPayments');
        }
        // parse inputs
        $resourcePath = "/api/v{version}/accounting/{period}/invoices/issued/{invoiceId}/payments";
        $httpBody = '';
        $queryParams = [];
        $headerParams = [];
        $formParams = [];
        $_header_accept = $this->apiClient->selectHeaderAccept(['application/json', 'text/json', 'text/html', 'application/xml', 'text/xml']);
        if (!is_null($_header_accept)) {
            $headerParams['Accept'] = $_header_accept;
        }
        $headerParams['Content-Type'] = $this->apiClient->selectHeaderContentType([]);

        // path params
        if ($invoice_id !== null) {
            $resourcePath = str_replace(
                "{" . "invoiceId" . "}",
                $this->apiClient->getSerializer()->toPathValue($invoice_id),
                $resourcePath
            );
        }
        // path params
        if ($version !== null) {
            $resourcePath = str_replace(
                "{" . "version" . "}",
                $this->apiClient->getSerializer()->toPathValue($version),
                $resourcePath
            );
        }
        // path params
        if ($period !== null) {
            $resourcePath = str_replace(
                "{" . "period" . "}",
                $this->apiClient->getSerializer()->toPathValue($period),
                $resourcePath
            );
        }
        // default format to json
        $resourcePath = str_replace("{format}", "json", $resourcePath);

        
        // for model (json/xml)
        if (isset($_tempBody)) {
            $httpBody = $_tempBody; // $_tempBody is the method argument, if present
        } elseif (count($formParams) > 0) {
            $httpBody = $formParams; // for HTTP post (form)
        }
        // this endpoint requires OAuth (access token)
        if (strlen($this->apiClient->getConfig()->getAccessToken()) !== 0) {
            $headerParams['Authorization'] = 'Bearer ' . $this->apiClient->getConfig()->getAccessToken();
        }
        // make the API Call
        try {
            list($response, $statusCode, $httpHeader) = $this->apiClient->callApi(
                $resourcePath,
                'GET',
                $queryParams,
                $httpBody,
                $headerParams,
                '\Contasimple\Swagger\Client\Model\ApiListResultDocumentPaymentApiModel',
                '/api/v{version}/accounting/{period}/invoices/issued/{invoiceId}/payments'
            );

            return [$this->apiClient->getSerializer()->deserialize($response, '\Contasimple\Swagger\Client\Model\ApiListResultDocumentPaymentApiModel', $httpHeader), $statusCode, $httpHeader];
        } catch (ApiException $e) {
            switch ($e->getCode()) {
                case 200:
                    $data = $this->apiClient->getSerializer()->deserialize($e->getResponseBody(), '\Contasimple\Swagger\Client\Model\ApiListResultDocumentPaymentApiModel', $e->getResponseHeaders());
                    $e->setResponseObject($data);
                    break;
            }

            throw $e;
        }
    }
}
