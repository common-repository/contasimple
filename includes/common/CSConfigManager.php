<?php
/**
 *  @author    Contasimple S.L. <soporte@contasimple.com>
 *  @copyright 2021 Contasimple S.L.
 */

/**
 * Interface CSConfigManager
 *
 * Defines the methods that clients must implement to persist the API configuration parameters such as
 * the API access token and refresh token, amongst others.
 *
 * The CSService makes use of this interface internally to retrieve/store the access and refresh tokens automatically when
 * the session expires.
 */
interface CSConfigManager
{
	/**
	 * Loads the configuration.
	 *
	 * Populates all CSConfig class parameters with the values stored by the client.
	 *
	 * @see CSConfig
	 *
	 * @return CSConfig A CSConfig object filled with client configuration if stored previously, or with empty values
	 *                  if the integration has not yet been connected with the CS API.
	 *
	 */
	public function loadConfiguration();

	/**
	 * Stores the configuration.
	 *
	 * Saves all CSConfig class parameters for later usage. For example in a database.
	 *
	 * @param CSConfig $config A CSConfig object filled with values to save.
	 */
	public function storeConfiguration(CSConfig $config);
}
