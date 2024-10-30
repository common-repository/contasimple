<?php
/**
 *  @author    Contasimple S.L. <soporte@contasimple.com>
 *  @copyright 2017 Contasimple S.L.
 */

class CSLogger
{
	// Implementing Singleton pattern to allow the logger to be reused across all plugin classes.
	private static $instance;

	// If set to false, the logger functions won't generate output.
	// Reconsider if this is a good design practice or it would be better to delegate this task to the plugin
	// main class.
	protected $enabled;

	// The filename where we want to log.
	protected $filename = '';

	// An internal identifier to make easier to keep track of concurrent requests logging.
	protected $uid = '';

	/**
	 * @return bool
	 */
	public function isEnabled()
	{
		return $this->enabled;
	}

	/**
	 * CSLogger constructor.
	 *
	 * Some variables are passed to the constructor to decouple the class to the calling CMS.
	 *
	 * @param bool $enabled
	 * @param string $plugin_version
	 * @param string $cms_version
	 *
	 * @throws Exception
	 */
	private function __construct($enabled, $plugin_version, $cms_version, $logs_dir)
	{
		$this->enabled = $enabled;

		if ($this->isEnabled()) {
			if (empty($logs_dir)) {
				$logs_dir =  dirname( __FILE__ ) . 'logs' . DIRECTORY_SEPARATOR;
			}
			if (!file_exists($logs_dir)) {
				try {
					mkdir($logs_dir, 0777, true);
				} catch (\Exception $e) {
					throw new \Exception("Could not create " . $logs_dir );
				}
			}

			$this->setFilename($logs_dir . "contasimple_".date('d-m-Y').".log");

			if (!file_exists($this->getFilename())) {
				try {
					$the_plugs = get_option('active_plugins');
					$cs_config = get_option( 'contasimple_settings_account' );
					$wc_options = get_option( 'woocommerce_integration-contasimple_settings' );

					if (!empty($cs_config) && $cs_config instanceof CSConfig && !empty($cs_config->getApiKey('apikey'))) {
						$apiKey = $cs_config->getApiKey('apikey');
						// Keep the last 8 digits and set all the remaining as asterisks.
						$apiKey = str_repeat('*', strlen($apiKey) - 8) . substr($apiKey, -8);
					} else {
						$apiKey = 'Not yet set';
					}

                    if (isset($_SERVER['SERVER_SOFTWARE'])) {
                        $serverSw = $_SERVER['SERVER_SOFTWARE'];
                    } else {
                        $serverSw = 'N/A';
                    }

                    if (isset($_SERVER['HTTP_HOST'])) {
                        $httpHost = $_SERVER['HTTP_HOST'];
                    } else {
                        $httpHost = 'N/A';
                    }

					// Only disable this feature if explicitly set in the settings.
					if ( !empty( $wc_options ) && isset( $wc_options['enable_mutex'] ) && 'no' === $wc_options['enable_mutex'] ) {
						$concurrencyControlEnabled = false;
					} else  {
						$concurrencyControlEnabled = true; // default
					}

					$lockFileExists = file_exists(CS_PLUGIN_PATH . 'admin' . DIRECTORY_SEPARATOR . 'class-contasimple-admin.php.lock');

					$this->log('Basic system information:');
					$this->log('CMS Version: ' . $cms_version);
					$this->log('Plugin Version: ' . $plugin_version);
					$this->log('Plugin APIKey: ' . $apiKey);
					$this->log('Plugin Mutex Enabled: ' . ($concurrencyControlEnabled ? 'true' : 'false'));
					$this->log('Plugin stale lock file detected: ' . ($lockFileExists ? 'true' : 'false'));
					$this->log('Server: ' . $serverSw);
					$this->log('Site: ' . $httpHost);
					$this->log('OS: ' . php_uname());
					$this->log('PHP Version: ' . PHP_VERSION);
					$this->log('PHP SAPI Name: ' . php_sapi_name());
					$this->log('PHP intl extension loaded: ' . (extension_loaded("intl") ? 'true' : 'false'));
					$this->log('PHP SUHOSIN extension loaded: ' . (extension_loaded( 'suhosin' ) ? 'true' : 'false'));
					$this->log('PHP max_execution_time: ' . ini_get('max_execution_time'));
					$this->log('PHP max_file_uploads: ' . ini_get('max_file_uploads'));
					$this->log('PHP post_max_size: ' . ini_get('post_max_size'));
					$this->log('PHP max_input_time: ' . ini_get('max_input_time'));
					$this->log('PHP max_input_vars: ' . ini_get('max_input_vars'));
					$this->log('PHP memory_limit: ' . ini_get('memory_limit'));
					$this->log('PHP display_errors: ' . strval(ini_get('display_errors')));
					$this->log('CMS Setting - Product prices include tax: ' . get_option( 'woocommerce_prices_include_tax', 'no' ));
					$this->log('CMS Setting - Round tax at subtotal: ' . get_option( 'woocommerce_tax_round_at_subtotal' ));
					$this->log('CMS Setting - Debug mode enabled: ' . (WP_DEBUG === false ? 'false' : 'true' ));
					$this->log('CMS Active plugins: ' . wp_json_encode($the_plugs));
				} catch (\Exception $e) {
					$this->log($e->getMessage());
				}
			}
		}
	}

	public static function getDailyLogger($enabled = true, $plugin_version = '', $cms_version = '', $logs_dir = null)
	{
		if (!self::$instance instanceof self) {
			self::$instance = new self($enabled, $plugin_version, $cms_version, $logs_dir);
		}

		return self::$instance;
	}

	public function logTransactionStart($message = null, $newUid = null)
	{
		if ($this->isEnabled()) {
			$this->uid = $newUid;

			$this->log(" ++++ [START OF TRANSACTION] ++++ ");
			$this->log("Request URI: " . $_SERVER['REQUEST_URI']);

			if (!empty($_POST) && is_array($_POST)) {
				$this->log(json_encode(array_filter($_POST), JSON_UNESCAPED_UNICODE));
			}

			if (!empty($message)) {
				$this->log($message);
			}
		}
	}

	public function logTransactionEnd($message = null)
	{
		if ($this->isEnabled()) {
			if (!empty($message)) {
				$this->log($message);
			}

			$this->log(" ---- [END OF TRANSACTION] ---- ");
		}
	}

	/**
	 * Write the desired message into file
	 *
	 * @param string message
	 * @param level
	 */
	public function log($my_message)
	{
		if ($this->isEnabled()) {
			if (!is_string($my_message)) {
				$my_message = print_r($my_message, true);
			}

			if (!empty($this->uid)) {
				$processMsg = '(' . $this->uid . ')';
			} else {
				$processMsg = '';
			}

			$message = date('Y/m/d - H:i:s'). ': ' . $processMsg . ' ' . $my_message . "\r\n";
			return (bool)file_put_contents($this->getFilename(), $message, FILE_APPEND);
		}
	}

	/**
	 * Setter for the filename
	 *
	 * Checks that file can be written
	 *
	 * @param string $filename
	 */
	public function setFilename($filename)
	{
		if (is_writable(dirname($filename))) {
			$this->filename = $filename;
		} else {
			die('Could not create file into directory: '.dirname($filename));
		}
	}

	/**
	 * Getter for filename var
	 *
	 * Cannot be empty
	 *
	 * @param string message
	 * @param level
	 *
	 * @return string The name of the file
	 */
	public function getFilename()
	{
		if (empty($this->filename)) {
			die('You need to specify a file name.');
		}

		return $this->filename;
	}
}
