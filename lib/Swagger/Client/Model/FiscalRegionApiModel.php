<?php
/**
 * FiscalRegionApiModel
 *
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

namespace Contasimple\Swagger\Client\Model;

use \ArrayAccess;

/**
 * FiscalRegionApiModel Class Doc Comment
 *
 * @category    Class */
 // @description Defines the information for a fiscal region
/**
 * @package     Contasimple\Swagger\Client
 * @author      http://github.com/swagger-api/swagger-codegen
 * @license     http://www.apache.org/licenses/LICENSE-2.0 Apache License v2
 * @link        https://github.com/swagger-api/swagger-codegen
 */
class FiscalRegionApiModel implements ArrayAccess
{
    /**
      * The original name of the model.
      * @var string
      */
    protected static $swaggerModelName = 'FiscalRegionApiModel';

    /**
      * Array of property to type mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerTypes = [
        'id' => 'int',
        'name' => 'string',
        'taxes_enabled' => 'bool',
        'allow_customization' => 'bool',
        'vat_name' => 'string',
        're_name' => 'string',
        'retention_name' => 'string',
        'company_identifier_name' => 'string',
        'culture' => 'string',
        'code' => 'string',
        'enable_re' => 'bool',
        'vat_modes' => '\Contasimple\Swagger\Client\Model\VatModeApiModel[]'
    ];

    public static function swaggerTypes()
    {
        return self::$swaggerTypes;
    }

    /**
     * Array of attributes where the key is the local name, and the value is the original name
     * @var string[]
     */
    protected static $attributeMap = [
        'id' => 'id',
        'name' => 'name',
        'taxes_enabled' => 'taxesEnabled',
        'allow_customization' => 'allowCustomization',
        'vat_name' => 'vatName',
        're_name' => 'reName',
        'retention_name' => 'retentionName',
        'company_identifier_name' => 'companyIdentifierName',
        'culture' => 'culture',
        'code' => 'code',
        'enable_re' => 'enableRe',
        'vat_modes' => 'vatModes'
    ];


    /**
     * Array of attributes to setter functions (for deserialization of responses)
     * @var string[]
     */
    protected static $setters = [
        'id' => 'setId',
        'name' => 'setName',
        'taxes_enabled' => 'setTaxesEnabled',
        'allow_customization' => 'setAllowCustomization',
        'vat_name' => 'setVatName',
        're_name' => 'setReName',
        'retention_name' => 'setRetentionName',
        'company_identifier_name' => 'setCompanyIdentifierName',
        'culture' => 'setCulture',
        'code' => 'setCode',
        'enable_re' => 'setEnableRe',
        'vat_modes' => 'setVatModes'
    ];


    /**
     * Array of attributes to getter functions (for serialization of requests)
     * @var string[]
     */
    protected static $getters = [
        'id' => 'getId',
        'name' => 'getName',
        'taxes_enabled' => 'getTaxesEnabled',
        'allow_customization' => 'getAllowCustomization',
        'vat_name' => 'getVatName',
        're_name' => 'getReName',
        'retention_name' => 'getRetentionName',
        'company_identifier_name' => 'getCompanyIdentifierName',
        'culture' => 'getCulture',
        'code' => 'getCode',
        'enable_re' => 'getEnableRe',
        'vat_modes' => 'getVatModes'
    ];

    public static function attributeMap()
    {
        return self::$attributeMap;
    }

    public static function setters()
    {
        return self::$setters;
    }

    public static function getters()
    {
        return self::$getters;
    }

    const CODE_ES_PENINSULA = 'es_Peninsula';
    const CODE_ES_CANARIAS = 'es_Canarias';
    const CODE_ES_CEUTA_Y_MELILLA = 'es_CeutaYMelilla';
    const CODE_ES_NAVARRA = 'es_Navarra';
    const CODE_ES_ALABA = 'es_Alaba';
    const CODE_ES_BIZCAYA = 'es_Bizcaya';
    const CODE_ES_GUIPUZCOA = 'es_Guipuzcoa';
    const CODE_MX = 'mx';
    const CODE_AR = 'ar';
    const CODE_CO = 'co';
    const CODE_CL = 'cl';
    const CODE_PE = 'pe';
    const CODE_VE = 've';
    const CODE_EC = 'ec';
    const CODE_HN = 'hn';
    const CODE_US = 'us';
    const CODE_UK = 'uk';
    const CODE_IE = 'ie';
    const CODE_OTRA = 'otra';



