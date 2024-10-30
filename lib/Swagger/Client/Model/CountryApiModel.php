<?php
/**
 * CountryApiModel
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
 * CountryApiModel Class Doc Comment
 *
 * @category    Class */
 // @description Defines the information for a country
/**
 * @package     Contasimple\Swagger\Client
 * @author      http://github.com/swagger-api/swagger-codegen
 * @license     http://www.apache.org/licenses/LICENSE-2.0 Apache License v2
 * @link        https://github.com/swagger-api/swagger-codegen
 */
class CountryApiModel implements ArrayAccess
{
    /**
      * The original name of the model.
      * @var string
      */
    protected static $swaggerModelName = 'CountryApiModel';

    /**
      * Array of property to type mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerTypes = [
        'id' => 'int',
        'name' => 'string',
        'iso_code_alpha2' => 'string',
        'iso_code_alpha3' => 'string',
        'display_on_account_configuration' => 'bool',
        'nif_validation_regex' => 'string',
        'fiscal_regions' => '\Contasimple\Swagger\Client\Model\FiscalRegionApiModel[]'
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
        'iso_code_alpha2' => 'isoCodeAlpha2',
        'iso_code_alpha3' => 'isoCodeAlpha3',
        'display_on_account_configuration' => 'displayOnAccountConfiguration',
        'nif_validation_regex' => 'nifValidationRegex',
        'fiscal_regions' => 'fiscalRegions'
    ];


    /**
     * Array of attributes to setter functions (for deserialization of responses)
     * @var string[]
     */
    protected static $setters = [
        'id' => 'setId',
        'name' => 'setName',
        'iso_code_alpha2' => 'setIsoCodeAlpha2',
        'iso_code_alpha3' => 'setIsoCodeAlpha3',
        'display_on_account_configuration' => 'setDisplayOnAccountConfiguration',
        'nif_validation_regex' => 'setNifValidationRegex',
        'fiscal_regions' => 'setFiscalRegions'
    ];


    /**
     * Array of attributes to getter functions (for serialization of requests)
     * @var string[]
     */
    protected static $getters = [
        'id' => 'getId',
        'name' => 'getName',
        'iso_code_alpha2' => 'getIsoCodeAlpha2',
        'iso_code_alpha3' => 'getIsoCodeAlpha3',
        'display_on_account_configuration' => 'getDisplayOnAccountConfiguration',
        'nif_validation_regex' => 'getNifValidationRegex',
        'fiscal_regions' => 'getFiscalRegions'
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
        $this->container['iso_code_alpha2'] = isset($data['iso_code_alpha2']) ? $data['iso_code_alpha2'] : null;
        $this->container['iso_code_alpha3'] = isset($data['iso_code_alpha3']) ? $data['iso_code_alpha3'] : null;
        $this->container['display_on_account_configuration'] = isset($data['display_on_account_configuration']) ? $data['display_on_account_configuration'] : null;
        $this->container['nif_validation_regex'] = isset($data['nif_validation_regex']) ? $data['nif_validation_regex'] : null;
        $this->container['fiscal_regions'] = isset($data['fiscal_regions']) ? $data['fiscal_regions'] : null;
    }

    /**
     * show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalid_properties = [];
        return $invalid_properties;
    }

    /**
     * validate all the properties in the model
     * return true if all passed
     *
     * @return bool True if all properteis are valid
     */
    public function valid()
    {
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
     * @param int $id The identifier of the country
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
     * @param string $name The name of the country
     * @return $this
     */
    public function setName($name)
    {
        $this->container['name'] = $name;

        return $this;
    }

    /**
     * Gets iso_code_alpha2
     * @return string
     */
    public function getIsoCodeAlpha2()
    {
        return $this->container['iso_code_alpha2'];
    }

    /**
     * Sets iso_code_alpha2
     * @param string $iso_code_alpha2 The country ISO Alpha-2 code
     * @return $this
     */
    public function setIsoCodeAlpha2($iso_code_alpha2)
    {
        $this->container['iso_code_alpha2'] = $iso_code_alpha2;

        return $this;
    }

    /**
     * Gets iso_code_alpha3
     * @return string
     */
    public function getIsoCodeAlpha3()
    {
        return $this->container['iso_code_alpha3'];
    }

    /**
     * Sets iso_code_alpha3
     * @param string $iso_code_alpha3 The country ISO Alpha-3 code
     * @return $this
     */
    public function setIsoCodeAlpha3($iso_code_alpha3)
    {
        $this->container['iso_code_alpha3'] = $iso_code_alpha3;

        return $this;
    }

    /**
     * Gets display_on_account_configuration
     * @return bool
     */
    public function getDisplayOnAccountConfiguration()
    {
        return $this->container['display_on_account_configuration'];
    }

    /**
     * Sets display_on_account_configuration
     * @param bool $display_on_account_configuration Indicates that the country is available for account configuration
     * @return $this
     */
    public function setDisplayOnAccountConfiguration($display_on_account_configuration)
    {
        $this->container['display_on_account_configuration'] = $display_on_account_configuration;

        return $this;
    }

    /**
     * Gets nif_validation_regex
     * @return string
     */
    public function getNifValidationRegex()
    {
        return $this->container['nif_validation_regex'];
    }

    /**
     * Sets nif_validation_regex
     * @param string $nif_validation_regex The regular expression to use for validation NIF for this country
     * @return $this
     */
    public function setNifValidationRegex($nif_validation_regex)
    {
        $this->container['nif_validation_regex'] = $nif_validation_regex;

        return $this;
    }

    /**
     * Gets fiscal_regions
     * @return \Contasimple\Swagger\Client\Model\FiscalRegionApiModel[]
     */
    public function getFiscalRegions()
    {
        return $this->container['fiscal_regions'];
    }

    /**
     * Sets fiscal_regions
     * @param \Contasimple\Swagger\Client\Model\FiscalRegionApiModel[] $fiscal_regions The list of fiscal regions on the country
     * @return $this
     */
    public function setFiscalRegions($fiscal_regions)
    {
        $this->container['fiscal_regions'] = $fiscal_regions;

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
