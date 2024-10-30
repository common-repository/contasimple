<?php

namespace Contasimple\Tests;

require_once("../../autoload.php");

use \Contasimple\Plugins\Common\InvoiceHelper;
use \Contasimple\Swagger\Client\Model\InvoiceApiModel;
use \Contasimple\Swagger\Client\Model\FiscalRegionApiModel;

class OperationTypeTest extends \PHPUnit\Framework\TestCase {

	public function test_when_invoice_has_YES_taxes_should_always_return_NACIONAL()
	{
		$nif = "";
		$vat = null;
		$customerCountry = "";
		$customerState = "";
		$issuerCountry = "";
		$issuerFiscalRegion = "";
		$invoiceHasTaxes = true; // Only this is relevant.

		$expected = InvoiceApiModel::OPERATION_TYPE_NACIONAL;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_EMPTY_and_fiscalRegion_is_ES_PENINSULA_goes_to_clientes_varios_should_return_NACIONAL_EXENTA()
	{
		$nif = "";
		$vat = null;
		$customerCountry = "";
		$customerState = "";
		$issuerCountry = "";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_ES_PENINSULA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_NACIONAL_EXENTA;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_EMPTY_and_fiscalRegion_is_NOT_ES_PENINSULA_goes_to_clientes_varios_should_return_NACIONAL()
	{
		$nif = ""; // Clientes varios.
		$vat = null;
		$customerCountry = "";
		$customerState = "";
		$issuerCountry = "";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_OTRA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_NACIONAL;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_VALID_and_fiscalRegion_is_ES_PENINSULA_and_customerCountry_is_US_should_return_EXPORTACION()
	{
		$nif = "46978414Q";
		$vat = null;
		$customerCountry = "US";
		$customerState = "AR";
		$issuerCountry = "ES";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_ES_PENINSULA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_EXPORTACION;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_VALID_and_fiscalRegion_is_ES_PENINSULA_and_customerCountry_is_ES_but_state_is_GC_should_return_EXPORTACION()
	{
		$nif = "46978414Q";
		$vat = null;
		$customerCountry = "ES";
		$customerState = "GC";
		$issuerCountry = "ES";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_ES_PENINSULA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_EXPORTACION;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_VALID_and_fiscalRegion_is_ES_PENINSULA_and_customerCountry_is_ES_but_state_is_ML_should_return_EXPORTACION()
	{
		$nif = "46978414Q";
		$vat = null;
		$customerCountry = "ES";
		$customerState = "ML";
		$issuerCountry = "ES";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_ES_PENINSULA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_EXPORTACION;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_VALID_and_fiscalRegion_is_NOT_ES_PENINSULA_and_customerCountry_is_ES_should_return_EXPORTACION()
	{
		$nif = "46978414Q";
		$vat = null;
		$customerCountry = "ES";
		$customerState = "B";
		$issuerCountry = "";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_OTRA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_EXPORTACION;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_VALID_and_fiscalRegion_is_ES_CANARIAS_and_customerCountry_is_ES_and_state_is_NOT_IN_CANARY_should_return_EXPORTACION()
	{
		$nif = "46978414Q";
		$vat = null;
		$customerCountry = "ES";
		$customerState = "B";
		$issuerCountry = "ES";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_ES_CANARIAS;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_EXPORTACION;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_VALID_and_fiscalRegion_is_ES_CANARIAS_and_customerCountry_is_ES_and_state_is_GC_should_return_NACIONAL()
	{
		$nif = "46978414Q";
		$vat = null;
		$customerCountry = "ES";
		$customerState = "GC";
		$issuerCountry = "ES";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_ES_CANARIAS;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_NACIONAL;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_VALID_and_fiscalRegion_is_ES_CANARIAS_and_customerCountry_is_ES_and_state_is_ML_should_return_EXPORTACION()
	{
		$nif = "46978414Q";
		$vat = null;
		$customerCountry = "ES";
		$customerState = "ML";
		$issuerCountry = "ES";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_ES_CANARIAS;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_EXPORTACION;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_VALID_and_fiscalRegion_is_ES_CEUTA_MELILLA_and_customerCountry_is_ES_and_state_is_ML_should_return_NACIONAL()
	{
		$nif = "46978414Q";
		$vat = null;
		$customerCountry = "ES";
		$customerState = "ML";
		$issuerCountry = "ES";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_ES_CEUTA_Y_MELILLA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_NACIONAL;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_VALID_and_fiscalRegion_is_ES_PENINSULA_and_customerCountry_is_ES_and_state_is_ANY_NOT_IN_CANARY_CEUTA_MELILLA_should_return_NACIONAL_EXENTA()
	{
		$nif = "46978414Q";
		$vat = null;
		$customerCountry = "ES";
		$customerState = "B";
		$issuerCountry = "";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_ES_PENINSULA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_NACIONAL_EXENTA;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_VALID_and_fiscalRegion_is_OTRA_and_issuerCountry_is_ES_and_customerCountry_is_ES_should_return_NACIONAL()
	{
		$nif = "46978414Q";
		$vat = null;
		$customerCountry = "ES";
		$customerState = "B";
		$issuerCountry = "ES";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_OTRA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_NACIONAL;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_VALID_and_fiscalRegion_is_OTRA_and_issuerCountry_is_ES_and_customerCountry_is_FR_should_return_EXPORTACION()
	{
		$nif = "46978414Q";
		$vat = null;
		$customerCountry = "FR";
		$customerState = "";
		$issuerCountry = "ES";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_OTRA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_EXPORTACION;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_VALID_and_fiscalRegion_is_OTRA_and_issuerCountry_is_FR_and_customerCountry_is_ES_should_return_EXPORTACION()
	{
		$nif = "46978414Q";
		$vat = null;
		$customerCountry = "ES";
		$customerState = "";
		$issuerCountry = "FR";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_OTRA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_EXPORTACION;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_VALID_VIES_and_fiscalRegion_is_ES_PENINSULA_and_customerCountry_is_IR_should_return_INTRACUMUNITARIA()
	{
		$nif = "";
		$vat = "IE6388047V"; // Tested against Google Limited Ireland.;
		$customerCountry = "IE";
		$customerState = "";
		$issuerCountry = "";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_ES_PENINSULA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_INTRACUMUNITARIA;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_VALID_VIES_and_fiscalRegion_is_NOT_ES_PENINSULA_and_customerCountry_is_IR_should_return_EXPORTACION()
	{
		$nif = "";
		$vat = "IE6388047V"; // Tested against Google Limited Ireland.;
		$customerCountry = "IE";
		$customerState = "";
		$issuerCountry = "";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_OTRA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_EXPORTACION;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_INVALID_VIES_and_fiscalRegion_is_ES_PENINSULA_and_customerCountry_is_FR_should_return_NACIONAL_EXENTA()
	{
		$nif = "";
		$vat = "FR12345678901"; // Invalid
		$customerCountry = "FR";
		$customerState = "";
		$issuerCountry = "";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_ES_PENINSULA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_NACIONAL_EXENTA;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}

	public function test_when_invoice_has_NO_taxes_and_nif_INVALID_VIES_and_fiscalRegion_is_NOT_ES_PENINSULA_and_issuerCountry_is_IE_and_customerCountry_is_FR_should_return_EXPORTACION()
	{
		$nif = "";
		$vat = "FR12345678901"; // Invalid
		$customerCountry = "FR";
		$customerState = "";
		$issuerCountry = "IE";
		$issuerFiscalRegion = FiscalRegionApiModel::CODE_OTRA;
		$invoiceHasTaxes = false;

		$expected = InvoiceApiModel::OPERATION_TYPE_EXPORTACION;
		$result = InvoiceHelper::getOperationType($nif, $vat, $customerCountry, $customerState, $issuerCountry, $issuerFiscalRegion, $invoiceHasTaxes);

		$this->assertEquals($expected, $result);
	}
}