    /**
     * Gets allowable values of the enum
     * @return string[]
     */
    public function getCodeAllowableValues()
    {
        return [
            self::CODE_ES_PENINSULA,
            self::CODE_ES_CANARIAS,
            self::CODE_ES_CEUTA_Y_MELILLA,
            self::CODE_ES_NAVARRA,
            self::CODE_ES_ALABA,
            self::CODE_ES_BIZCAYA,
            self::CODE_ES_GUIPUZCOA,
            self::CODE_MX,
            self::CODE_AR,
            self::CODE_CO,
            self::CODE_CL,
            self::CODE_PE,
            self::CODE_VE,
            self::CODE_EC,
            self::CODE_HN,
            self::CODE_US,
            self::CODE_UK,
            self::CODE_IE,
            self::CODE_OTRA,
        ];
    }


    /**
     * Associative array for storing property values
     * @var mixed[]
     */
    protected $container = [];

    /**
     * Constructor
     * @param mixed[] $data Associated array of property values initializing the model
     */
    public function __construct(array $data = null)
    {
        $this->container['id'] = isset($data['id']) ? $data['id'] : null;
        $this->container['name'] = isset($data['name']) ? $data['name'] : null;
        $this->container['taxes_enabled'] = isset($data['taxes_enabled']) ? $data['taxes_enabled'] : null;
        $this->container['allow_customization'] = isset($data['allow_customization']) ? $data['allow_customization'] : null;
        $this->container['vat_name'] = isset($data['vat_name']) ? $data['vat_name'] : null;
        $this->container['re_name'] = isset($data['re_name']) ? $data['re_name'] : null;
        $this->container['retention_name'] = isset($data['retention_name']) ? $data['retention_name'] : null;
        $this->container['company_identifier_name'] = isset($data['company_identifier_name']) ? $data['company_identifier_name'] : null;
        $this->container['culture'] = isset($data['culture']) ? $data['culture'] : null;
        $this->container['code'] = isset($data['code']) ? $data['code'] : null;
        $this->container['enable_re'] = isset($data['enable_re']) ? $data['enable_re'] : null;
        $this->container['vat_modes'] = isset($data['vat_modes']) ? $data['vat_modes'] : null;
    }

    /**
     * show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalid_properties = [];
        /* Custom change! If you ever regenerate this ApiModel via Swagger Codegen, keep this manual change!
        $allowed_values = ["es_Peninsula", "es_Canarias", "es_CeutaYMelilla", "es_Navarra", "es_Alaba", "es_Bizcaya", "es_Guipuzcoa", "mx", "ar", "co", "cl", "pe", "ve", "ec", "hn", "otra"];
        if (!in_array($this->container['code'], $allowed_values)) {
            $invalid_properties[] = "invalid value for 'code', must be one of #{allowed_values}.";
        }*/

