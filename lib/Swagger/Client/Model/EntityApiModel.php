<?php
/**
 * EntityApiModel
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
 * EntityApiModel Class Doc Comment
 *
 * @category    Class */
 // @description Defines the information for working with entities
/**
 * @package     Contasimple\Swagger\Client
 * @author      http://github.com/swagger-api/swagger-codegen
 * @license     http://www.apache.org/licenses/LICENSE-2.0 Apache License v2
 * @link        https://github.com/swagger-api/swagger-codegen
 */
class EntityApiModel implements ArrayAccess
{
    /**
      * The original name of the model.
      * @var string
      */
    protected static $swaggerModelName = 'EntityApiModel';

    /**
      * Array of property to type mappings. Used for (de)serialization
      * @var string[]
      */
    protected static $swaggerTypes = [
        'id' => 'int',
        'company_id' => 'int',
        'type' => 'string',
        'organization' => 'string',
        'name' => 'string',
        'firstname' => 'string',
        'lastname' => 'string',
        'nif' => 'string',
        'address' => 'string',
        'province' => 'string',
        'city' => 'string',
        'country' => 'string',
        'country_id' => 'int',
        'postal_code' => 'string',
        'phone' => 'string',
        'fax' => 'string',
        'email' => 'string',
        'notes' => 'string',
        'url' => 'string',
        'custom_field1' => 'string',
        'custom_field2' => 'string',
        'latitude' => 'double',
        'longitude' => 'double',
        'discount_percentage' => 'double'
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
        'company_id' => 'companyId',
        'type' => 'type',
        'organization' => 'organization',
        'name' => 'name',
        'firstname' => 'firstname',
        'lastname' => 'lastname',
        'nif' => 'nif',
        'address' => 'address',
        'province' => 'province',
        'city' => 'city',
        'country' => 'country',
        'country_id' => 'countryId',
        'postal_code' => 'postalCode',
        'phone' => 'phone',
        'fax' => 'fax',
        'email' => 'email',
        'notes' => 'notes',
        'url' => 'url',
        'custom_field1' => 'customField1',
        'custom_field2' => 'customField2',
        'latitude' => 'latitude',
        'longitude' => 'longitude',
        'discount_percentage' => 'discountPercentage'
    ];


    /**
     * Array of attributes to setter functions (for deserialization of responses)
     * @var string[]
     */
    protected static $setters = [
        'id' => 'setId',
        'company_id' => 'setCompanyId',
        'type' => 'setType',
        'organization' => 'setOrganization',
        'name' => 'setName',
        'firstname' => 'setFirstname',
        'lastname' => 'setLastname',
        'nif' => 'setNif',
        'address' => 'setAddress',
        'province' => 'setProvince',
        'city' => 'setCity',
        'country' => 'setCountry',
        'country_id' => 'setCountryId',
        'postal_code' => 'setPostalCode',
        'phone' => 'setPhone',
        'fax' => 'setFax',
        'email' => 'setEmail',
        'notes' => 'setNotes',
        'url' => 'setUrl',
        'custom_field1' => 'setCustomField1',
        'custom_field2' => 'setCustomField2',
        'latitude' => 'setLatitude',
        'longitude' => 'setLongitude',
        'discount_percentage' => 'setDiscountPercentage'
    ];