        return $invalid_properties;
    }

    /**
     * validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properteis are valid
     */
    public function valid()
    {   /* Custom change! If you ever regenerate this ApiModel via Swagger Codegen, keep this manual change!
        $allowed_values = ["es_Peninsula", "es_Canarias", "es_CeutaYMelilla", "es_Navarra", "es_Alaba", "es_Bizcaya", "es_Guipuzcoa", "mx", "ar", "co", "cl", "pe", "ve", "ec", "hn", "us", "uk", "ie", "otra"];
        if (!in_array($this->container['code'], $allowed_values)) {
            return false;
        }*/
        return true;
    }


    /**
     * Gets id
     * @return int
     */
    public function getId()
    {
        return $this->container['id'];
    }

    /**
     * Sets id
     * @param int $id The identifier of the fiscal regoion
     * @return $this
     */
    public function setId($id)
    {
        $this->container['id'] = $id;

        return $this;
    }

    /**
     * Gets name
     * @return string
     */
    public function getName()
    {
        return $this->container['name'];
    }

    /**
     * Sets name
     * @param string $name The name of the fiscal region
     * @return $this
     */
    public function setName($name)
    {
        $this->container['name'] = $name;

        return $this;
    }

    /**
     * Gets taxes_enabled
     * @return bool
     */
    public function getTaxesEnabled()
    {
        return $this->container['taxes_enabled'];
    }

    /**
     * Sets taxes_enabled
     * @param bool $taxes_enabled Indicates if the fiscall region allows to use the taxing module
     * @return $this
     */
    public function setTaxesEnabled($taxes_enabled)
    {
        $this->container['taxes_enabled'] = $taxes_enabled;

        return $this;
    }

    /**
     * Gets allow_customization
     * @return bool
     */
    public function getAllowCustomization()
    {
        return $this->container['allow_customization'];
    }

    /**
     * Sets allow_customization
     * @param bool $allow_customization Indicates if the fiscal region allows to customize its settings
     * @return $this
     */
    public function setAllowCustomization($allow_customization)
    {
        $this->container['allow_customization'] = $allow_customization;

        return $this;
    }

    /**
     * Gets vat_name
     * @return string
     */
    public function getVatName()
    {
        return $this->container['vat_name'];
    }

    /**
     * Sets vat_name
     * @param string $vat_name The name of the VAT tax
     * @return $this
     */
    public function setVatName($vat_name)
    {
        $this->container['vat_name'] = $vat_name;

        return $this;
    }

    /**
     * Gets re_name
     * @return string
     */
    public function getReName()
    {
        return $this->container['re_name'];
    }

    /**
     * Sets re_name
     * @param string $re_name The name of the RE tax
     * @return $this
     */
    public function setReName($re_name)
    {
        $this->container['re_name'] = $re_name;

        return $this;
    }

    /**
     * Gets retention_name
     * @return string
     */
    public function getRetentionName()
    {
        return $this->container['retention_name'];
    }

    /**
     * Sets retention_name
     * @param string $retention_name The name of the retention field
     * @return $this
     */
    public function setRetentionName($retention_name)
    {
        $this->container['retention_name'] = $retention_name;

        return $this;
    }

    /**
     * Gets company_identifier_name
     * @return string
     */
    public function getCompanyIdentifierName()
    {
        return $this->container['company_identifier_name'];
    }

    /**
     * Sets company_identifier_name
     * @param string $company_identifier_name The name of the Company Identifier (NIF title)
     * @return $this
     */
    public function setCompanyIdentifierName($company_identifier_name)
    {
        $this->container['company_identifier_name'] = $company_identifier_name;

        return $this;
    }

    /**
     * Gets culture
     * @return string
     */
    public function getCulture()
    {
        return $this->container['culture'];
    }

    /**
     * Sets culture
     * @param string $culture The culture code of the fiscal region
     * @return $this
     */
    public function setCulture($culture)
    {
        $this->container['culture'] = $culture;

        return $this;
    }

    /**
     * Gets code
     * @return string
     */
    public function getCode()
    {
        return $this->container['code'];
    }

    /**
     * Sets code
     * @param string $code The code of the fiscal region
     * @return $this
     */
    public function setCode($code)
    {   /* Custom change! If you ever regenerate this ApiModel via Swagger Codegen, keep this manual change!
        $allowed_values = array('es_Peninsula', 'es_Canarias', 'es_CeutaYMelilla', 'es_Navarra', 'es_Alaba', 'es_Bizcaya', 'es_Guipuzcoa', 'mx', 'ar', 'co', 'cl', 'pe', 've', 'ec', 'hn', 'us', 'uk', 'ie', 'otra');
        if (!is_null($code) && (!in_array($code, $allowed_values))) {
            throw new \InvalidArgumentException("Invalid value for 'code', must be one of 'es_Peninsula', 'es_Canarias', 'es_CeutaYMelilla', 'es_Navarra', 'es_Alaba', 'es_Bizcaya', 'es_Guipuzcoa', 'mx', 'ar', 'co', 'cl', 'pe', 've', 'ec', 'hn', 'us', 'uk', 'ie', 'otra'");
        }*/
        $this->container['code'] = $code;

        return $this;
    }

    /**
     * Gets enable_re
     * @return bool
     */
    public function getEnableRe()
    {
        return $this->container['enable_re'];
    }

    /**
     * Sets enable_re
     * @param bool $enable_re Indicates if the fiscal region allows enabled the RE tax
     * @return $this
     */
    public function setEnableRe($enable_re)
    {
        $this->container['enable_re'] = $enable_re;

        return $this;
    }

    /**
     * Gets vat_modes
     * @return \Contasimple\Swagger\Client\Model\VatModeApiModel[]
     */
    public function getVatModes()
    {
        return $this->container['vat_modes'];
    }

    /**
     * Sets vat_modes
     * @param \Contasimple\Swagger\Client\Model\VatModeApiModel[] $vat_modes The list of VAT modes on the fiscal region
     * @return $this
     */
    public function setVatModes($vat_modes)
    {
        $this->container['vat_modes'] = $vat_modes;

        return $this;
    }
    /**
     * Returns true if offset exists. False otherwise.
     * @param  integer $offset Offset
     * @return boolean
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset)
    {
        return isset($this->container[$offset]);
    }

    /**
     * Gets offset.
     * @param  integer $offset Offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return isset($this->container[$offset]) ? $this->container[$offset] : null;
    }

    /**
     * Sets value based on offset.
     * @param  integer $offset Offset
     * @param  mixed   $value  Value to be set
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /**
     * Unsets offset.
     * @param  integer $offset Offset
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset)
    {
        unset($this->container[$offset]);
    }

    /**
     * Gets the string presentation of the object
     * @return string
     */
    public function __toString()
    {
        if (defined('JSON_PRETTY_PRINT')) { // use JSON pretty print
            return json_encode(\Contasimple\Swagger\Client\ObjectSerializer::sanitizeForSerialization($this), JSON_PRETTY_PRINT);
        }

        return json_encode(\Contasimple\Swagger\Client\ObjectSerializer::sanitizeForSerialization($this));
    }
}