    /**
     * Array of attributes to getter functions (for serialization of requests)
     * @var string[]
     */
    protected static $getters = [
        'id' => 'getId',
        'company_id' => 'getCompanyId',
        'type' => 'getType',
        'organization' => 'getOrganization',
        'name' => 'getName',
        'firstname' => 'getFirstname',
        'lastname' => 'getLastname',
        'nif' => 'getNif',
        'address' => 'getAddress',
        'province' => 'getProvince',
        'city' => 'getCity',
        'country' => 'getCountry',
        'country_id' => 'getCountryId',
        'postal_code' => 'getPostalCode',
        'phone' => 'getPhone',
        'fax' => 'getFax',
        'email' => 'getEmail',
        'notes' => 'getNotes',
        'url' => 'getUrl',
        'custom_field1' => 'getCustomField1',
        'custom_field2' => 'getCustomField2',
        'latitude' => 'getLatitude',
        'longitude' => 'getLongitude',
        'discount_percentage' => 'getDiscountPercentage'
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

    const TYPE_ISSUER = 'Issuer';
    const TYPE_TARGET = 'Target';
    const TYPE_NONE = 'None';
    const TYPE_OWNER = 'Owner';
    const TYPE_INVOICEPAYMENT = 'Invoicepayment';
    const TYPE_INVOICE_DOMICILIATION = 'InvoiceDomiciliation';



    /**
     * Gets allowable values of the enum
     * @return string[]
     */
    public function getTypeAllowableValues()
    {
        return [
            self::TYPE_ISSUER,
            self::TYPE_TARGET,
            self::TYPE_NONE,
            self::TYPE_OWNER,
            self::TYPE_INVOICEPAYMENT,
            self::TYPE_INVOICE_DOMICILIATION,
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
        $this->container['company_id'] = isset($data['company_id']) ? $data['company_id'] : null;
        $this->container['type'] = isset($data['type']) ? $data['type'] : null;
        $this->container['organization'] = isset($data['organization']) ? $data['organization'] : null;
        $this->container['name'] = isset($data['name']) ? $data['name'] : null;
        $this->container['firstname'] = isset($data['firstname']) ? $data['firstname'] : null;
        $this->container['lastname'] = isset($data['lastname']) ? $data['lastname'] : null;
        $this->container['nif'] = isset($data['nif']) ? $data['nif'] : null;
        $this->container['address'] = isset($data['address']) ? $data['address'] : null;
        $this->container['province'] = isset($data['province']) ? $data['province'] : null;
        $this->container['city'] = isset($data['city']) ? $data['city'] : null;
        $this->container['country'] = isset($data['country']) ? $data['country'] : null;
        $this->container['country_id'] = isset($data['country_id']) ? $data['country_id'] : null;
        $this->container['postal_code'] = isset($data['postal_code']) ? $data['postal_code'] : null;
        $this->container['phone'] = isset($data['phone']) ? $data['phone'] : null;
        $this->container['fax'] = isset($data['fax']) ? $data['fax'] : null;
        $this->container['email'] = isset($data['email']) ? $data['email'] : null;
        $this->container['notes'] = isset($data['notes']) ? $data['notes'] : null;
        $this->container['url'] = isset($data['url']) ? $data['url'] : null;
        $this->container['custom_field1'] = isset($data['custom_field1']) ? $data['custom_field1'] : null;
        $this->container['custom_field2'] = isset($data['custom_field2']) ? $data['custom_field2'] : null;
        $this->container['latitude'] = isset($data['latitude']) ? $data['latitude'] : null;
        $this->container['longitude'] = isset($data['longitude']) ? $data['longitude'] : null;
        $this->container['discount_percentage'] = isset($data['discount_percentage']) ? $data['discount_percentage'] : null;
    }

    /**
     * show all the invalid properties with reasons.
     *
     * @return array invalid properties with reasons
     */
    public function listInvalidProperties()
    {
        $invalid_properties = [];
        $allowed_values = ["Issuer", "Target", "None", "Owner", "Invoicepayment", "InvoiceDomiciliation"];
        if (!in_array($this->container['type'], $allowed_values)) {
            $invalid_properties[] = "invalid value for 'type', must be one of #{allowed_values}.";
        }

        if ($this->container['organization'] === null) {
            $invalid_properties[] = "'organization' can't be null";
        }
        if ($this->container['nif'] === null) {
            $invalid_properties[] = "'nif' can't be null";
        }
        if (!is_null($this->container['latitude']) && ($this->container['latitude'] > 90.0)) {
            $invalid_properties[] = "invalid value for 'latitude', must be smaller than or equal to 90.0.";
        }

        if (!is_null($this->container['latitude']) && ($this->container['latitude'] < -90.0)) {
            $invalid_properties[] = "invalid value for 'latitude', must be bigger than or equal to -90.0.";
        }

        if (!is_null($this->container['longitude']) && ($this->container['longitude'] > 180.0)) {
            $invalid_properties[] = "invalid value for 'longitude', must be smaller than or equal to 180.0.";
        }

        if (!is_null($this->container['longitude']) && ($this->container['longitude'] < -180.0)) {
            $invalid_properties[] = "invalid value for 'longitude', must be bigger than or equal to -180.0.";
        }

        if (!is_null($this->container['discount_percentage']) && ($this->container['discount_percentage'] > 100.0)) {
            $invalid_properties[] = "invalid value for 'discount_percentage', must be smaller than or equal to 100.0.";
        }

        if (!is_null($this->container['discount_percentage']) && ($this->container['discount_percentage'] < 0.0)) {
            $invalid_properties[] = "invalid value for 'discount_percentage', must be bigger than or equal to 0.0.";
        }

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
        $allowed_values = ["Issuer", "Target", "None", "Owner", "Invoicepayment", "InvoiceDomiciliation"];
        if (!in_array($this->container['type'], $allowed_values)) {
            return false;
        }
        if ($this->container['organization'] === null) {
            return false;
        }
        if ($this->container['nif'] === null) {
            return false;
        }
        if ($this->container['latitude'] > 90.0) {
            return false;
        }
        if ($this->container['latitude'] < -90.0) {
            return false;
        }
        if ($this->container['longitude'] > 180.0) {
            return false;
        }
        if ($this->container['longitude'] < -180.0) {
            return false;
        }
        if ($this->container['discount_percentage'] > 100.0) {
            return false;
        }
        if ($this->container['discount_percentage'] < 0.0) {
            return false;
        }
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
     * @param int $id The identifier of the entity
     * @return $this
     */
    public function setId($id)
    {
        $this->container['id'] = $id;

        return $this;
    }

    /**
     * Gets company_id
     * @return int
     */
    public function getCompanyId()
    {
        return $this->container['company_id'];
    }

    /**
     * Sets company_id
     * @param int $company_id The company that owns the entity
     * @return $this
     */
    public function setCompanyId($company_id)
    {
        $this->container['company_id'] = $company_id;

        return $this;
    }

    /**
     * Gets type
     * @return string
     */
    public function getType()
    {
        return $this->container['type'];
    }

    /**
     * Sets type
     * @param string $type The type of entity
     * @return $this
     */
    public function setType($type)
    {
        $allowed_values = array('Issuer', 'Target', 'None', 'Owner', 'Invoicepayment', 'InvoiceDomiciliation');
        if (!is_null($type) && (!in_array($type, $allowed_values))) {
            throw new \InvalidArgumentException("Invalid value for 'type', must be one of 'Issuer', 'Target', 'None', 'Owner', 'Invoicepayment', 'InvoiceDomiciliation'");
        }
        $this->container['type'] = $type;

        return $this;
    }

    /**
     * Gets organization
     * @return string
     */
    public function getOrganization()
    {
        return $this->container['organization'];
    }

    /**
     * Sets organization
     * @param string $organization The organization name of the entity
     * @return $this
     */
    public function setOrganization($organization)
    {
        $this->container['organization'] = $organization;

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
     * @param string $name The person name (required for company type = autonomo)
     * @return $this
     */
    public function setName($name)
    {
        $this->container['name'] = $name;

        return $this;
    }

    /**
     * Gets firstname
     * @return string
     */
    public function getFirstname()
    {
        return $this->container['firstname'];
    }

    /**
     * Sets firstname
     * @param string $firstname The person firstname (required for company type = autonomo)
     * @return $this
     */
    public function setFirstname($firstname)
    {
        $this->container['firstname'] = $firstname;

        return $this;
    }

    /**
     * Gets lastname
     * @return string
     */
    public function getLastname()
    {
        return $this->container['lastname'];
    }

    /**
     * Sets lastname
     * @param string $lastname The person lastname (required for company type = autonomo)
     * @return $this
     */
    public function setLastname($lastname)
    {
        $this->container['lastname'] = $lastname;

        return $this;
    }

    /**
     * Gets nif
     * @return string
     */
    public function getNif()
    {
        return $this->container['nif'];
    }

    /**
     * Sets nif
     * @param string $nif The NIF number of the entity
     * @return $this
     */
    public function setNif($nif)
    {
        $this->container['nif'] = $nif;

        return $this;
    }

    /**
     * Gets address
     * @return string
     */
    public function getAddress()
    {
        return $this->container['address'];
    }

    /**
     * Sets address
     * @param string $address The entity address
     * @return $this
     */
    public function setAddress($address)
    {
        $this->container['address'] = $address;

        return $this;
    }

    /**
     * Gets province
     * @return string
     */
    public function getProvince()
    {
        return $this->container['province'];
    }

    /**
     * Sets province
     * @param string $province The entity province
     * @return $this
     */
    public function setProvince($province)
    {
        $this->container['province'] = $province;

        return $this;
    }

    /**
     * Gets city
     * @return string
     */
    public function getCity()
    {
        return $this->container['city'];
    }

    /**
     * Sets city
     * @param string $city The entity city
     * @return $this
     */
    public function setCity($city)
    {
        $this->container['city'] = $city;

        return $this;
    }

    /**
     * Gets country
     * @return string
     */
    public function getCountry()
    {
        return $this->container['country'];
    }

    /**
     * Sets country
     * @param string $country The entity country. Do not use (available only for old clients). Use the countryId otherwise.
     * @return $this
     */
    public function setCountry($country)
    {
        $this->container['country'] = $country;

        return $this;
    }

    /**
     * Gets country_id
     * @return int
     */
    public function getCountryId()
    {
        return $this->container['country_id'];
    }

    /**
     * Sets country_id
     * @param int $country_id The country identifier
     * @return $this
     */
    public function setCountryId($country_id)
    {
        $this->container['country_id'] = $country_id;

        return $this;
    }

    /**
     * Gets postal_code
     * @return string
     */
    public function getPostalCode()
    {
        return $this->container['postal_code'];
    }

    /**
     * Sets postal_code
     * @param string $postal_code The entity postal code
     * @return $this
     */
    public function setPostalCode($postal_code)
    {
        $this->container['postal_code'] = $postal_code;

        return $this;
    }

    /**
     * Gets phone
     * @return string
     */
    public function getPhone()
    {
        return $this->container['phone'];
    }

    /**
     * Sets phone
     * @param string $phone The entity phone number
     * @return $this
     */
    public function setPhone($phone)
    {
        $this->container['phone'] = $phone;

        return $this;
    }

    /**
     * Gets fax
     * @return string
     */
    public function getFax()
    {
        return $this->container['fax'];
    }

    /**
     * Sets fax
     * @param string $fax The entity fax number
     * @return $this
     */
    public function setFax($fax)
    {
        $this->container['fax'] = $fax;

        return $this;
    }

    /**
     * Gets email
     * @return string
     */
    public function getEmail()
    {
        return $this->container['email'];
    }

    /**
     * Sets email
     * @param string $email The entity email address
     * @return $this
     */
    public function setEmail($email)
    {
        $this->container['email'] = $email;

        return $this;
    }

    /**
     * Gets notes
     * @return string
     */
    public function getNotes()
    {
        return $this->container['notes'];
    }

    /**
     * Sets notes
     * @param string $notes The entity notes
     * @return $this
     */
    public function setNotes($notes)
    {
        $this->container['notes'] = $notes;

        return $this;
    }

    /**
     * Gets url
     * @return string
     */
    public function getUrl()
    {
        return $this->container['url'];
    }

    /**
     * Sets url
     * @param string $url The entity URL address
     * @return $this
     */
    public function setUrl($url)
    {
        $this->container['url'] = $url;

        return $this;
    }

    /**
     * Gets custom_field1
     * @return string
     */
    public function getCustomField1()
    {
        return $this->container['custom_field1'];
    }

    /**
     * Sets custom_field1
     * @param string $custom_field1 The entity custom field 1
     * @return $this
     */
    public function setCustomField1($custom_field1)
    {
        $this->container['custom_field1'] = $custom_field1;

        return $this;
    }

    /**
     * Gets custom_field2
     * @return string
     */
    public function getCustomField2()
    {
        return $this->container['custom_field2'];
    }

    /**
     * Sets custom_field2
     * @param string $custom_field2 The entity custom field 2
     * @return $this
     */
    public function setCustomField2($custom_field2)
    {
        $this->container['custom_field2'] = $custom_field2;

        return $this;
    }

    /**
     * Gets latitude
     * @return double
     */
    public function getLatitude()
    {
        return $this->container['latitude'];
    }

    /**
     * Sets latitude
     * @param double $latitude Map latitude of the contact. Does not have to match the exact address latitude  A value between: [-90, +90]
     * @return $this
     */
    public function setLatitude($latitude)
    {

        if (!is_null($latitude) && ($latitude > 90.0)) {
            throw new \InvalidArgumentException('invalid value for $latitude when calling EntityApiModel., must be smaller than or equal to 90.0.');
        }
        if (!is_null($latitude) && ($latitude < -90.0)) {
            throw new \InvalidArgumentException('invalid value for $latitude when calling EntityApiModel., must be bigger than or equal to -90.0.');
        }

        $this->container['latitude'] = $latitude;

        return $this;
    }

    /**
     * Gets longitude
     * @return double
     */
    public function getLongitude()
    {
        return $this->container['longitude'];
    }

    /**
     * Sets longitude
     * @param double $longitude Map longitude of the contact. Does not have to match the exact address longitude  A value between: [-180,+180]
     * @return $this
     */
    public function setLongitude($longitude)
    {

        if (!is_null($longitude) && ($longitude > 180.0)) {
            throw new \InvalidArgumentException('invalid value for $longitude when calling EntityApiModel., must be smaller than or equal to 180.0.');
        }
        if (!is_null($longitude) && ($longitude < -180.0)) {
            throw new \InvalidArgumentException('invalid value for $longitude when calling EntityApiModel., must be bigger than or equal to -180.0.');
        }

        $this->container['longitude'] = $longitude;

        return $this;
    }

    /**
     * Gets discount_percentage
     * @return double
     */
    public function getDiscountPercentage()
    {
        return $this->container['discount_percentage'];
    }

    /**
     * Sets discount_percentage
     * @param double $discount_percentage The default discount percentage to apply on the concept lines for this entity
     * @return $this
     */
    public function setDiscountPercentage($discount_percentage)
    {

        if (!is_null($discount_percentage) && ($discount_percentage > 100.0)) {
            throw new \InvalidArgumentException('invalid value for $discount_percentage when calling EntityApiModel., must be smaller than or equal to 100.0.');
        }
        if (!is_null($discount_percentage) && ($discount_percentage < 0.0)) {
            throw new \InvalidArgumentException('invalid value for $discount_percentage when calling EntityApiModel., must be bigger than or equal to 0.0.');
        }

        $this->container['discount_percentage'] = $discount_percentage;

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
