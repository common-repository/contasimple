<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://www.contasimple.com
 * @since      1.0.0
 *
 * @package    contasimple
 * @subpackage contasimple/admin
 * @author     Contasimple S.L. <soporte@contasimple.com>
 */

use Contasimple\Plugins\Common\InvoiceHelper;
use Contasimple\Swagger\Client\Model\FiscalRegionApiModel;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Class for the Admin backoffice
 *
 * Defines the plugin name, version, two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript, and other admin logic.
 *
 * @since      1.0.0
 */
class Contasimple_Admin {

	/**
	 * Contasimple Service
	 *
	 * @var        CSService $cs
	 *
	 * @since      1.0.0
	 */
	private $cs;

	/**
	 * File logger to catch errors
	 *
	 * @var        CSLogger $logger
	 *
	 * @since      1.0.0
	 */
	protected $logger;

	/**
	 * @var string Identifier of this request execution.
	 *
	 * @since 1.26.0
	 */
	private $processId;

	/*
	 * Default time for the concurrency lock.
	 */
	private $max_lock_time = 30;

	private static $instance = null;

	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new Contasimple_Admin();
		}
		return self::$instance;
	}

	/**
	 * Getter for the config class
	 *
	 * @return     CSConfig
	 *
	 * @since      1.0.0
	 */
	public function get_config() {

		$csConfigManager = new Contasimple_WC_Config_Manager();
		return $csConfigManager->loadConfiguration();
	}

	/**
	 * Setter for the config class
	 *
	 * @param      CSConfig $config Instance of the Config class to store.
	 *
	 * @since      1.0.0
	 */
	public function set_config( $config ) {

		$csConfigManager = new Contasimple_WC_Config_Manager();
		$csConfigManager->storeConfiguration( $config );
	}


	/**
	 * Getter for Contasimple settings as WP Options
	 *
	 * @return     mixed|void
	 *
	 * @since      1.0.0
	 */
	public static function get_config_static() {

		return get_option( 'contasimple_settings_account' );
	}

	/**
	 * Getter for the Service class
	 *
	 * @return     CSService
	 *
	 * @since      1.0.0
	 */
	public function get_service() {

		return $this->cs;
	}

	/**
	 * Getter for the Logger class
	 *
	 * @return  CSLogger
	 *
	 * @since   1.5
	 */
	public function getLogger() {
		return $this->logger;
	}

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 */
	protected function __construct() {

		$this->logger = CSLogger::getDailyLogger();
		$this->cs = new CSService( new Contasimple_WC_Config_Manager());
		$this->processId = md5(microtime(true).mt_Rand());

		$max_exec_time = ini_get('max_execution_time');

		if ($max_exec_time !== false && is_numeric($max_exec_time) && $max_exec_time > 0) {
			$this->max_lock_time = +$max_exec_time;
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( 'bootstrap', plugin_dir_url( __FILE__ ) . 'css/bootstrap-cs.css', array(), '3.3.7' );
		wp_enqueue_style( 'jquery-ui-css' );

		// Seems like after 2.2 it is always loaded.
		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
			wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css', array(), WC_VERSION );
		}

		$deps = array( 'woocommerce_admin_styles' );
		wp_enqueue_style( Contasimple::$plugin_name, plugin_dir_url( __FILE__ ) . 'css/contasimple-admin.css', $deps, Contasimple::$version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		if ( $this->check_bootstrap_loaded() == false ) {
			wp_enqueue_script( 'bootstrap', plugin_dir_url( __FILE__ ) . 'js/bootstrap.min.js', array( 'jquery' ), '3.3.7', true );
		}

		wp_enqueue_script( 'cs_gtag', '//www.googletagmanager.com/gtag/js?id=UA-9928674-21#asyncload', array() );
		wp_enqueue_script( 'cs_ga_analytics', plugin_dir_url( __FILE__ ) . 'js/analytics.js' );
		wp_enqueue_script( Contasimple::$plugin_name, plugin_dir_url( __FILE__ ) . 'js/contasimple-configuration.js', array(
			'jquery',
			'jquery-ui-datepicker',
			'jquery-tiptip',
		), Contasimple::$version, false );
		wp_enqueue_script( Contasimple::$plugin_name . '_orders', plugin_dir_url( __FILE__ ) . 'js/contasimple-orders.js', array(
			'jquery',
			'jquery-ui-tooltip',
		), Contasimple::$version, false );

		// Localize the script with new data.
		$translation_array = array(
			'msg_syncing' => __('Syncing...', 'contasimple'),
			'msg_stopping' => __('Removing...', 'contasimple'),
			'msg_log_not_found' => __( 'There are no registered logs for the selected day.', 'contasimple' ),
			'msg_new_series_added_successfully' => __( 'New series added successfully. You can now select it from the above dropwdown controls.',  'contasimple' ),
			'msg_new_series_add_error' => __( 'Error trying to create a new series. Please contact with Contasimple.', 'contasimple' ),
			'msg_no_active_companies_found' => __('You do not have access to any company. You must create a company in Contasimple before linking the plugin for WooCommerce.', 'contasimple')
		);

		wp_localize_script( Contasimple::$plugin_name, 'js_translations', $translation_array );
		wp_localize_script( Contasimple::$plugin_name . '_orders', 'js_translations', $translation_array );
	}

	/**
	 * Check if bootstrap js is already loaded
	 * Note: This method is not infallible.
	 *
	 * @since 1.5
	 *
	 * @return bool True if bootstrap is already loaded, false otherwise.
	 */
	public function check_bootstrap_loaded() {

		global $wp_scripts;

		$bootstrap_enqueued = false;

		foreach ( $wp_scripts->registered as $script ) {
			if ( !empty( $script->src) &&
				(  stristr( $script->src, 'bootstrap.min.js') != false ||
			       stristr( $script->src, 'bootstrap.js') != false ||
			       stristr( $script->src, 'bootstrap-modal.js') != false ||
			       stristr( $script->src, 'bootstrap-modal.min.js') != false
			    ) &&
			    wp_script_is( $script->handle, $list = 'enqueued' )
			) {
				$bootstrap_enqueued = true;

				if ( !empty( $this->logger ) ) {
					$this->logger->log('Skipped loading CS own bootstrap.js file. Detected potential conflict with bootstrap js: [' . $script->handle . '] => ' . $script->src . '.' );
				}

				break;
			}
		}

		return $bootstrap_enqueued;
	}

	public function add_async_to_gtag( $url ) {

		if ( strpos( $url, '#asyncload' ) === false )
			return $url;
		else
			return str_replace( '#asyncload', '', $url )."' async='async";
	}

	/**
	 * Login to CS API via APIKEY
	 *
	 * This method is called via AJAX and returns an AJAX response.
	 * If login is successful, will allow the user to continue with the wizard. Otherwise it will be locked in the
	 * configuration screen.
	 *
	 * @since    1.0.0
	 */
	public function cs_login() {

		global $woocommerce;

		$this->logger->logTransactionStart( 'Called: ' . __METHOD__ );

		$result = array(
			'message' => __( 'Connection successful', 'contasimple' ),
		);

		try {
			$this->check_ajax_wizard_allowed();

			$apikey = esc_attr( $_REQUEST['apikey'] ); // Input var okay.
			$user_agent = 'ContasimpleWooCommerce/' . Contasimple::$version . '-' . $woocommerce->version;

			$this->force_login();
			$this->cs = new CSService( new Contasimple_WC_Config_Manager() );
			$this->cs->connect( $apikey, $user_agent );

			$this->logger->log( 'Connection with APIKEY successful' );

			$companies_list = $this->cs->getCompanies();

			foreach ( $companies_list as $company ) {
				// $currency              = get_woocommerce_currencies()[ $company->getCountry()->getCurrency()->getShortName() ];
				$result['companies'][] = array(
					'id_option'    => $company->getId(),
					'name'         => $company->getDisplayName(),
					// 'currency'     => $currency,
					'country'      => $company->getCountry()->getName(),
					'fiscalRegion' => $company->getFiscalRegion()->getName(),
				);
			}
		} catch ( Exception $e ) {
			if ( $e->getMessage() === 'key is required.' ) {
				$result = array(
					'error'   => true,
					'message' => __( 'Please, enter a valid API Key', 'contasimple' ),
				);
			} elseif ( $e->getMessage() == 'Client is not registered in the system.' ) {
				$result = array(
					'error'   => true,
					'message' => __( 'Your API Key cannot be found in our system. Please, make sure it is correct. If you are still not registered, please register first in Contasimple.com', 'contasimple' ),
				);
			} elseif ( $e->getMessage() === 'invalid_grant' || strpos( $e->getMessage(), 'Authorization has been denied' ) !== false ) {
				$this->force_login();
				$result = array(
					'error'   => true,
					'message' => __( 'Invalid grant', 'contasimple' ),
				);
			} elseif ( strpos( $e->getMessage(), 'SSL certificate problem' ) !== false ) {
				$result = array(
					'error'   => true,
					'message' => __( 'Contasimple does not recognize the SSL certificate from this server. This might be caused by a bad SSL certificate configuration. Please check your system security settings and fix this problem before trying to connect again.', 'contasimple' ),
				);
			} elseif ( strpos( $e->getMessage(), 'Could not resolve' ) !== false ) {
				$result = array(
					'error'   => true,
					'message' => __( 'Could not resolve host. Please check that your server has internet access.', 'contasimple' ),
				);
			} elseif ( strpos( $e->getMessage(), 'Your user has been deactivated' ) !== false ) {
				$result = array(
					'error'   => true,
					'message' => $e->getMessage(),
				);
			} else {
				// Captured an unknown error, log.
				$this->logger->log( 'Unexpected exception thrown: ' . $e->getMessage() );

				$this->force_login();
				$result = array(
					'error'   => true,
					'message' => __( 'An unknown error occurred. Please try again, and if it persists, please contact Contasimple.', 'contasimple' ),
				);
			}
		}

		$this->logger->logTransactionEnd();
		echo wp_json_encode( $result );

		wp_die(); // this is required to terminate immediately and return a proper response.
	}

	/**
	 * Stores the order status option that the user choose in the configuration wizard (either 'completed' or 'processing')
	 * that will trigger the synchronization of the orders as Contasimple invoices.
	 *
	 * @since    1.11.0
	 */
	public function cs_select_sync_order_status() {

		$this->logger->logTransactionStart( 'Called: ' . __METHOD__ );

		$result = array(
			'message' => __( 'Order status to sync saved successfully', 'contasimple' ),
		);

		try {
			$syncOrderStatus = $_REQUEST['syncOrderStatus'];

			if ( ! empty( $syncOrderStatus ) ) {
				if ( 'completed' == $syncOrderStatus || 'processing' == $syncOrderStatus || 'on-hold' == $syncOrderStatus ) {
					$wc_options = get_option( 'woocommerce_integration-contasimple_settings' );

					if ( false !== $wc_options ) {
						$wc_options['wc_minimum_status_to_sync'] = $syncOrderStatus;
					} else {
						$wc_options = array(
							'wc_minimum_status_to_sync' => $syncOrderStatus
						);
					}
					update_option( 'woocommerce_integration-contasimple_settings', $wc_options );

				} else {
					throw new Exception('Invalid value for order status to sync.');
				}
			} else {
				throw new Exception('Empty value for order status to sync.');
			}
		} catch ( Exception $e ) {
			$this->logger->log( 'Error trying to save the minimum order status to sync invoices to Contasimple. Exception message: ' . $e->getMessage() );

			$this->force_login();
			$result = array(
				'error'   => true,
				'message' => __( 'An unknown error occurred. Please try again, and if it persists, please contact Contasimple.', 'contasimple' ),
			);
		}

		$this->logger->logTransactionEnd();
		echo wp_json_encode( $result );

		wp_die(); // this is required to terminate immediately and return a proper response.
	}

	/**
	 * Add NIF to edit order screen
	 *
	 * @param WC_Order $order The order to get the customer NIF for.
	 *
	 * @since    1.0.0
	 */
	public function add_custom_field_nif_checkout_edit_order( $order ) {

		$config_company_identifier_name = $this->get_company_identifier_name();

		if ( !empty( get_option( 'woocommerce_integration-contasimple_settings' ) )
			&& !empty( get_option( 'woocommerce_integration-contasimple_settings' )['nif_custom_text'])
		) {
			$nif_label = get_option( 'woocommerce_integration-contasimple_settings' )['nif_custom_text'];
		} elseif ( !empty( $config_company_identifier_name ) ) {
			$nif_label = sprintf( __('%s / Company identifier', 'contasimple'), $config_company_identifier_name );
		} else {
			$nif_label = __( 'Company identifier', 'contasimple' );
		}

		$nif = Contasimple_WC_Backward_Compatibility::get_order_meta( $order->get_id(), 'NIF', true );

		echo '<p><strong>' . $nif_label . ':</strong> ' . esc_attr( $nif ) . '</p>';
	}

	/**
	 * Creates the invoice and resumes sync queue when an order is completed.
	 *
	 * Complete here means that payment also has been collected. This is typically considered an 'invoiceable' state.
	 *
	 * @param int $order_id The order id.
	 *
	 * @since    1.0.0
	 */
	public function order_status_completed( $order_id ) {

		$this->logger->logTransactionStart( 'Called: ' . __METHOD__, $this->processId );

		if ( ! empty( $order_id ) ) {
			$this->maybe_create_invoice_and_resume_queue( $order_id );
		} else {
			$this->logger->log( 'Empty order_id found on status_completed hook.' );
		}

		$this->logger->logTransactionEnd();
	}

	/**
	 * Creates the invoice and resumes sync queue when an order is processing.
	 *
	 * Processing here means that payment also has been collected. This is typically considered an 'invoiceable' state.
	 *
	 * Note: Only if the user choose the default sync status for invoices as 'Processing' in the configuration
	 * section, instead of 'Completed'. If the selected status is 'On-hold' it should also sync because
	 * the processing status is considered the next step after on-hold, so it follows.
	 * Otherwise the invoice is delayed to the 'Completed' status (default).
	 *
	 * @param int $order_id The order id.
	 *
	 * @throws Exception
	 *
	 * @since    1.11.0
	 */
	public function order_status_processing( $order_id ) {

		$wc_options = get_option( 'woocommerce_integration-contasimple_settings' );

		if (
			! empty( $wc_options )
			&& array_key_exists('wc_minimum_status_to_sync', $wc_options)
			&& ('processing' === $wc_options['wc_minimum_status_to_sync'] || 'on-hold' === $wc_options['wc_minimum_status_to_sync'] )
		) {

			$this->logger->logTransactionStart( 'Called: ' . __METHOD__, $this->processId );

			if ( ! empty( $order_id ) ) {
				$this->maybe_create_invoice_and_resume_queue( $order_id );
			} else {
				$this->logger->log( 'Empty order_id found on status_processing hook.' );
			}

			$this->logger->logTransactionEnd();
		}
	}

	/**
	 * Creates the invoice and resumes sync queue when an order is in On-hold status.
	 *
	 * Note: This means that the invoice will be synced when immediately created, regardless of payment status.
	 *
	 * @param $order_id
	 *
	 * @throws Exception
	 *
	 * @since    1.19.0
	 */
	public function order_status_on_hold( $order_id ) {

		$wc_options = get_option( 'woocommerce_integration-contasimple_settings' );

		if (
			! empty( $wc_options )
			&& array_key_exists('wc_minimum_status_to_sync', $wc_options)
			&& 'on-hold' === $wc_options['wc_minimum_status_to_sync']
		) {

			$this->logger->logTransactionStart( 'Called: ' . __METHOD__, $this->processId );

			if ( ! empty( $order_id ) ) {
				$this->maybe_create_invoice_and_resume_queue( $order_id );
			} else {
				$this->logger->log( 'Empty order_id found on status_on_hold hook.' );
			}

			$this->logger->logTransactionEnd();
		}
	}

	/**
	 * Creates an invoice and resumes sync queue when a refund occurs.
	 *
	 * Refund also known as order slip or credit note, 'factura simplificada' in Spain.
	 *
	 * @param    int   $refund_get_id Refund id passed down by the hook.
	 * @param    array $args          Data about the refund post.
	 *
	 * @since    1.0.0
	 */
	public function refund_created( $refund_get_id, $args ) {

		$this->logger->logTransactionStart( 'Called: ' . __METHOD__, $this->processId );

		if ( ! empty( $args['order_id'] ) ) {
			$this->maybe_create_invoice_and_resume_queue( $args['order_id'], $refund_get_id, $args );
		} else {
			$this->logger->log( 'Empty order_id found on refund_created hook.' );
		}

		$this->logger->logTransactionEnd();
	}

	/**
	 * Creates a ContasimpleInvoice post type, if it does not exist already, and resumes the sync queue.
	 *
	 * @param int  $order_id  The order id.
	 * @param null $refund_id (Optional) the refund id, only if the status was refunded.
	 * @param null $args      (Optional) args object passed down from the WC refund_created hook.
	 *
	 * @throws Exception
	 */
	public function maybe_create_invoice_and_resume_queue($order_id, $refund_id = null, $args = null ) {

		// Set lock.
		if ( $this->concurrency_control_enabled() ) {
			$newInvoiceMutex = new WebMutex(__FILE__ );

			if ( !$newInvoiceMutex->Lock( true, $this->max_lock_time ) )
			{
				$this->logger->log( "Error: Process $this->processId could not create a lock for new invoice for order $order_id." );
				return;
			}

			$this->logger->log( "Locked mutex for new invoice for order: $order_id. Critical section of process $this->processId begins..." );
		}

		$order_invoices = CS_Invoice_Sync::get_invoices_from_order( $order_id );

		if ( $this->invoice_can_be_added( $order_id, $order_invoices, $args ) ) {
			$contasimple_invoice_sync = CS_Invoice_Sync::create_empty( $order_id, $refund_id, $args );

			if ( ! empty( $contasimple_invoice_sync ) ) {
				if ( $this->properly_configured() ) {
					$contasimple_invoice_sync->companyID = $this->get_config()->getCompanyId();
					$contasimple_invoice_sync->save();
				}
			} else {
				if ( !empty( $refund_id ) ) {
					$this->logger->log( "Error creating an entry into the database for the order refund: " . $refund_id );
				} else {
					$this->logger->log( "Error creating an entry into the database for the order: " . $order_id );
				}
			}
		} else {
			$this->logger->log("The 'invoice_can_be_added_method' determined that this amount for the order id '$order_id' has already been added to sync.");
		}

		// Resume queue in any case.
		$this->resume_queue( false );

		// Release lock.
		if ( $this->concurrency_control_enabled() && is_object( $newInvoiceMutex ) ) {
			$newInvoiceMutex->Unlock();

			$this->logger->log( "Unlocked process $this->processId mutex for new invoice for order: $order_id." );
		}
	}

	/**
	 * Gets the number formatting series to apply for document sequencing.
	 *
	 * We have 3 possible choices for the mask/series.
	 * The series selected will depend on the customer order info provided.
	 *
	 * If it is a refund, we will always return a mask for the 'CREDIT NOTES' series (Also known as order slips, etc).
	 * If it is a completed order or equivalent, we will return either:
	 * - A mask for regular 'INVOICES' series (those that have valid fiscal information, AKA ADDRESS AND COMPANY ID/NIF/VAT)
	 * - A mask for a 'RECEIPT' series. (Also known as ticket or 'factura simplificada', since Spain requires a whole
	 *   different series if an invoice cannot be generated due to MISSING EITHER ADDRESS OR ID/NIF/VAT).
	 *
	 * @param int $order_id Order class ID to extract the needed data (NIF, Address is typically enforced by default in WC).
	 *
	 * @return \Contasimple\Swagger\Client\Model\InvoiceNumberingFormatApiModel An entity with the mask to use for
	 * the specific type of document that we need to create and a series id.
	 *
	 * @throws Exception If cannot get the desired info from the CS API.
	 *
	 * @since   1.16.0
	 *
	 * Note: This method replaces the old get_mask_from_order() method to use native Contasimple numbering series feature.
	 */
	public function get_series_from_order( $order_id ) {

		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
			try {
                $order = new WC_Order($order_id);
            }
            catch(\Exception $ex){
                $order = new WC_Order();
            }
			$order_status = $order->status;
		} else {
			$order        = wc_get_order( $order_id );
			$order_status = $order->get_status();
		}

		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
			$is_partial_refund = false;
		} else {
			$is_partial_refund = count( $order->get_refunds() ) > 0 ? true : false;
		}

		$nif     = Contasimple_WC_Backward_Compatibility::get_order_meta( $order_id, 'NIF', true );
		$options = get_option( 'woocommerce_integration-contasimple_settings' );

		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			$customer_country = $order->billing_country;
		} else {
			$customer_country = $order->get_billing_country();
		}

		if ( 'refunded' === $order_status || $is_partial_refund ) {
			if ( ! empty( $options['refunds_series'] ) ) {
				$serie = $this->cs->getInvoiceNumberingFormat( $options['refunds_series']);
			}
		} elseif ( ! empty( $nif ) && $this->is_valid_nif( $nif, $customer_country ) ) {
			if ( ! empty( $options['invoices_series'] ) ) {
				$serie = $this->cs->getInvoiceNumberingFormat( $options['invoices_series']);
			}
		} else {
			if ( ! empty( $options['receipts_series'] ) ) {
				$serie = $this->cs->getInvoiceNumberingFormat( $options['receipts_series']);
			}
		}

		return $serie;
	}

	/**
	 * Perform actions on order change (if needed be)
	 *
	 * @param   int    $order_id
	 * @param   string $old_status
	 * @param   string $new_status
	 *
	 * @deprecated
	 */
	public function order_status_changed( $order_id, $old_status, $new_status ) {

		if ( 'completed' === $new_status ) {
			// TODO Do we need to really do something if an order changes or not?
		}

		if ( 'refunded' === $new_status ) {
			// WC prior to 2.2 does not have the refund_created hook, so we must act here.
			if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
				$args = array();
				$args['order_id'] = $order_id;
				$this->refund_created(null, $args);
			}
		}
	}

	/**
	 * Send individual order info to Contasimple
	 *
	 * @param CS_Invoice_Sync $contasimple_invoice_sync An entity that keeps syncing info about this order.
	 *
	 * @return  array A response with info about the result
	 * @throws Exception
	 *
	 * @since    1.0.0
	 */
	public function create_or_update_invoice( $contasimple_invoice_sync ) {

		$order = null;
		$order_data = null;
		$cs_invoice = null;
		$cs_invoice_post = null;
		$type = 'invoice';

		try {
			$this->logger->log( 'Called: ' . __METHOD__ );

			// Retrieve WC order object from the ID stored in our sync table row.
			$order = Contasimple_WC_Backward_Compatibility::get_order_from_id( $contasimple_invoice_sync->order_id );

            // The data regarding the sync process, we will update with either SYNC or an ERROR code + message.
            $cs_invoice_post = get_post( $contasimple_invoice_sync->ID );

			// Order could have been deleted or fail for whatever reason unknown to us, abort right now.
			if ( empty( $order ) ) {
				// The exception will be caught in the main sync loop and mark the invoice with the pertinent error
				// code.
				throw new \Exception( '', MISSING_ORDER );
			}

			// Only for logging purposes.
			$order_data = Contasimple_WC_Backward_Compatibility::get_order_data( $order );

			$order_status = Contasimple_WC_Backward_Compatibility::get_order_status( $order );

			// Custom field stored from the checkout page, allows us to sync customer info.
			$nif = Contasimple_WC_Backward_Compatibility::get_order_meta( $contasimple_invoice_sync->order_id, 'NIF', true );

			// Get the currency iso and currency symbol of the WC order paid amount, ie: $ or €
			$wc_order_currency_iso = Contasimple_WC_Backward_Compatibility::get_currency( $order );
			$wc_order_currency_symbol = html_entity_decode( get_woocommerce_currency_symbol( $wc_order_currency_iso ) );

			// @since 1.16.0 the invoice date will be the date when it is synced, not the completed date of the order.
			// This simplifies the process a bit.
			$date = date_i18n( 'Y-m-d H:i:s' );

			// Format the CS period based on the completed date, ie: 2019-4T
			$period = Contasimple_CPT::get_period_from_date( $date );

			// We will use this to generate CS negative amounts by just multiplying the order items qty by 1 or -1.
			$sign = Contasimple_WC_Backward_Compatibility::get_invoice_sign_from_order( $order, $contasimple_invoice_sync->args);

			$type = $sign < 0 ?
				'refund' : 'invoice';

			// Get the mask / series.
			$series_to_use = $this->get_series_from_order( $contasimple_invoice_sync->order_id );
			$series_mask = $series_to_use->getMask();
			$series_id = $series_to_use->getId();

			$contasimple_invoice_sync->mask = $series_mask;

			// Since we keep the companyID inside the synced invoice data in the meta table, we have to make sure that
			// if the company has changed, the current sync reflects this (the rule used here is that the company who
			// syncs the invoice gets the authorship).
			$contasimple_invoice_sync->companyID = $this->get_config()->getCompanyId();

			// Store currencies, useful for later displaying on render each row in invoices table.
			$contasimple_invoice_sync->cs_currency = $this->get_config()->getCurrencySymbol();
			$contasimple_invoice_sync->order_currency = $wc_order_currency_symbol;

			update_post_meta( $contasimple_invoice_sync->ID, 'cs_currency', $contasimple_invoice_sync->cs_currency);
			update_post_meta( $contasimple_invoice_sync->ID, 'order_currency', $contasimple_invoice_sync->order_currency);

			// Unless the state indicates an error with a payment or an invoice state change, we will try to sync.
			if ( PAYMENT_SYNC_ERROR  !== (int) $contasimple_invoice_sync->state &&
			                 SYNC_OK !== (int) $contasimple_invoice_sync->state &&
				             CHANGED !== (int) $contasimple_invoice_sync->state
			) {
				if ( empty( $contasimple_invoice_sync->number ) ) {
					$this->logger->log( 'Retrieving next invoice number...' );
					try {
						$contasimple_invoice_sync->number = $this->cs->getNextInvoiceNumber( $period, $series_id );
					} catch (Exception $e) {
						if ( API_ERROR === (int) $e->getCode() ) {
							// Expand the generic API error message to add more detail.
							throw new Exception(__( 'Next invoice number could not be retrieved', 'contasimple' ) . '. ' . $e->getMessage(), API_ERROR, $e );
						} elseif ( GENERIC_ERROR === (int) $e->getCode() ) {
							// Display at least an error representative of the step of failure.
							throw new Exception('', NEXT_INVOICE_NUMBER_ERROR, $e );
						} else {
							// Errors like connection refused, unreachable host, etc.
							throw $e;
						}
					}
				}

				$contasimple_invoice_sync->update_sync_state( null, PENDING, null, $contasimple_invoice_sync->attempts + 1 );
				$this->logger->log( 'Internal sync status of object type ' . $type . ' updated to PENDING' );
				$this->logger->log( sprintf( 'Gathering data for invoice number [%1s] from period [%2s]... ', $contasimple_invoice_sync->number, $period ) );

				// Contasimple can only handle invoices generated with the same currency as the issuer company's currency set in Contasimple, due to the complexity in currency exchanges
				// Beware, however, that some countries might share the symbol and still they don't use the same currency, ex: USD dollar and Mexican dollar are both $
				Contasimple_WC_Helpers::validate_equivalent_currencies( $wc_order_currency_iso, $contasimple_invoice_sync->order_currency, $contasimple_invoice_sync->cs_currency );

				$attempt = 1;

				while ( $attempt <= 3 ) {
					// Attempt 1 tries syncing WC invoice line per line exactly.
					// Attempt 2 will try to group invoice lines per VAT type to avoid rounding issues.
					// Attempt 3 will desperately try to sync WC order final total amount and VAT amount only
					// if everything else failed.
					$this->logger->log( 'CS Invoice calculation strategy #' . $attempt );
					$cs_invoice = $this->convert_order_to_cs_invoice( $order, $contasimple_invoice_sync, $nif, $date, $series_id, $sign, $attempt );

					$args = $contasimple_invoice_sync->get_unserialized_args();

					if ( ! Contasimple_WC_Backward_Compatibility::is_partial_refund( $order, $contasimple_invoice_sync->refund_id ) ) {
						$this->logger->log( 'Validating order data against CS invoice... ' );
						$subtotal = ( $order->get_total() - $order->get_total_tax() );
						$tax_total = +$order->get_total_tax();
					} else {
						$this->logger->log( 'Validating order partial refund against CS invoice... ' );
						$subtotal = $contasimple_invoice_sync->get_partial_refund_total_base();
						$tax_total = $contasimple_invoice_sync->get_partial_refund_total_tax();
					}

					$success = $this->validate_invoice_amounts( $subtotal, $tax_total, $cs_invoice, $attempt, $sign );

					// If our result either fully matches (1)
					// or it meets the threshold allowed (0)
					// we can proceed with the sync.
					if ( $success >= 0 ) {
						$this->logger->log( 'Sending invoice...' );
						$cs_response = $this->send_invoice( $period, $cs_invoice, 0 );

						$contasimple_invoice_sync->amount = $cs_response->getTotalAmount();
						$this->logger->log( 'Invoice sent OK!' );

						if ( isset( $cs_invoice['warning'] ) ) {
							$contasimple_invoice_sync->update_sync_state( $cs_response->getId(), $cs_invoice['warning'], null );
							$this->logger->log( 'Status changed to SYNC_OK (WITH WARNINGS)' );
						} else if ( 0 == $success ) {
							$contasimple_invoice_sync->update_sync_state( $cs_response->getId(), SYNCED_WITH_ROUNDING_ISSUES, null );
							$this->logger->log( 'Status changed to SYNC_OK (WITH ROUNDING WARNINGS)' );
						} else if ( 1 == $success && ($attempt > 1) ) {
							$contasimple_invoice_sync->update_sync_state( $cs_response->getId(), SYNCED_WITH_ROUNDING_ISSUES, null );
							$this->logger->log( 'Status changed to SYNC_OK (WITH ROUNDING WARNINGS)' );
						} else {
							$contasimple_invoice_sync->update_sync_state( $cs_response->getId(), SYNC_OK, null );
							$this->logger->log( 'Status changed to SYNC_OK' );
						}

						// If we made it here without throwing an exception, the invoice is synced so next step is sending payments,
						// Unless:
						// - If it is an error scenario (as invoice sync is needed first)
						// - An update (TODO ignore, delete and resend payments, or send missing?).
						if ( 0 < $contasimple_invoice_sync->externalID  &&
						     INVOICE_REPEATED !== (int) $contasimple_invoice_sync->state &&
						     CHANGED !== (int) $contasimple_invoice_sync->state
						) {
							if ( ! empty( $args ) ) {
								$amount = $args['amount'] * $sign;
							} else {
								$amount = $order->get_total() * $sign;
							}

							$method = Contasimple_WC_Backward_Compatibility::get_order_payment_method( $order );

							$this->send_payment( $contasimple_invoice_sync, $period, $method, $amount );

							// Keep the current state so that if it was OK, still is OK, if there was an empty VAT for national warning,
							// the warning is still persisted (payment does not affect this), etc.
							$contasimple_invoice_sync->update_sync_state( $cs_response->getId(), $contasimple_invoice_sync->state, null );
						}

						// If there has been changes in the original order, update them in CS as well.
						if ( 0 < (int) $contasimple_invoice_sync->externalID &&
						     CHANGED === (int) $contasimple_invoice_sync->state
						) {
							$this->logger->log( 'Already exists. Updating invoice...' );
							$this->send_invoice( $period, $cs_invoice, $contasimple_invoice_sync->externalID );
							$this->logger->log( 'Invoice updated OK!' );

							$contasimple_invoice_sync->update_sync_state( $cs_response->getId(), SYNC_OK, null );
							$this->logger->log( 'Status changed to SYNC_OK' );
						}

						// At this point we consider 'SYNCED' being the same success state with or without payments
						// Otherwise if a payment exists but is missing then it is a special case of an error state.
						$response = $this->format_response_sync_ok( $contasimple_invoice_sync );

						$this->logger->log( 'Everything seems OK! Still, internal data logging is provided, just in case:' );
						$this->logger->log( '  >  wc_invoice = ' . wp_json_encode( $contasimple_invoice_sync, JSON_UNESCAPED_UNICODE ) );
						$this->logger->log( '  >  wc_order   = ' . wp_json_encode( $order_data, JSON_UNESCAPED_UNICODE ) );
						$this->logger->log( '  >  CSInvoice  = ' . wp_json_encode( $cs_invoice, JSON_UNESCAPED_UNICODE ) );

						break;
					} else {
						if ( $attempt < 3 ) {
							if ( !empty( $cs_invoice ) ) {
								// Keep a log of that was tried during the last attempt, as the CSInvoices keeps changing
								// during sync attempts, this makes it easier to see what was tried at every attempt.
								$this->logger->log( '  >  CSInvoice  = ' . wp_json_encode( $cs_invoice, JSON_UNESCAPED_UNICODE ) );
							}

							$this->logger->log( 'Trying next CS Invoice calculation strategy...' );
						}
						// Try the next strategy
						$attempt ++;
					}
				}

				if ( $success < 0 ) {
					// We could not find a satisfactory sync strategy. Let the user know and suggest.
					$this->logger->log( 'Could not find appropriate amounts to sync.' );
					throw new \Exception( 'Could not find appropriate amounts to sync.', TOTAL_AMOUNT_INVALID );
				}
			} else {
				if ( SYNC_OK === (int) $contasimple_invoice_sync->state ) {
					// The invoice appears as synced already, so return a 'SYNC OK' response.
					// Hopefully if an AJAX call is performed on more than 1 browser, the first one will
					// do the sync, and the next one will skip the sync and read the synced status
					// and will display	the synced OK message, akin as if the user reloaded the browser.
					// NOTE: Use of PHP semaphores would be ideal but not all hosts support it.
					$this->logger->log( 'This invoice has already been marked as synced, skipping sync.' );
					$response = $this->format_response_sync_ok( $contasimple_invoice_sync );
				} else if ( PAYMENT_SYNC_ERROR === (int) $contasimple_invoice_sync->state ) {
					if ( 0 < $contasimple_invoice_sync->externalID ) {
						$amount = $order->get_total() * $sign;
						$method = Contasimple_WC_Backward_Compatibility::get_order_payment_method( $order );

						$this->send_payment( $contasimple_invoice_sync, $period, $method, $amount );

						// Note: In this case we do not have a hint regarding if the previous state was sync OK with or
						// without warnings... for simplicity's sake, we will set SYNC_OK even if it is not exactly true.
						$contasimple_invoice_sync->update_sync_state( null, SYNC_OK, null );

						$response = $this->format_response_sync_ok( $contasimple_invoice_sync );
					}
				}
			}
		} catch ( \Exception $e ) {
			$this->logger->log( 'An error occurred trying to sync the ' . $type . ' at attempt #' . $contasimple_invoice_sync->attempts . '. Internal data is provided:' );
			$this->logger->log( '  >  wc_invoice = ' . wp_json_encode( $contasimple_invoice_sync, JSON_UNESCAPED_UNICODE ) );
			$this->logger->log( '  >  wc_order   = ' . wp_json_encode( $order_data, JSON_UNESCAPED_UNICODE ) );

			if ( ! empty( $cs_invoice ) ) {
				$this->logger->log( '  >  CSInvoice  = ' . wp_json_encode( $cs_invoice, JSON_UNESCAPED_UNICODE ) );
			}
			$this->logger->log( 'Exception thrown message: ' . $e->getMessage() );

			$contasimple_invoice_sync->number = '';

			// Only if the error is API-related, save the API message and show this to the end user instead of a generic
			// error message. Note that this is not translated at the Wordpress level and might be shown in a different
			// language depending on how things are set up.
			if ( (int) $e->getCode() === API_ERROR ) {
				$contasimple_invoice_sync->update_sync_state( null, $e->getCode(), $e->getMessage() );
			} else {
				$contasimple_invoice_sync->update_sync_state( null, $e->getCode(), null );
			}

			$this->logger->log( 'Internal status updated to ERROR (code ' . $e->getCode() . ' => ' . trim( strip_tags( Contasimple_CPT::get_message_html( $e->getCode(), $contasimple_invoice_sync->api_message ) ) ) . ')' );

			$response = $this->format_response_sync_error( $contasimple_invoice_sync, $e->getCode(), $contasimple_invoice_sync->api_message );
		}

		if ( ! isset( $response['error'] ) ) {
			$this->maybe_send_email_and_update_status( $order, $contasimple_invoice_sync, false );
		}

		return $response;
	}

	/**
	 * Formats a sync success response.
	 * Useful for AJAX sync() calls.
	 *
	 * @param $contasimple_invoice_sync A The CS invoice class.
	 * @return array An array of parameters that the javascript will parse to update the screen.
	 */
	public function format_response_sync_ok( $contasimple_invoice_sync ) {

		$cs_invoice_post = get_post( $contasimple_invoice_sync->ID );

		return array(
			'success' => true,
			'message'   => Contasimple_CPT::get_message_html( $contasimple_invoice_sync->state, $contasimple_invoice_sync->api_message ),
			'icon'      => Contasimple_CPT::get_state_html( $contasimple_invoice_sync->state ),
			'buttons'   => Contasimple_CPT::render_action_buttons( $cs_invoice_post, array( 'cs_view', 'cs_pdf', 'cs_email' ) ),
			'date_sync' => date( 'Y-m-d H:i:s' ),
			'invoice_number' => $contasimple_invoice_sync->number,
			'total_amount'  => $contasimple_invoice_sync->amount,
		);
	}

	/**
	 * Formats a sync error response.
	 * Useful for AJAX sync() calls.
	 *
	 * @param $contasimple_invoice_sync A The CS invoice class.
	 * @param $code The error code.
	 * @return array An array of parameters that the javascript will parse to update the screen.
	 */
	public function format_response_sync_error( $contasimple_invoice_sync, $code, $api_message = null ) {

		$cs_invoice_post = get_post( $contasimple_invoice_sync->ID );

		return array(
			'error'     => true,
			'message'   => Contasimple_CPT::get_message_html( $code, $api_message ),
			'icon'      => Contasimple_CPT::get_state_html( $code ),
			'buttons'   => Contasimple_CPT::render_action_buttons( $cs_invoice_post, array( 'cs_sync', 'cs_stop' ) ),
			'date_sync' => date( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Sends invoice email to customer if configured to automatically send (see WC Emails docs).
	 *
	 * @param    WC_Order           $order                    The order to email about.
	 * @param    CS_Invoice_Sync    $contasimple_invoice_sync The entity that holds the invoice sync info.
	 * @param    bool               $manual                   True if manual triggered sending process, false if auto.
	 *
	 * @since    1.0.0
	 */
	private function maybe_send_email_and_update_status( $order, $contasimple_invoice_sync, $manual = false ) {

		// We need to force the mailer init so that it can register the hook 'woocommerce_' for our custom invoice email, it does not load by default.
		WC()->mailer();

		$result = apply_filters( 'woocommerce_cs_invoice_generated_sync', $order, $contasimple_invoice_sync, $manual );

		if ( true === $result ) {
			$contasimple_invoice_sync->mail_status = EMAIL_SENT;
			$contasimple_invoice_sync->save();
		} elseif ( false === $result ) {
			$contasimple_invoice_sync->mail_status = EMAIL_FAILED;
			$contasimple_invoice_sync->save();
		} else {
			$contasimple_invoice_sync->mail_status = EMAIL_NOT_SENT;
			$contasimple_invoice_sync->save();
		}
	}

	/**
	 * Resume syncing queue
	 *
	 * TODO: See if we can use WP Background Processing to improve performance and scalability.
	 *
	 * @param $is_manual bool  By default true, used to check if we need to sync again after a certain amount of attempts.
	 *                         Set to false if you want to skip syncing failed invoices.
	 *
	 * @since    1.0.0
	 */
	public function resume_queue( $is_manual = true ) {

		try {
			$this->logger->log( 'Called: ' . __METHOD__ );
			$this->logger->log( 'Checking if there are pending invoices to sync...' );

			// If it has been 24h since the last attempt, trigger bulk sync > For now we are syncing always
			if ( true ) { //(!$this->get_config()->getLastAutoSync() || strtotime($this->get_config()->getLastAutoSync()) + (60 * 60 * HOURS_TO_SYNC_AGAIN) < time()) {
				// $this->logger->log( 'More than 24h passed sync last sync. Trying to sync invoices...' );
				$results = $this->send_pending_invoices( $is_manual );

				// Update last sync date on database so that we don't try again until the next HOURS_TO_SYNC_AGAIN defined const (ex: 24)
				$now = new DateTime();

				$config = $this->get_config();
				$config->setLastAutoSync( $now->format( 'Y-m-d H:i:s' ) );
				$this->set_config( $config );

				if ( $results['error'] > 0 ) {
					//$this->logger->logTransactionEnd( 'Errors were detected and the auto-sync process stopped. Auto-sync timer has been reset to look after 24h from now on.' );
					$this->logger->log( 'Sync process ended with errors.' );
				} else {
					if ($results['synced'] > 0) {
						$this->logger->log( 'Invoices synced OK.' );
					} else {
						$this->logger->log( 'No new invoices are pending to sync.' );
					}
				}
			} else {
				$this->logger->log( 'Not 24h passed since last automatic sync. Sync skipped.' );
			}
		} catch ( \Exception $e ) {
			$this->logger->log( 'Exception thrown while trying to autosync pending invoices: ' . $e->getMessage() );
		}
	}

	/**
	 * Resume sync queue
	 *
	 * This method does the same as the resume_queue() but is meant to be called when the user explicitly wants to
	 * trigger the action, not in response of any WP hook like order_completed or refund_created.
	 *
	 * @since 1.4.2
	 */
	public function resume_queue_manually() {

		$this->logger->logTransactionStart( 'Called: ' . __METHOD__, $this->processId );

		// Set lock.
		if ( $this->concurrency_control_enabled() ) {
			$newInvoiceMutex = new WebMutex(__FILE__ );

			if ( !$newInvoiceMutex->Lock( true, $this->max_lock_time ) )
			{
				$this->logger->log( "Error: Process $this->processId could not create a lock for manual invoice queue resume." );
				return;
			}

			$this->logger->log( "Locked mutex for manual invoice queue resume. Process $this->processId begins..." );
		}

		$this->resume_queue( true );

		// Release lock.
		if ( $this->concurrency_control_enabled() && is_object( $newInvoiceMutex ) ) {
			$newInvoiceMutex->Unlock();

			$this->logger->log( "Unlocked process $this->processId mutex for manual invoice queue resume." );
		}

		$this->logger->logTransactionEnd();

		// Redirect to cs_invoice post page in order to get rid of query params like sync=resume that trigger
		// accidentally the sync process when not intended, like a user pressing enter or F5 on the page.
		wp_redirect( esc_url( add_query_arg( 'post_type', 'cs_invoice', admin_url( 'edit.php' ) ) ) );
		exit();
	}

	/**
	 * Retrieve pending invoices and try to sync them one by one.
	 *
	 * @param $is_manual
	 *
	 * @return array
	 *
	 * @since    1.0.0
	 */
	public function send_pending_invoices( $is_manual ) {

		set_time_limit( 60 * 5 );

		$results = array(
			'total'        => 0,
			'synced'       => 0,
			'error'        => 0,
			'max_attempts' => 0,
		);

		$contasimple_invoices = CS_Invoice_Sync::get_pending_invoices();

		$results['total'] = count( $contasimple_invoices );

		$this->logger->log( sprintf( 'There are %1d pending invoices.', $results['total'] ) );

		// Before sending, make sure we are sending to the current CS company.
		$this->maybe_update_cs_company_settings();

		// Iterate orders and send one by one.
		foreach ( $contasimple_invoices as $contasimple_invoice ) {
			try {
				if ( $contasimple_invoice->attempts < MAX_SYNC_ATTEMPTS || true === $is_manual ) {
					$result = $this->create_or_update_invoice( $contasimple_invoice );

					if ( isset( $result['error'] ) ) {
						$results['error'] += 1;
						// Fist attempt and failed, send the email error. Skip for the following ones.
						if ($contasimple_invoice->attempts == 1) {
							$this->send_error_sync_mail( $contasimple_invoice, $result );
						}
					} else {
						$results['synced'] += 1;
					}
				} else {
					$results['max_attempts'] += 1;
				}
			} catch ( \Exception $e ) {
				$results['error'] += 1;
				$this->logger->log( 'Internal status updated to ERROR (code ' . $e->getCode() . ')' );
			}
		}

		return $results;
	}

	/**
	 * Sync invoice via AJAX
	 *
	 * This handles syncing at the individual level as an action button on the invoices admin table list.
	 */
	public function cs_sync() {

		$this->logger->logTransactionStart( 'Called: ' . __METHOD__, $this->processId );

		$cs_invoice_id = (int) $_REQUEST['cs_invoice_id']; // Input var okay.
		$nonce         = esc_attr( $_REQUEST['nonce'] ); // Input var okay.
		$action        = esc_attr( $_REQUEST['action'] ); //Input var okay.

		if ( ! $this->access_allowed( $nonce, $action ) ) {
			$this->logger->logTransactionEnd( 'Access not allowed. Timed out, invalid user requesting or configuration info is missing.' );
			$response = array(
				'redirect' => admin_url( 'edit.php?post_type=shop_order' ),
			);

			echo wp_json_encode( $response );
			exit();
		}

		if ( null !== $cs_invoice_id ) {

			// Set lock.
			if ( $this->concurrency_control_enabled() ) {
				$syncInvoiceMutex = new WebMutex(__FILE__ );

				if ( !$syncInvoiceMutex->Lock( true, $this->max_lock_time) )
				{
					$this->logger->log( "Error: Process $this->processId could not create a lock for sending invoice via AJAX request!" );
					exit();
				}

				$this->logger->log( "Locked mutex before sending invoice via AJAX request. Critical section of process $this->processId begins..." );
			}

			$contasimple_invoice_sync = new CS_Invoice_Sync($cs_invoice_id);

			try {
				$this->logger->log('Trying to manually sync invoice with ID [' . $cs_invoice_id . ']');

				$unique_series_prefix = substr(md5(microtime()),rand(0,26),4) . "_";

				$contasimple_cpt = new Contasimple_CPT();

				if ( SYNC_OK === (int) $contasimple_invoice_sync->state ) {

					$this->logger->log( 'This invoice has already been marked as synced, skipping sync.' );
					$response = $this->format_response_sync_ok( $contasimple_invoice_sync );

				} else {
					// Reset attempts on manual sync.
					$contasimple_invoice_sync->attempts = 0;

					// Before sending, make sure we are sending to the current CS company.
					$this->maybe_update_cs_company_settings();

					$response = $this->create_or_update_invoice( $contasimple_invoice_sync );
				}

				$response['summary'] = $contasimple_cpt->get_summary(null, true);

			} catch (\Exception $e) {
				$this->logger->log('Error updating the internal state of the item. Internal data is provided:');
				$this->logger->log('  >  Exception message = ' . $e->getMessage());

				$response = $this->format_response_sync_error( $contasimple_invoice_sync, GENERIC_ERROR );
			}

			// Release lock.
			if ( $this->concurrency_control_enabled() && is_object( $syncInvoiceMutex ) ) {
				$syncInvoiceMutex->Unlock();

				$this->logger->log( "Unlocked process $this->processId mutex after sending invoice via AJAX request." );
			}

		} else {
			$response = array(
				'error'   => true,
				'message' => __( 'Invalid WooCommerce invoice ID', 'contasimple' ),
			);
		}

		$this->logger->logTransactionEnd();

		echo wp_json_encode( $response );
		exit();
	}

	/**
	 * Stop syncing a certain order
	 *
	 * @throws Exception If something went wrong.
	 *
	 * @since    1.0.0
	 */
	public function cs_stop() {

		$this->logger->logTransactionStart( 'Called: ' . __METHOD__ );

		$cs_invoice_id = (int) $_REQUEST['cs_invoice_id']; // Input var okay.
		$nonce         = esc_attr( $_REQUEST['nonce'] ); // Input var okay.
		$action        = esc_attr( $_REQUEST['action'] ); //Input var okay.

		if ( ! $this->access_allowed( $nonce, $action ) ) {
			$this->logger->log( 'Access not allowed. Configuration info is missing.' );
			$response = array(
				'redirect' => admin_url( 'edit.php?post_type=shop_order' ),
			);
		}

		if ( null !== $cs_invoice_id ) {
			$contasimple_invoice_sync = new CS_Invoice_Sync( $cs_invoice_id );

			try {
				$this->logger->log( 'Stopping invoice sync with ID [' . $cs_invoice_id . ']' );

				if ( $contasimple_invoice_sync->state == PENDING ||
				     $contasimple_invoice_sync->state == NOT_SYNC ||
				     $contasimple_invoice_sync->state >= SYNC_ERROR ) {
					wp_delete_post( $cs_invoice_id, true );
					$this->logger->log( 'Successfully deleted from sync queue.' );
					$contasimple_cpt = new Contasimple_CPT();
					$response = array(
						'summary' => $contasimple_cpt->get_summary(null, true)
					);
				} else {
					throw new Exception();
				}
			} catch ( \Exception $e ) {
				$this->logger->log( 'Error updating the internal state of the item. Internal data is provided:' );
				$this->logger->log( '  >  Exception message = ' . $e->getMessage() );

				$response = $this->format_response_sync_error( $contasimple_invoice_sync, GENERIC_ERROR );
			}
		} else {
			$response = array(
				'error'   => true,
				'message' => __( 'Invalid WooCommerce invoice ID', 'contasimple' ),
			);
		}

		$this->logger->logTransactionEnd();
		echo wp_json_encode( $response );
		exit();
	}

	/**
	 * Download PDF invoice file from CS
	 *
	 * @since    1.0.0
	 */
	public function cs_pdf() {

		$this->logger->logTransactionStart( 'Called: ' . __METHOD__ );

		$nonce  = esc_attr( $_REQUEST['_wpnonce'] ); // Input var okay.
		$action = esc_attr( $_REQUEST['action'] ); // Input var okay.

		if ( ! $this->access_allowed( $nonce, $action ) ) {

			$this->logger->logTransactionEnd( 'Access not allowed. Configuration info is missing.' );
			$response = array(
				'redirect' => '',
			);

			echo wp_json_encode( $response );
			exit();

		} else {
			try {
				$id     = (int) $_REQUEST['externalID']; // Input var okay.
				$period = esc_attr( $_REQUEST['period'] ); // Input var okay.
				$number = esc_attr( $_REQUEST['number'] ); // Input var okay.

				$this->cs->getInvoicePDF( $id, $period, $number );

				exit();
			} catch ( \Exception $e ) {
				$this->logger->logTransactionEnd( 'Exception occurred during PDF download: ' + $e->getMessage() );

				$target_query_string = 'page=wc-settings&tab=integration&section=integration-contasimple';
				$target_url          = admin_url( 'admin.php?' . $target_query_string );

				wp_redirect( $target_url );

				exit();
			}
		}
	}

	/**
	 * Send mail to customer with attached PDF invoice from CS
	 *
	 * @since    1.0.0
	 */
	public function cs_email() {

		$this->logger->logTransactionStart( 'Called: ' . __METHOD__ );

		$nonce  = esc_attr( $_REQUEST['nonce'] ); // Input var okay.
		$action = esc_attr( $_REQUEST['action'] ); // Input var okay.

		if ( ! $this->access_allowed( $nonce, $action ) ) {

			$this->logger->log( 'Access not allowed.' );
			$response = array(
				'redirect' => admin_url( 'edit.php?post_type=cs_invoice' ),
			);

		} else {
			$cs_invoice_id = (int) $_REQUEST['cs_invoice_id'];

			$contasimple_invoice_sync = new CS_Invoice_Sync( $cs_invoice_id );

            try{
                $order = new WC_Order( $contasimple_invoice_sync->order_id );
            }
            catch(\Exception $ex){
                $order = new WC_Order();
            }

			if ( $cs_invoice_id > 0 & ! empty( $contasimple_invoice_sync ) && ! ( empty( $order ) ) ) {

				$this->maybe_send_email_and_update_status( $order, $contasimple_invoice_sync, true );

				$response = array(
					'success' => true,
					'buttons' => Contasimple_CPT::render_action_buttons( get_post( $cs_invoice_id ), array(
						'cs_view',
						'cs_pdf',
						'cs_email',
					) ),
				);

			} else {
				$response = array(
					'error' => true,
				);
			}
		}

		$this->logger->logTransactionEnd();

		echo wp_json_encode( $response );
		exit();
	}

	/**
	 * Send the invoice to CS via API
	 *
	 * @param   string $period
	 * @param   array $cs_invoice
	 * @param   int   $externalID
	 *
	 * @return  \Contasimple\Swagger\Client\Model\InvoiceApiModel
	 * @throws  Exception Containing an error code defined in Service.php.
	 *
	 * @since   1.0.0
	 */
	public function send_invoice( $period, $cs_invoice, $externalID = 0 ) {

		try {
			// If the invoice already exists (contains a valid externalID) some minor changes must be made in order to update it instead of creating it
			if ( $externalID && $externalID > 0 ) {
				$response = $this->cs->updateInvoice( $period, $cs_invoice, $externalID );
			} else {
				// If not exceptions thrown and we made it here, it's time to sync the invoice
				$response = $this->cs->createInvoice( $period, $cs_invoice );
			}

			return $response;

		} catch (Exception $e) {
			if ( API_ERROR === (int) $e->getCode() ) {
				// Expand the generic API error message to add more detail.
				throw new Exception(__( 'Invoice Synchronization failed.', 'contasimple' ) . '. ' . $e->getMessage(), API_ERROR, $e );
			} elseif ( GENERIC_ERROR === (int) $e->getCode() ) {
				// Display at least an error representative of the step of failure.
				throw new Exception('', SYNC_ERROR, $e );
			} else {
				// Errors like connection refused, unreachable host, etc.
				throw $e;
			}
		}
	}

	/**
	 * Verify that the company stored info in WC is still valid and update it otherwise.
	 *
	 * TODO See if we can use WP transients to do not perform this very time, most likely this info rarely changes.
	 */
	public function maybe_update_cs_company_settings() {

		$company = $this->cs->getCurrentCompany();

        if ( empty( $company ) ) {
            new Contasimple_Notice( __( 'An unknown error occurred trying to fetch your Contasimple account data, please contact us.', 'contasimple' ), 'error' );
            $this->force_login();

            throw new \Exception( "Could not load CS company data.", CANNOT_READ_COMPANY_DATA );
        }

		if ( $company->getId() != $this->get_config()->getCompanyId() ) {
			$this->cs->selectCompany( (int)$this->get_config()->getCompanyId() );
		} else {
			// Since 1.4.2 > Refresh WC config, maybe user changed its fiscal region, etc.
			$cs_config = $this->get_config();

			if ( $company->getFiscalRegion()->getCode() == FiscalRegionApiModel::CODE_OTRA ) {
				$countryName = $company->getExtraInformation()->getEntity()->getCountry();
				$allCountries = $this->cs->getCountries('true');
				foreach ( $allCountries as $country ) {
					if ($country->getName() == $countryName) {
						$countryIso = $country->getIsoCodeAlpha2();
						break;
					}
				}
			} else {
				$countryIso = $company->getCountry()->getIsoCodeAlpha2();
			}

			$cs_config->setCurrencySymbol( $company->getExtraInformation()->getCurrencySymbol() );
			$cs_config->setCountryISOCode( $countryIso );
			$cs_config->setFiscalRegionCode( $company->getFiscalRegion()->getCode() );
			$cs_config->setVatName( $company->getFiscalRegion()->getVatName() );
			$cs_config->setInvoiceCulture( $company->getExtraInformation()->getInvoiceCulture() );

			$this->set_config( $cs_config );
		}
	}

	/**
	 * Send payment to CS via API
	 *
	 * @param   ContasimpleInvoice $cs_invoice_sync
	 * @param   string             $period
	 * @param   string             $method
	 * @param   float              $amount
	 * @param   null|bool          $reset
	 *
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function send_payment( $cs_invoice_sync, $period, $method, $amount, $reset = null ) {

		try {
			$CSPayment = array(
				'date'            => $cs_invoice_sync->date_sync,
				'amount'          => $amount,
				'paymentMethodId' => $this->get_payment_id_equivalence( $method )
			);

			$this->cs->assignPayment( $cs_invoice_sync->externalID, $CSPayment, $period );
			$this->logger->log( sprintf( "Payment amount [%1$.2f] synced OK to contasimple invoice with ID [%2d]", $amount, $cs_invoice_sync->externalID ) );

		} catch ( \Exception $e ) {
			if ( PAYMENT_METHODS_RETRIEVAL_ERROR === $e->getCode() ) {
				// Fix for default payment method ID mechanic suddenly upgraded in CS release 1.88, the default payment
				// will no longer be id = 1 and shared across all companies, so we need to upgrade them on the fly.
				$newId = 0;
				$currentPaymentMethods = $this->cs->getPaymentMethods();
				foreach ( $currentPaymentMethods as $paymentMethod ) {
					if ( true === $paymentMethod->getIsDefault() ) {
						$newId = $paymentMethod->getId();
						break;
					}
				}
				if ( $newId > 0 ) {
					$config = $this->get_config();
					$oldPaymentMethods = $config->getPaymentEquivalences();

					if ( !empty( $oldPaymentMethods ) && is_array( $oldPaymentMethods ) ) {
						foreach ( $oldPaymentMethods as $key => &$value ) {
							if ( $value == $CSPayment['paymentMethodId'] ) {
								$this->logger->log( sprintf( "New default payment method ID in Contasimple '%1s' to replace failed ID '%2s'", $newId, $value ) );
								$value = $newId;
							}
						}
						$CSPayment['paymentMethodId'] = $newId;
						$config->setPaymentEquivalences( $oldPaymentMethods );
						$this->set_config( $config );

						try {
							$this->cs->assignPayment( $cs_invoice_sync->externalID, $CSPayment, $period );
						} catch ( \Exception $e ) {
							$this->logger->log( sprintf( "An error code (%1s) occurred trying to send payment amount [%2d] to Contasimple invoice with ID [%3d]", $e->getCode(), $amount, $cs_invoice_sync->externalID ) );
							throw new Exception($e->getMessage(), PAYMENT_SYNC_ERROR, $e );
						}
					}
				} else {
					throw new Exception($e->getMessage(), PAYMENT_SYNC_ERROR, $e );
				}
			} else {
				$this->logger->log( sprintf( "An error code (%1s) occurred trying to send payment amount [%2d] to Contasimple invoice with ID [%3d]", $e->getCode(), $amount, $cs_invoice_sync->externalID ) );

				// Here we cannot use the API_ERROR code because there's special handling of state PAYMENT_SYNC_ERROR later.
				throw new Exception($e->getMessage(), PAYMENT_SYNC_ERROR, $e );
			}
		}
	}

	/**
	 * Transforms an order to an array request object that the CS API createInvoice() method can digest.
	 *
	 * @param WC_Order        $order                     The WC Order with the data to convert to a Contasimple invoice.
	 * @param CS_Invoice_Sync $contasimple_invoice_sync  A CS_Invoice_Sync object that stores sync info of the invoice.
	 * @param string          $nif                       The NIF / Company identifier of the target of the invoice.
	 * @param string          $sync_date                 The date the invoice is being synced.
	 * @param int             $series_id                 The id of the numbering series in Contasimple.
	 * @param int             $sign                      Posible values: 1 or -1. Used to multiply amounts for refunds.
	 * @param int             $attempt                   The invoice sync strategy to use.
	 *
	 * @return array|mixed    An array that conforms to the InvoiceApiModel definition and can be passed down to the
	 *                        CS API createInvoice() method.
	 * @throws Exception
	 *
	 * @since   1.0.0
	 */
	public function convert_order_to_cs_invoice( $order, $contasimple_invoice_sync, $nif, $sync_date, $series_id, $sign, $attempt = 1 ) {

		try {
			if ( empty( $order ) || $order->get_order_number() == null ) {
				throw new \Exception( '', MISSING_ORDER );
			}

			$config = $this->get_config();

			// Get target entity data from CS (if customer does not exist, create it, or return aggregated if needed)
			$targetEntity = $this->convert_billing_address_to_cs_customer( $order, $nif );

			// Default mode for entity, used to create an invoice.
			$entityKey = 'target_entity_id';
			$entityVal = $targetEntity->getId();

			// There's a slight difference in the customer entity referencing mode
			// depending on whether it's a new invoice or an update...
			if ( $contasimple_invoice_sync->externalID > 0 && $contasimple_invoice_sync->state === CHANGED ) {
				$entityKey = 'target_entity';
				$entityVal = $targetEntity;
			}

			$order_id     = Contasimple_WC_Backward_Compatibility::get_id( $order );
			$order_status = Contasimple_WC_Backward_Compatibility::get_order_status( $order );
			$invoice_has_taxes = $order->get_total_tax() != 0;
			$invoice_rectified = null;

			// Check if this rectifies another synced invoice.
			if ( $sign < 0 ) {
				$order_invoices = CS_Invoice_Sync::get_invoices_from_order( $order_id );

				if ( is_array( $order_invoices ) && count( $order_invoices ) > 0) {
					$this->logger->log( "Invoice metadata found in WooCommerce to link to this rectifying invoice. Checking if already synced..." );
					/* @var CS_Invoice_Sync $order_invoice */
					foreach ( $order_invoices as $order_invoice ) {
						if ( $order_invoice->externalID > 0 && $order_invoice->amount > 0 ) {
							if ( $order_invoice->companyID != $config->getCompanyId() ) {
								$this->logger->log( sprintf("Warning: the company of the invoice trying to rectify [%s] is a different company than the current set [%s]. Looking for other candidates...", $order_invoice->companyID, $config->getCompanyId() ) );
							} else {
								$invoice_rectified = $order_invoice->externalID;
								break;
							}
						}
					}
				}

				// If woocommerce data has been deleted the linked invoice would not be found, try to look for it
				// in Contasimple as a last resort.
				if ( empty( $invoice_rectified ) ) {
					$this->logger->log( "No candidate invoices found in WooCommerce. Looking for order id into notes field via Contasimple API..." );

					// Search all the invoices in this year and with the #orderId text in the notes.
					$period = Contasimple_CPT::get_period_from_date( $sync_date, true );
					$expected_order_reference = '#' . $order_id;

					$order_invoices = $this->cs->getInvoices( $period, $expected_order_reference );

					if ( is_array( $order_invoices ) && count( $order_invoices ) > 0) {
						/* @var \Contasimple\Swagger\Client\Model\InvoiceApiModel $order_invoice */
						foreach ( $order_invoices as $order_invoice ) {
							$private_note = $order_invoice->getNotes();

							if ( strpos( $private_note, $expected_order_reference ) !== false
							     && $order_invoice->getTotalAmount() > 0 )
							{
								$invoice_rectified = $order_invoice->getId();
								break;
							}
						}
					}

					if ( empty( $invoice_rectified ) ) {
						$this->logger->log( sprintf("Neither candidate invoices found via Contasimple API for period [%s] in company id [%s] with notes containing [%s]", $period, $config->getCompanyId(), $expected_order_reference ) );
					}
				}
			}

			// Start building the CS invoice entity.
			// The basic info
			$cs_invoice = array(
				$entityKey            => $entityVal,
				'number'              => $contasimple_invoice_sync->number, // Already handled by the API
				'date'                => $sync_date,
				'numbering_format_id' => $series_id,
				'invoice_class'       => 700,
				'notes'               => $this->get_invoice_notes( $order_id, $sign, $nif ),
				'is_rectification_invoice' => empty( $invoice_rectified ) ? false : true,
				'rectifies_invoice_id' => $invoice_rectified
			);

			// Set default values just in case some of the if/else does not set the value and the validate amounts detect
			// an undefined index. Needs improvement > refactor needed to minimize spaghetti code.
			$cs_invoice['retentionAmount'] = 0;
			$cs_invoice['retention_percentage'] = 0;

			// Gather info from both issuer (from CS company info) and receiver (from WC customer address).
			$issuerCountryISOCode   = $config->getCountryISOCode();
			$issuerFiscalRegionCode = $config->getFiscalRegionCode();
			$customerCountry = Contasimple_WC_Backward_Compatibility::get_billing_country( $order );
			$customerState   = Contasimple_WC_Backward_Compatibility::get_billing_state( $order );

			// CS 'Other' countries returns a null value for ISO code.
			// In that case, use the WC base shop country.
			if ( empty( $issuerCountryISOCode ) )
				$issuerCountryISOCode = wc_get_base_location()['country'];

			// Let's decide the invoice type based on availability of invoicing data and issuer/target fiscal regions.
			$cs_invoice['operation_type'] = InvoiceHelper::getOperationType(
				$nif,
				null,
				$customerCountry,
				$customerState,
				$issuerCountryISOCode,
				$issuerFiscalRegionCode,
				$invoice_has_taxes
			);

			$warning = InvoiceHelper::getVatWarnings(
				$nif,
				null,
				$customerCountry,
				$customerState,
				$issuerCountryISOCode,
				$issuerFiscalRegionCode,
				$invoice_has_taxes
			);

			if ( $warning )
				$cs_invoice['warning'] = $warning;

			// We will keep two instances of the invoice (rounded and not rounded) if the rounded invoice won't sync
			// we might try the 2nd group by VAT strategy with the original un-rounded amounts.
			$cs_invoice_without_rounding = $cs_invoice;

			// Get candidates for cs invoice lines
			// Get discounts applied to all items item (only if first attempt and WC >= 3.2)
			if ( $this->can_compute_discounts_separately( $attempt ) ) {
			    $line_items = $order->get_items( array('line_item', 'shipping', 'fee', 'coupon') );
			    $discounts = Contasimple_WC_Backward_Compatibility::get_order_discounts( $order );
			} else {
			    $line_items = $order->get_items( array('line_item', 'shipping', 'fee') );
			    $discounts = array();
			}

			// Regular orders and full refunds can be iterated in the same fashion, only the amount sign
			// will change (negative) for unitAmount, totalTaxableAmount and vatAmount.
			if ( ! $this->is_partial_refund( $order, $contasimple_invoice_sync->args ) ) {

				// Iterating and extracting data from order products, shipping costs, etc.
				foreach ( $line_items as $item_id => $item ) {

				    $line_sign = $sign;
                    $discount_percentage = 0;
					$type = Contasimple_WC_Backward_Compatibility::get_order_item_type( $item );

					switch ( $type ) {
						// It's a shop product
						case 'line_item':

                            $concept = Contasimple_WC_Backward_Compatibility::get_order_item_name( $item );
                            $quantity = intval( $item['qty'] );

							if ( ! empty( get_option( 'woocommerce_integration-contasimple_settings' ) )
							    && array_key_exists( 'enable_sku', get_option( 'woocommerce_integration-contasimple_settings' ) )
								&& 'yes' === get_option( 'woocommerce_integration-contasimple_settings' )['enable_sku'] ) {

								$product  = Contasimple_WC_Backward_Compatibility::get_product_from_order_item( $item );
								$sku = Contasimple_WC_Backward_Compatibility::get_product_sku( $product );

								if ( ! empty ( $sku ) ) {
									$concept .= " (SKU: " . $sku . ")";
								}
							}

                            if ( $this->can_compute_discounts_separately( $attempt ) ) {
                                $discount_percentage = Contasimple_WC_Backward_Compatibility::calculate_item_discount_percentage( $discounts, $item->get_id() );

                                if ( $discount_percentage > 0 ) {
                                    // This item is affected by discounts.
                                    $concept .= " (" . __( 'Discount coupon', 'contasimple' ) . ": " . Contasimple_WC_Backward_Compatibility::get_order_item_name( $item ) . ") ";
                                    $unit_amount =  $item['line_subtotal'] / $item['qty'];
                                    $total_taxable_amount = round( $item['line_subtotal'] * (1 - $discount_percentage / 100), CS_MAX_DECIMALS, PHP_ROUND_HALF_UP );
                                } else {
                                    // This item is not affected by discounts, treat as before accounting for discounts.
                                    $unit_amount = $item['line_subtotal'] / $item['qty'];
                                    $total_taxable_amount = $item['line_subtotal'];
                                }
                            } else {
                                // Go the traditional route with discounts already accounted for in the line.
                                $unit_amount = $item['line_total'] / $item['qty'];
                                $total_taxable_amount = $item['line_total'];
                            }


							break;

						// It's a shipping carrier fee
						case 'shipping':

							// Carrier shipping name
							$concept  = __( 'Shipping', 'contasimple' ) . ': ' . Contasimple_WC_Backward_Compatibility::get_order_item_name( $item );
							$unit_amount = Contasimple_WC_Backward_Compatibility::get_order_shipping_item_amount( $item );
							$total_taxable_amount = $unit_amount; // Only 1 item of shipping always!
							$quantity = 1;

							break;

						case 'fee':

							$concept = Contasimple_WC_Backward_Compatibility::get_order_item_name( $item );
							$unit_amount = $item['line_total'];
							$total_taxable_amount = $unit_amount;
							$quantity = 1;

							break;

                        case 'coupon':
                            // If we are in attempt 2 or 3, skip the discounts and go for totals already discounted.
                            if ( !$this->can_compute_discounts_separately( $attempt ) ) {
                                continue 2;
                            }

                            $coupon_code = Contasimple_WC_Backward_Compatibility::get_order_item_name( $item );
                            $concept = __( 'Discount coupon', 'contasimple' ) . ': ' . $coupon_code;
                            $coupon = new WC_Coupon( $coupon_code );

                            // If it is a percentage coupon, skip it, as we already handled it in the line_item case.
                            if ( $coupon->get_discount_type() == 'percent') {
                                continue 2;
                            }

                            $unit_amount = $item->get_discount();
                            $total_taxable_amount = $unit_amount;
                            $quantity = 1;
                            $line_sign = $sign * -1;

                            break;
					}

					$this->add_line_to_cs_invoice( $cs_invoice, $cs_invoice_without_rounding,
						$concept,
						$unit_amount,
						$quantity,
						$total_taxable_amount,
						$discount_percentage,
						$item,
                        $line_sign,
						$order,
                        $attempt
					);
				}

			} else {
				// If it's a refund and the total paid is different than the amount that we get as an argument,
				// it means this is a partial refund, we have to check other entities, account for remaining amounts,
				// etc.
				$this->convert_partial_refund_to_cs_lines( $cs_invoice, $cs_invoice_without_rounding, $order, $contasimple_invoice_sync->args );
			}

			if ( $attempt >= 2 ) {
				$cs_invoice = $this->get_invoice_lines_grouped_by_VAT( $cs_invoice_without_rounding, $order, $sign );
			}

			if ( $attempt == 3 ) {
				if ( count( $cs_invoice['lines'] ) > 1 ) {
					throw new \Exception( '', CANNOT_FIND_SYNC_STRATEGY );
				} else {
					$cs_invoice = $this->get_invoice_one_liner_emergency_sync( $cs_invoice, $order, $sign );
				}
			}

			return $cs_invoice;

		} catch ( \Exception $e ) {
			$this->logger->log( "Exception thrown while gathering PS invoice info to send via API: " . $e->getMessage() );
			throw $e;
		}
	}

	/**
	 * Transforms the Order billing address to a Contasimple customer (targetEntity)
	 *
	 * This is part of the whole Contasimple invoice that we need to send via API.
	 *
	 * @param $order
	 * @param $nif
	 *
	 * @return \Contasimple\Swagger\Client\Model\EntityApiModel|null|
	 * @throws Exception
	 */
	public function convert_billing_address_to_cs_customer( $order, $nif ) {

		if ( empty( $nif ) ) {
			// The invoice does not have a valid NIF, we need to get the aggregated customer for tickets.
			try {
				$targetEntity = $this->cs->getCustomerAggregated();
			}  catch ( \Exception $e ) {
				if ( API_ERROR === (int) $e->getCode() || GENERIC_ERROR === (int) $e->getCode() ) {
					// Display an error representative of the step of failure.
					throw new Exception('', AGGREGATED_CUSTOMER_CANNOT_CREATE, $e );
				} else {
					// Errors like connection refused, unreachable host, etc.
					throw $e;
				}
			}
		} else {
			$customer_country = Contasimple_WC_Backward_Compatibility::get_billing_country( $order );
			$order_id         = Contasimple_WC_Backward_Compatibility::get_id( $order );
			$country_id       = $this->get_cs_country_id_from_wc_iso( $customer_country );

			if ( ! $this->is_valid_nif( $nif, $customer_country ) ) {
				// If NIF is invalid, do not block the invoice generation, let's send it to the ticket pile (aggregated customer).
				try {
					$targetEntity = $this->cs->getCustomerAggregated();
					$nif = $targetEntity->getNif();
				}  catch ( \Exception $e ) {
					if ( API_ERROR === (int) $e->getCode() || GENERIC_ERROR === (int) $e->getCode() ) {
						// Display an error representative of the step of failure.
						throw new Exception('', AGGREGATED_CUSTOMER_CANNOT_CREATE, $e );
					} else {
						// Errors like connection refused, unreachable host, etc.
						throw $e;
					}
				}
			} else {
				// The customer has a valid NIF, it may exist already in CS or not
				try {
					$targetEntity = $this->cs->getCustomerByNIF( $nif );
				} catch (Exception $e) {
					// Expand the generic API error message to add more detail.
					if ( API_ERROR === $e->getCode() ) {
						throw new Exception(__( 'The customer could not be retrieved', 'contasimple' ) . '. ' . $e->getMessage(), API_ERROR, $e );
					} elseif ( GENERIC_ERROR === (int) $e->getCode() ) {
						// Display at least an error representative of the step of failure.
						throw new Exception('', GET_CUSTOMER_ERROR, $e );
					} else {
						// Errors like connection refused, unreachable host, etc.
						throw $e;
					}
				}
			}

			if ( empty( $targetEntity ) ) {
				$wc_states = WC()->countries->get_states( $customer_country );

				// If it is the first invoice generated for this user, we must create it in CS before sending the invoice, as every
				// CS invoice has its customer embedded as a 'target entity'
				if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
					$billing_company = $order->billing_company;
					$billing_first_name = $order->billing_first_name;
					$billing_last_name = $order->billing_last_name;
					$billing_address_1 = $order->billing_address_1;
					$billing_address_2 = $order->billing_address_2;
					$billing_city = $order->billing_city;
					$billing_postcode = $order->billing_postcode;
					$billing_phone = $order->billing_phone;
					$billing_email = $order->billing_email;
					$billing_state = $order->billing_state;
				} else {
					$billing_company = $order->get_billing_company();
					$billing_first_name = $order->get_billing_first_name();
					$billing_last_name = $order->get_billing_last_name();
					$billing_address_1 = $order->get_billing_address_1();
					$billing_address_2 = $order->get_billing_address_2();
					$billing_city = $order->get_billing_city();
					$billing_postcode = $order->get_billing_postcode();
					$billing_phone = $order->get_billing_phone();
					$billing_email = $order->get_billing_email();
					$billing_state = $order->get_billing_state();
				}

				// Avoid undefined index warnings just in case the country is not present (should not happen).
				if ( array_key_exists( $customer_country, WC()->countries->countries ) ) {
					$wc_country = WC()->countries->countries[ $customer_country ];
				} else {
					$wc_country = $customer_country;
				}

				// Avoid undefined index warnings just in case the billing state cannot be returned (should not happen).
				if ( empty ( $billing_state ) || ! is_array( $wc_states ) || ! array_key_exists( $billing_state, $wc_states ) ) {
					$wc_billing_state = "";
				} else {
					$wc_billing_state =  html_entity_decode($wc_states[$billing_state]);
				}

				$cs_customer = array(
					"type"         => "Target",
					"organization" => ! empty( $billing_company ) ? $billing_company : $billing_first_name . " " . $billing_last_name,
					"nif"          => $nif,
					"address"      => $billing_address_1 . " " . $billing_address_2,
					"province"     => $wc_billing_state,
					"city"         => $billing_city,
					"country"      => $wc_country,
					"countryID"    => $country_id,
					"postalCode"   => $billing_postcode,
					"phone"        => $billing_phone,
					"fax"          => "",
					"email"        => $billing_email,
					"notes"        => __( 'Synchronized from WooCommerce order #' . $order_id, 'contasimple' ),
					"url"          => "",
					"customField1" => "",
					"customField2" => ""
				);

				try {
					$targetEntity = $this->cs->createCustomer( $cs_customer );
				} catch ( \Exception $e ) {
					// Expand the generic API error message to add more detail.
					if ( API_ERROR === $e->getCode() ) {
						throw new Exception(__( 'The customer could not be created', 'contasimple' ) . '. ' . $e->getMessage(), API_ERROR, $e );
					} elseif ( GENERIC_ERROR === (int) $e->getCode() ) {
						// Display at least an error representative of the step of failure.
						throw new Exception('', CUSTOMER_CANNOT_CREATE, $e );
					} else {
						// Errors like connection refused, unreachable host, etc.
						throw $e;
					}
				}
			}
		}

		return $targetEntity;
	}

	/**
	 * Handles the particular case of a partial refund.
	 *
	 * @param $cs_invoice The CS invoice entity as we need to inject the changes calculated in this method (by reference).
	 * @param $args The data regarding the WC refund process.
	 *
	 * @return int|number The accumulated decimal error, needed on the outside to decide if we have to switch to the
	 * group by VAT rates calculation strategy or other emergency sync strategies.
	 */
	public function convert_partial_refund_to_cs_lines( &$cs_invoice, &$cs_invoice_without_rounding, $order, $args ) {

		if ( is_serialized( $args ) ) {
			$args = unserialize( $args );
		}

		$amount_from_items = 0;

		if ( ! empty( $args['reason'] ) ) {
			$cs_invoice['notes'] .= CS_EOL . __( 'Refund reason: ', 'contasimple' ) . $args['reason'];
		}

		foreach ( $args['line_items'] as $key => $item ) {
			if ( empty( $item['refund_total'] ) ) {
				continue;
			}

			$order_item = Contasimple_WC_Backward_Compatibility::get_order_item( $order, $key );

			$quantity          = empty( $item['qty'] ) ? 1 : intval( $item['qty'] );
			$product_unit_cost = $item['refund_total'] / $quantity;
			$product_line_cost = $item['refund_total'];

			$product_name      = Contasimple_WC_Backward_Compatibility::get_order_item_name( $order_item );
			if ( empty ( $product_name ) ) {
				$product_name = __( 'Other', 'contasimple' );
			}

			$vatAmount         = isset( $item['refund_tax'] ) ? floatval( array_values( $item['refund_tax'] )[0] ) : 0;
			$vatPercentage     = floatval( ( $vatAmount * 100 ) / ( $item['refund_total'] ) );

			// Problem: if low precision operation gives spanish vat rate of 21.01, the order won't sync.
			// This is not ideal, but for the time being just assume that anything very similar to 21 is 21.00 %
			// Do only for spain as this is so common that we need to get going by.
			// TODO Improve this with maybe matching CS region VAT and calculated VAT rate.
			if ( abs ( $vatPercentage - 21 ) <= 0.1 ) {
				$vatPercentage = 21;
			}

			if ( ! empty( get_option( 'woocommerce_integration-contasimple_settings' ) )
			    && array_key_exists( 'enable_sku', get_option( 'woocommerce_integration-contasimple_settings' ) )
				&& 'yes' === get_option( 'woocommerce_integration-contasimple_settings' )['enable_sku'] ) {

				$product  = Contasimple_WC_Backward_Compatibility::get_product_from_order_item( $order_item );
				$sku = Contasimple_WC_Backward_Compatibility::get_product_sku( $product );

				if ( ! empty ( $sku ) ) {
					$product_name .= " (SKU: " . $sku . ")";
				}
			}

			$line = array(
				'concept'            => $product_name,
				'unitAmount'         => $product_unit_cost,
				'quantity'           => $quantity * -1,
				'vatPercentage'      => $vatPercentage,
				'vatAmount'          => $vatAmount * -1,
				'totalTaxableAmount' => $product_line_cost * -1,
				'discountPercentage' => 0,
				'reAmount'           => 0,
				'rePercentage'       => 0
			);

			$line_rounded = $this->round_cs_line_item( $line );

			// Accumulate refunds from items so we can later see if there is some amount without a line associated
			$amount_from_items += $product_line_cost + $vatAmount;

			$cs_invoice['lines'][]                  = $line_rounded;
			$cs_invoice_without_rounding['lines'][] = $line;
		}

		// If there's an orphaned amount, we have to add it as a new line item so that the exact invoice/refund amounts match.
		// Also contemplate adding at least one line with a total 0 amount value if nothing is found
		// so that the CS invoices does not end with no lines at all.
		if ( $amount_from_items < floatval( $args['amount'] ) || ( empty( $cs_invoice['lines'] ) && 0 == floatval( $args['amount'] ) ) ) {
			$line = array(
				'concept'            => __( 'Remaining amount refunded', 'contasimple' ),
				'unitAmount'         => ( floatval( $args['amount'] ) - $amount_from_items ),
				'quantity'           => -1,
				'vatPercentage'      => 0, // TODO is this really ok? :/
				'vatAmount'          => 0 * -1,
				'totalTaxableAmount' => ( floatval( $args['amount'] ) - $amount_from_items ) * -1,
				'discountPercentage' => 0,
				'reAmount'           => 0,
				'rePercentage'       => 0
			);

			$line_rounded = $this->round_cs_line_item( $line );

			$cs_invoice['lines'][]                  = $line_rounded;
			$cs_invoice_without_rounding['lines'][] = $line;
		}
	}

	public function convert_shipping_to_cs_invoice_line( &$cs_invoice, $order, $sign ) {

		$shipping_items = $order->get_items( 'shipping' );

		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
			global $wpdb;

			if ( count( $shipping_items ) > 1 ) {
				// WC before v2.2 offers poor support to get tax rates bases on shipping methods as items,
				// so given that almost everybody has dropped support already, we will find a compromise by assuming
				// that only 1 shipping method is used for each order and we will get the total rate + amount used as
				// one CS invoice line.
				// If more than 1 method is detected, throw an error. It should not be a typical scenario.
				throw new \Exception( '', TAXES_PER_LINE_TOO_COMPLEX );
			}

			$shipping = array_shift( $shipping_items );
			$taxes = $order->get_taxes();

			$shipping_taxes_found = 0;
			$shipping_tax = 0;
			$vatPercentage = 0;
			$shipping_total = $order->get_total_shipping();

			foreach ( $taxes as $tax ) {
				if ( isset( $tax['shipping_tax_amount'] ) && $tax['shipping_tax_amount'] > 0 ) {
					$shipping_taxes_found++;
					$shipping_tax = $tax['shipping_tax_amount'];
					$vatPercentage = $wpdb->get_var( $wpdb->prepare( "SELECT tax_rate FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %s", $tax['rate_id'] ) );
				}
			}

			// TODO Maybe account for IRPF and R.E in <2.2 since now we know how (see other examples)
			if ( $shipping_tax != $order->get_shipping_tax() && 1 < $shipping_taxes_found ) {
				throw new \Exception( '', TAXES_PER_LINE_TOO_COMPLEX );
			}

			$line = array(
				'concept'            => __( 'Shipping', 'contasimple' ) . ': ' . $shipping['name'],
				'unitAmount'         => $shipping_total * $sign,
				'quantity'           => 1,
				'vatAmount'          => $shipping_tax * $sign,
				'vatPercentage'      => $vatPercentage,
				'totalTaxableAmount' => $shipping_total * $sign,
			);

			$cs_invoice['lines'][] = $line;

		} else {
			// WC 2.2+ offers methods to get shipping concepts more similar to product item lines, it's easier
			// to get the tax rate for each shipping method. Thus, we will support more than one shipping method
			// per order just in case this has some real case use.
			foreach ( $shipping_items as $item ) {
				$shipping_name  = __( 'Shipping', 'contasimple' ) . ': ' . $item->get_name();
				// There's still some differences between up to 2.6 and 3.0+ but not difficult to deal with.
				if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
					$shipping_total = $item['cost'];
					$shipping_tax   = $item['taxes'];
					if( is_serialized( $shipping_tax )) {
						$shipping_tax = maybe_unserialize($shipping_tax);
						$shipping_tax = count($shipping_tax) > 0 ? $shipping_tax[1] : 0;
					}
				} else {
					$shipping_total = $item->get_total();
					$shipping_tax   = $item->get_total_tax();
				}

				// Let's get the VAT for the shipping and handle weird IRPF or R.E. scenarios
				$additionalTaxes = $this->calculate_cs_line_taxes( $item );

				$vatPercentage = $additionalTaxes['vatPercentage'];
				$vatAmount = $additionalTaxes['vatAmount'];
				$rePercentage = $additionalTaxes['rePercentage'];
				$reAmount = $additionalTaxes['reAmount'];

				$line = array(
					'concept'            => $shipping_name,
					'unitAmount'         => $shipping_total * $sign,
					'quantity'           => 1,
					'vatAmount'          => $vatAmount * $sign, //$shipping_tax * $sign,
					'vatPercentage'      => $vatPercentage,
					'totalTaxableAmount' => $shipping_total * $sign,
					'reAmount'           => $reAmount * $sign,
					'rePercentage'       => $rePercentage
				);

				$cs_invoice['lines'][] = $line;
			}
		}
	}

	/**
	 * Rounds the relevant values of a Contasimple invoice line.
	 *
	 * CS forces a line to to be sent with a maximum of CS_MAX_DECIMALS defined decimal numbers (2d as to date).
	 * Away-from-zero rounding is applied.
	 *
	 * @param array $line The array containing the original line items, could contain up to 5 digits in decimal values.
	 *
	 * @return array An array containing the resulting line items, with their values rounded to CS_MAX_DECIMALS defined
	 *               decimal values.
	 */
	public function round_cs_line_item( $line ) {

		$line_rounded = array(
			'concept'             => $line['concept'],
			'unitAmount'          => round( $line['unitAmount'], CS_MAX_DECIMALS, PHP_ROUND_HALF_UP ),
			'quantity'            => $line['quantity'],
			'discountPercentage'  => round( $line['discountPercentage'], CS_MAX_DECIMALS, PHP_ROUND_HALF_UP ),
			'vatPercentage'       => round( $line['vatPercentage'], CS_MAX_DECIMALS, PHP_ROUND_HALF_UP ),
			'vatAmount'           => round( $line['vatAmount'], CS_MAX_DECIMALS, PHP_ROUND_HALF_UP ),
			'totalTaxableAmount'  => round( $line['totalTaxableAmount'], CS_MAX_DECIMALS, PHP_ROUND_HALF_UP ),
			'rePercentage'        => round( $line['rePercentage'], CS_MAX_DECIMALS, PHP_ROUND_HALF_UP ),
			'reAmount'            => round( $line['reAmount'], CS_MAX_DECIMALS, PHP_ROUND_HALF_UP )
		);

		if ( isset( $line['detailedDescription'] ) ) {
			$line_rounded['detailedDescription'] = $line['detailedDescription'];
		}

		return $line_rounded;
	}

	/**
	 * Checks if the order refunded amounts and original amount differ.
	 * We need to know this as the process to build our contasimple invoice will be slightly different then if
	 * the refunded amount is the same (full refund).
	 *
	 * @param WC_Order         $order  The WC order instance.
	 * @param array|mixed|null $args   The arguments that come from the order refunded WC hook and contain the amount refunded.
	 *
	 * @return bool True if it is a partial refund, false otherwise.
	 */
	public function is_partial_refund( $order, $args ) {

		if ( is_serialized( $args ) ) {
			$args = unserialize( $args );
		}

		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
			return ! empty( $args ) && $order->get_total() != $args['amount'];
		} else {
			return ! empty( $args ) && $order->get_total() != $args['amount'];
		}
	}

	/**
	 * Reduce invoice lines to lines grouped by VAT percentage
	 *
	 * This reduces the rounding error, invoke when we detect a threshold error that is not tolerable.
	 *
	 * @param   $cs_invoice
	 * @param   $order
	 *
	 * @return  mixed
	 * @throws  Exception
	 *
	 * @since   1.0.0
	 */
	public function get_invoice_lines_grouped_by_VAT( $cs_invoice, $order, $sign ) {

		try {
			$currency_symbol = $this->get_config()->getCurrencySymbol(); // Taken from CS -> '€'
			$currency = Contasimple_WC_Backward_Compatibility::get_currency( $order ); // taken from WC order since CS does not have an ISO 3 digits equivalent anymore -> 'EUR'
			$vatName = $this->get_config()->getVatName(); // Ex: 'I.V.A'.

			$line_num  = 1;
			$new_lines = array();

			$cs_invoice['notes'] = __( 'The concepts of the invoice do not reflect the individual products of the order because the taxable amount charged for one or more products (the unit cost excluding taxes and having applied any discount) is not representable with two decimals, which is why the order had to be synchronized by grouping the totals by type of tax. If you want your eCommerce order invoices to be synchronized by keeping the original product lines, the price assigned to the products from your shop must be an amount that, after deducting taxes and discounts, can be represented exactly with two decimal digits and without the need to round off the amount.', 'contasimple' )
				. ' ' . __( 'For more information, please check the FAQ in the \'How to configure the Contasimple plugin for WooCommerce\' tutorial in the \'Help and tutorials\' section. ', 'contasimple');

			// Condense N lines as concepts into M lines grouped by VAT type
			foreach ( $cs_invoice['lines'] as $line ) {

				// An empty line might come from free shipping and mess with the flattening, so skip it.
				if ( $line['vatPercentage'] == 0 && $line['vatAmount'] == 0 && $line['unitAmount'] == 0) {
					continue;
				}

				if ($line['rePercentage'] > 0) {
					$key = $line['vatPercentage'] + $line['rePercentage'];
					$vatRates = $line['vatPercentage'] . ' & ' . $line['rePercentage'];
					$vatNames = $vatName . ' & ' . 'R.E';
				} else {
					$key = $line['vatPercentage'];
					$vatRates = $line['vatPercentage'];
					$vatNames = $vatName;
				}

				$new_lines[ $key ] = array(
					'concept'             => sprintf( __( 'Sum of the products with %1$d%% %2$s', 'contasimple' ), $vatRates, $vatNames ),
					'unitAmount'          => ( isset( $new_lines[ $key ]['unitAmount'] ) ? $new_lines[ $key ]['unitAmount'] : 0 ) + $line['unitAmount'] * abs( $line['quantity'] ),
					'quantity'            => 1 * $sign,
					'vatPercentage'       => $line['vatPercentage'],
					'vatAmount'           => ( isset( $new_lines[ $key ]['vatAmount'] ) ? $new_lines[ $key ]['vatAmount'] : 0 ) + $line['vatAmount'],
					'totalTaxableAmount'  => ( isset( $new_lines[ $key ]['totalTaxableAmount'] ) ? $new_lines[ $key ]['totalTaxableAmount'] : 0 ) + $line['totalTaxableAmount'],
					'rePercentage'        => $line['rePercentage'],
					'reAmount'            => ( isset( $new_lines[ $key ]['reAmount'] ) ? $new_lines[ $key ]['reAmount'] : 0 ) + $line['reAmount'],
					'discountPercentage'  => 0,
					'detailedDescription' => $this->get_detailed_description_line( $new_lines, $key, $line, $currency, $currency_symbol, $vatName, $this->get_config()->getInvoiceCulture() )
				);
			}

			// Once grouped, now do the final rounding at the very last moment.
			foreach ( $new_lines as &$new_line ) {
				$new_line = $this->round_cs_line_item( $new_line );

				// Close the <ul> list item.
				if ( ! empty( $new_line['detailedDescription'] ) ) {
					$new_line['detailedDescription'] = $new_line['detailedDescription'] . '</ul>';
				}
			}

			// Reset keys or otherwise the API would fail
			$cs_invoice['lines'] = array_values( $new_lines );

			return $cs_invoice;
		} catch ( \Exception $e ) {
			$this->logger->log( "Exception thrown while generating a simplified invoice to avoid rounding issues: " . $e->getMessage() );
			throw $e;
		}
	}

	/**
	 * Generates an HTML <li> description element for a given product (as a line item).
	 *
	 * To use when we need to group the invoice by VAT and qty and we want to attach info about the original order
	 * in the CS invoice line advanced comments field.
	 *
	 * @since 1.8.0
	 *
	 * @param $new_lines array The array with all the lines. We need it to check if it is the first item and thus we need to open the <ul> element.
	 * @param $key int The index indicating the actual line that we want to generate the comment for.
	 * @param $line array The line per se, has all the info about prices, taxes, etc.
	 * @param $currency string A currency in the ISO-3 digits style. Examples: 'EUR' or 'USD'.
	 * @param $vatName string The name of the tax as defined per CS fiscal region. Ex: 'I.V.A'.
	 * @param $companyCulture string A locale that comes from the CS company extra information field 'invoiceCulture'. Example: 'es-EN' (spanish language, english formatting).
	 *
	 * @return string A string that contains the HTML of an item description and original price amounts. Example:
	 */
	private function get_detailed_description_line($new_lines, $key, $line, $currency, $currency_symbol, $vatName, $companyCulture ) {

		// CS uses C# .toString('c', invoiceCulture) which works slightly different than PHP NumberFormatter class
		// regarding the format of negative currency numbers due to differences in intl ICU library.
		// We will have to deal with this.
		// Define a mapping (this might need to be tweaked if ever changes in CS).
		$cs_cultures_with_accounting_style = array(
			"es-CR", "es-PA", "es-EC", "es-PY",	"es-BO", "es-SV", "es-NI", "es-US",
			"en-US", "en-BZ", "en-TT", "en-ZW",	"en-PH", "en-MY", "en-HK", "en-SG",
			"fr-CA"
		);

		// Use PHP NumberFormatter class wherever possible (requires PECL intl extension enabled)
		if ( class_exists( 'NumberFormatter' ) ) {

			// Note that in PHP 7 + ICU 53+ there's a new NumberFormatter::CURRENCY_ACCOUNTING option.
			// However CS differs quite a bit so we will do our custom stuff anyways.
			$formatter = new \NumberFormatter( $companyCulture, \NumberFormatter::CURRENCY);

			// If CS does accounting format for this culture, reflect it here.
			if ( in_array( $companyCulture, $cs_cultures_with_accounting_style) ) {

				$p = $formatter->getPattern();
				$p = explode(";", $p, 2);

				// All analyzed cases in CS keep the same format as the positive amount but surrounded with parentheses,
				// so will just add a 2nd mask that is a clone of the first but with the ( ).
				// Beware this might change in the future.
				$formatter->setPattern($p[0] . ";(" . $p[0] . ")" );

			} else	{
				// CS does not seem to use accounting style, so let's do the opposite: if libicu uses it, remove it.
				$p = $formatter->getPattern();
				$p = explode(";", $p, 2 );

				if ( count( $p ) > 1 ) {
					$formatter->setPattern( $p[0] );
				}
			}

			// Although the formatter almost always formats 3-iso to the correct symbol (EUR to €) it seems that in some
			// scenarios (es-PA) it does not.
			// Since we have to make sure that the CS defined symbol ends in the invoice comments, just replace altogether
			// the currency masking character for our desired one.
			$new_pattern_with_cs_currency_symbol = str_replace('¤', $currency_symbol, $formatter->getPattern());
			$formatter->setPattern( $new_pattern_with_cs_currency_symbol );

			$formatter->setAttribute(\NumberFormatter::MIN_FRACTION_DIGITS, 2 );
			$formatter->setAttribute(\NumberFormatter::MAX_FRACTION_DIGITS, 6 );

			$totalTaxableAmount = $formatter->formatCurrency( $line['totalTaxableAmount'], $currency );
			$unitAmount = $formatter->formatCurrency( $line['unitAmount'], $currency );
			$vatAmount = $formatter->formatCurrency( $line['vatAmount'], $currency );
			$reAmount = $formatter->formatCurrency( $line['reAmount'], $currency );
			$total = $formatter->formatCurrency( $line['totalTaxableAmount'] + $line['vatAmount'] + $line['reAmount'], $currency );

		} else {
			// Fallback to universal PHP number_format() since PHP 4 with no dependencies.
			$this->logger->log( "Class 'NumberFormatter' not found in this system, PHP intl extension might not be enabled. Original product description in CS invoice line 'detailed description' may not use the correct local formatting." );

			// TODO Complete with all possible CS cultures
			switch ( $companyCulture )  {
				case 'es-MX':
				case 'es-GT':
				case 'es-DO':
				case 'es-US':
				case 'en-US':
				case 'en_GB':
					$thousands_separator = ',';
					$decimal_separator = '.';
					$currency_placing_mask = '%2%1';
					break;
				case 'es-PA':
					$thousands_separator = ',';
					$decimal_separator = '.';
					$currency_placing_mask = '%2 %1';
					break;
				case 'es-VE':
				case 'es-PE':
				case 'es-AR':
				case 'es-EC':
				case 'es-CL':
				case 'es-UY':
				case 'es-PY':
				case 'es_BO':
					$thousands_separator = '.';
					$decimal_separator = ',';
					$currency_placing_mask = '%2 %1';
					break;
				case 'es-CO':
					$thousands_separator = '.';
					$decimal_separator = ',';
					$currency_placing_mask = '%2%1';
					break;
				case 'fr-FR':
				case 'fr-CA':
				case 'fr-LU':
				case 'fr-MC':
					$thousands_separator = ' ';
					$decimal_separator = ',';
					$currency_placing_mask = '%1 %2';
					break;
				case 'de-CH':
					$thousands_separator = '\'';
					$decimal_separator = '.';
					$currency_placing_mask = '%2 %1';
					break;
				default:
					$thousands_separator = '.';
					$decimal_separator = ',';
					$currency_placing_mask = '%1 %2';
					break;
			}

			$totalTaxableAmount = self::cs_number_format( $line['totalTaxableAmount'], 6, $decimal_separator, $thousands_separator, $currency_symbol, $currency_placing_mask, in_array( $companyCulture, $cs_cultures_with_accounting_style) );
			$unitAmount = self::cs_number_format( $line['unitAmount'], 6, $decimal_separator, $thousands_separator, $currency_symbol, $currency_placing_mask, in_array( $companyCulture, $cs_cultures_with_accounting_style) );
			$vatAmount = self::cs_number_format( $line['vatAmount'], 6, $decimal_separator, $thousands_separator, $currency_symbol, $currency_placing_mask, in_array( $companyCulture, $cs_cultures_with_accounting_style) );
			$reAmount = self::cs_number_format( $line['reAmount'], 6, $decimal_separator, $thousands_separator, $currency_symbol, $currency_placing_mask, in_array( $companyCulture, $cs_cultures_with_accounting_style) );
			$total = self::cs_number_format( $line['totalTaxableAmount'] + $line['vatAmount'] + $line['reAmount'], 6, $decimal_separator, $thousands_separator, $currency_symbol, $currency_placing_mask, in_array( $companyCulture, $cs_cultures_with_accounting_style) );
		}

		$inner_description  = $line['concept'] . "<ul>";
		$inner_description .= sprintf( __( '<li> Total taxable: %1$s (%2$s unit price x %3$d units, discounts incl.)</li>', 'contasimple' ), $totalTaxableAmount, $unitAmount, $line['quantity'] );
		$inner_description .= sprintf( '<li> %1$s: %2$s</li>', $vatName, $vatAmount );

		if ( isset( $line['rePercentage'] ) && $line['rePercentage'] > 0 ) {
			$inner_description .= sprintf( '<li> R.E: %1$s%% (%2$s)</li>', $line['rePercentage'], $reAmount );
		}

		$inner_description .= sprintf( __( '<li> Total: %1$s</li>', 'contasimple' ), $total );
		$inner_description .= "</ul>";

		if ( isset( $new_lines[ $key ]['detailedDescription'] ) ) {
			return $new_lines[ $key ]['detailedDescription'] . '<li>' . $inner_description . '</li>';
		} else {
			return '<ul><li>' . $inner_description . '</li>';
		}
	}

	public function get_invoice_one_liner_emergency_sync( $cs_invoice, $order, $sign ) {
		try {
			$detailedDescription = null;

			// Check if a line contains no VAT amount nor prices (a zero line, might happen).
			// In this case this is not a deal breaker, we can remove it and still condense the invoice.
			foreach ( $cs_invoice['lines'] as $index => $line ) {
				if ( $line['totalTaxableAmount'] == 0 && $line['vatAmount'] == 0 ) {
					unset( $cs_invoice['lines'][$index] );
				} else {
					$detailedDescription = $line['detailedDescription'];
				}
			}

			// Now there must only remain one line with some kind of VAT rate.
			if ( count( $cs_invoice['lines'] ) !== 1 ) {
				throw new \Exception( 'Previously grouped by VAT invoice returning more than one line. This invoice has several different taxes applied and thus cannot be condensed into a one line invoice.', TOTAL_AMOUNT_INVALID );
			}

			// Amounts will come directly from the totals calculated by WC.
			$orderTotalAmountWithoutVAT = $order->get_total() - $order->get_total_tax();
			$orderTotalVAT = +$order->get_total_tax();

			// The only remaining VAT rate must be the correct one.
			$tax = array_shift( $cs_invoice['lines'] );

			if ( !empty( $tax ) && is_array( $tax ) ) {
				$orderVatPercentage = $tax['vatPercentage'];
				$orderVatRePercentage = $tax['rePercentage'];
				$orderTotalVAT = $orderTotalVAT - $tax['reAmount'];
				$orderTotalRE = $tax['reAmount'];
			} else {
				// In the event that there was an issue with the CS calculated line vat, do the manual calculation.
				$orderVatPercentage = round(( $order->get_total() / ($order->get_total() - $order->get_total_tax()) - 1 ) * 100, 1, PHP_ROUND_HALF_UP);
				$orderVatRePercentage = 0;
				$orderTotalRE = 0;
			}

			// Reset lines.
			unset( $cs_invoice['lines'] );

			// ... and inject our custom one.
			$cs_invoice['lines'][] = array(
				'concept' => __('Order total', 'contasimple'),
				'quantity' => 1 * $sign,
				'unitAmount' =>  $orderTotalAmountWithoutVAT,
				'vatAmount' => $orderTotalVAT * $sign,
				'vatPercentage' =>  $orderVatPercentage,
				'totalTaxableAmount' => $orderTotalAmountWithoutVAT,
				'reAmount' => $orderTotalRE * $sign,
				'rePercentage' => $orderVatRePercentage,
				'discountPercentage' => 0,
				'detailedDescription' => $detailedDescription
			);

			return $cs_invoice;

		} catch (\Exception $e) {
			$this->logger->log( "Exception thrown while generating an emergency sync invoice to avoid rounding issues: " . $e->getMessage() );
			throw $e;
		}
	}

	/**
	 * Get the CS equivalent payment method ID from WC selected method
	 *
	 * @param $method
	 *
	 * @return mixed
	 *
	 * @since   1.0.0
	 */
	public function get_payment_id_equivalence( $method ) {
		// Get the equivalences configured in the wizard
		$paymentEquivalences = $this->get_config()->getPaymentEquivalences();

		// It should be set, but if it is not, use the default one
		if ( ! array_key_exists( $method, $paymentEquivalences ) ) {
			$method = "default";
		}

		// Return an integer that the API can consume
		return $paymentEquivalences[ $method ];
	}

	/**
	 * Store company ID from the selected company via AJAX
	 *
	 * An APIkey will return one or more companies associated to a CS account. The user picks one and the wizard
	 * invokes this method to store the selection using WP Options.
	 *
	 * @since   1.0.0
	 */
	public function cs_select_company() {

		$result = array(
			'message' => __( 'Your company preferences have been updated', 'contasimple' )
		);

		try {
			$this->check_ajax_wizard_allowed();

			$companyId = (int) $_REQUEST['company'];
			if ( ! empty( $companyId ) ) {
				$registrationNumber = ''; // Configuration::get('PS_SHOP_DETAILS');
				$companiesList      = $this->cs->getCompanies();
				foreach ( $companiesList as $company ) {
					if ( $company->getId() == $companyId ) {
						if ( $company->getRequiresConfiguration() ) {
							$result = array(
								'error'   => true,
								'message' => __( 'Your Contasimple account is not fully configured. Please login into Contasimple.com to complete the configuration process.', 'contasimple' )
							);
						} else {
							if ( ! empty( $company->getExtraInformation() ) ) {
								if ( ! $this->registration_number_matches( $registrationNumber, $company->getExtraInformation()->getEntity()->getNif() ) ) {
									$result = array(
										'error'                 => true,
										'updateCompanyInfoStep' => true,
										'message'               => __( 'Your account needs additional configuration. Please wait...', 'contasimple' )
									);
								} else {
									$response = $this->cs->selectCompany( $companyId );

									// Get payments data and pass it down to the next step
									$responsePaymentMethods = $this->cs->getPaymentMethods();
									foreach ( $responsePaymentMethods as $paymentMethod ) {
										$paymentMethods[] = array(
											'id_option' => $paymentMethod->getId(),
											'name'      => $paymentMethod->getName(),
										);
									}
									$result['paymentMethods'] = $paymentMethods;

									$config = $this->get_config();
									$countryIso = null;

									// Generally the selected fiscal region has its own country ISO-2 code but
									// if the fiscal region is otra then we have to go deeper to get it by the country
									// name so that in the end we can always get an ISO-2 code to work with.
									if ( $company->getFiscalRegion()->getCode() == FiscalRegionApiModel::CODE_OTRA ) {
										$countryName = $company->getExtraInformation()->getEntity()->getCountry();
										$allCountries = $this->cs->getCountries('true');
										foreach ( $allCountries as $country ) {
											if ($country->getName() == $countryName) {
												$countryIso = $country->getIsoCodeAlpha2();
												break;
											}
										}
									} else {
										$countryIso = $company->getCountry()->getIsoCodeAlpha2();
									}

									// $config->setCurrencyISOCode( $company->getCountry()->getCurrency()->getShortName() );
									$config->setCurrencySymbol( $company->getExtraInformation()->getCurrencySymbol() );
									$config->setCountryISOCode( $countryIso );
									$config->setFiscalRegionCode( $company->getFiscalRegion()->getCode() );
									$config->setVatName( $company->getFiscalRegion()->getVatName() );
									$config->setInvoiceCulture( $company->getExtraInformation()->getInvoiceCulture() );

									$this->set_config( $config );
								}
							} else {
								$result = array(
									'error'   => true,
									'message' => __( 'Your Contasimple account is not fully configured. Please login into Contasimple.com to complete the configuration process.', 'contasimple' )
								);
							}
						}

						break;
					}
				}
			}
		} catch ( Exception $e ) {
			if ( $e->getCode() == API_ERROR ) {
				// If we get an API error, pass down this message to the end user as most likely this error is not
				// controlled and the error might be of interest.
				$result = array(
					'error'   => true,
					'message' => $e->getMessage()
				);
			} elseif ( $e->getCode() == AUTH_DENIED ) {
				$result = array(
					'error'   => true,
					'message' => __( 'Invalid grant', 'contasimple' )
				);
			} elseif ( $e->getCode() == INSUFFICIENT_RIGHTS_ERROR ) {
				// In the very special case that the user is picking a company which does not have enough rights
				// to use with the plugin, display a custom error, as the error returned by the API is too generic.
				$result = array(
					'error'   => true,
					'message' => __( 'User permissions in the selected company do not support synchronization with WooCommerce, please use user credentials with the appropriate permissions.', 'contasimple' )
				);
			} else {
				// If it's not an API error, just display a generic error regarding the company completion process.
				$result = array(
					'error'   => true,
					'message' => __( 'Your company selection could not be set. Please try again, and if the problem persists, contact us for technical support.', 'contasimple' )
				);
			}
		}

		echo wp_json_encode( $result );
		wp_die();
	}

	/**
	 * Store CS payment methods equivalences in WC via AJAX
	 *
	 * Invoked by the config wizard
	 *
	 * @since   1.0.0
	 */
	public function cs_select_payment_methods() {

		$result = array(
			'message' => __( 'Your payment preferences have been updated', 'contasimple' ) . ". " . __( 'Please, wait...', 'contasimple' )
		);

		try {
			$this->check_ajax_wizard_allowed();

			$paymentMethods = array();
			parse_str( $_REQUEST['data'], $paymentMethods );

			if ( ! empty( $paymentMethods ) ) {
				$config = $this->get_config();
				$config->setPaymentEquivalences( $paymentMethods );
				$this->set_config( $config );
			}

			// Get numbering series data and pass it down to the last step
			$responseNumberingSeries = $this->cs->getInvoiceNumberingFormats();

			foreach ( $responseNumberingSeries as $numberingSeries ) {
				$numberingSeriesList[] = array(
					'id_option' => $numberingSeries->getId(),
					'name'      => $numberingSeries->getName(),
					'type'      => $numberingSeries->getType()
				);
			}

			$result['numberingSeries'] = $numberingSeriesList;

		} catch ( Exception $e ) {
			$result = array(
				'error'   => true,
				'message' => __( $e->getMessage(), 'contasimple' )
			);
		}

		echo wp_json_encode( $result );
		wp_die();
	}

	/**
	 * Store CS numbering series for each invoice type in WC via AJAX
	 *
	 * Invoked by the config wizard
	 *
	 * @since   1.16.0
	 */
	public function cs_select_numbering_series() {

		$result = array(
			'message' => __( 'Your numbering series preferences have been updated', 'contasimple' ) . ". " . __( 'Please, wait...', 'contasimple' )
		);

		try {
			$this->check_ajax_wizard_allowed();

			$numberingSeries = array();
			parse_str( $_REQUEST['data'], $numberingSeries );

			if ( ! empty( $numberingSeries ) ) {

				$wc_options = get_option( 'woocommerce_integration-contasimple_settings' );
				$wc_options['refunds_series'] = $numberingSeries['select-refunds-series'];
				$wc_options['invoices_series'] = $numberingSeries['select-invoices-series'];
				$wc_options['receipts_series'] = $numberingSeries['select-receipts-series'];

				update_option( 'woocommerce_integration-contasimple_settings', $wc_options );
			}
		} catch ( Exception $e ) {
			$result = array(
				'error'   => true,
				'message' => __( $e->getMessage(), 'contasimple' )
			);
		}

		echo wp_json_encode( $result );
		wp_die();
	}

	/**
	 * Check access to a certain section is granted
	 *
	 * Uses WP nounces for validation and only allows access also if company info available by running the config wizard
	 *
	 * @param $nonce
	 * @param $action
	 *
	 * @return bool
	 */
	public function access_allowed( $nonce, $action ) {

		return function_exists( 'curl_version' ) && class_exists( 'Contasimple' ) && $this->properly_configured() && wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Check that AJAX calls during settings wizard are allowed
	 *
	 * We reuse the WC settings nonce
	 */
	public function check_ajax_wizard_allowed( ) {

		if ( ! check_ajax_referer( 'woocommerce-settings', null, false ) ) {

			$this->logger->log( 'Referrer WP nonce check failed. AJAX call not allowed.' );

			$result = array(
				'error'   => true,
				'message' => __( 'Access denied. Make sure you are still logged into WordPress.', 'contasimple' ),
			);

			echo wp_json_encode( $result );
			wp_die();
		}
	}

	/**
	 * Check that all data needed from running the config wizard exists
	 *
	 * We need to have an API key for a company to make all the other sections work.
	 *
	 * @return  bool      If config data is available
	 * @throws  Exception If something went work with the checks
	 *
	 * @since   1.0.0
	 */
	public function properly_configured() {

		$config = get_option( 'contasimple_settings_account' );
		$wc_options = get_option( 'woocommerce_integration-contasimple_settings' );

		if ( empty( $config ) || $config instanceof __PHP_Incomplete_Class ) {
			$config = new CSConfig();
		}

		try {
			// Without numbering series info.
			$properlyConfiguredLegacy = ! (
				   empty( $config->getAccessToken() )
				|| empty( $config->getRefreshToken() )
				|| empty( $config->getApiKey( 'apikey' ) )
				|| empty( $config->getCompanyId() )
				|| empty( $config->getExpireTime() )
				|| empty( $config->getPaymentEquivalences() )
			);

			// New expected data.
			$seriesInfoConfigured = ! (
				   empty( $wc_options['invoices_series'] )
				|| empty( $wc_options['refunds_series'] )
				|| empty( $wc_options['receipts_series'] )
			);

			// Try to migrate old mask settings to CS numbering series settings.
			if ($properlyConfiguredLegacy && !$seriesInfoConfigured) {
				$this->migrate_old_invoice_masks_to_cs_series();

				// Reload data to see if it had effect.
				$wc_options = get_option( 'woocommerce_integration-contasimple_settings' );

				$seriesInfoConfigured = ! (
					   empty( $wc_options['invoices_series'] )
					|| empty( $wc_options['refunds_series'] )
					|| empty( $wc_options['receipts_series'] )
				);
			}

			return $properlyConfiguredLegacy && $seriesInfoConfigured;

		} catch ( \Exception $e ) {

			$this->logger->log("Error trying to validate the plugin settings. Settings will be reset and the wizard will be displayed again. Details: " . $e->getMessage());
			$this->force_login();

			return false;
		}
	}

	/**
	 * Migrates the data about invoice masks present in the options table prior to plugin version 1.14 to the new
	 * format.
	 *
	 * We drop support for custom invoice masks set in WC and we start using Contasimple series entities instead.
	 * If a user updates the plugin while already configured, in order to ease transition we will try to create
	 * the series in CS for him based on the previous set masks.
	 *
	 * Users that install the plugin fresh new should not worry about this because the config wizard will force to
	 * pick series from CS already before letting the user move forward.
	 *
	 * @since 1.16
	 */
	public function migrate_old_invoice_masks_to_cs_series() {

		$types = array( 'refunds_', 'invoices_', 'receipts_' );
		$wc_options = get_option( 'woocommerce_integration-contasimple_settings' );
		$cs_language = Contasimple_WC_Helpers::get_contasimple_equivalent_current_wp_locale();
		$invoiceNumberingFormatsList = null;

		foreach ( $types as $invoiceType ) {
			if ( empty( $wc_options[$invoiceType . 'series'] ) ) {
				if ( !empty( $wc_options[$invoiceType . 'mask'] ) ) {

					// So there is no selected series yet for this document type,
					// but we have info about the mask used in earlier versions, migrate!

					if ( $invoiceNumberingFormatsList == null )
						$invoiceNumberingFormatsList = $this->cs->getInvoiceNumberingFormats($cs_language);

					$matching_series_found = false;
					$series_id = null;

					// Try to reuse a matching series.
					foreach ( $invoiceNumberingFormatsList as $serie ) {
						if ( $serie->getMask() == $wc_options[$invoiceType . 'mask'] ) {
							$series_id = $serie->getId();
							$matching_series_found = true;
							break;
						}
					}

					// Otherwise create a new series in Contasimple.
					if ( !$matching_series_found ) {
						if ( $invoiceType == 'refunds_' ) {
							$name = __( 'Credit notes', 'contasimple' );
							$type = 'Rectifying';
						} elseif ( $invoiceType == 'receipts_')  {
							$name = __( 'Receipts', 'contasimple' );
							$type = 'Normal';
						} else {
							$name = __( 'Invoices', 'contasimple' );
							$type = 'Normal';
						}

						$data = array(
							'name' => 'WooCommerce ' . $name,
							'type' => $type,
							'mask' => $wc_options[$invoiceType . 'mask']
						);

						$new_series = $this->cs->createInvoiceNumberingFormat($data, $cs_language);
						$series_id = $new_series->getId();
					}

					// In any case, store series id as a new setting and get rid of the mask setting
					// to ensure this process is not run again.
					$wc_options[$invoiceType . 'series'] = $series_id;
					$wc_options[$invoiceType . 'mask'] = null;

					$result = update_option( 'woocommerce_integration-contasimple_settings', $wc_options );

					// TODO log
				}
			}
		}
	}

	/**
	 * Delete current user settings (forces the request of needed user info)
	 *
	 * Deletes all stored contasimple variables from ps_configuration table.
	 * The configuration wizard will have to be run again to reconfigure the module.
	 *
	 * @since   1.0.0
	 */
	public function force_login() {

		delete_option( 'woocommerce_integration-contasimple_settings' );
		delete_option( 'contasimple_settings_account' );

		$this->set_config( new CSConfig() );
	}

	/**
	 * Check if two NIFs match
	 *
	 * Used to check if WC shop owner and CS selected company fiscal ID do match.
	 *
	 * @param $numberWP
	 * @param $numberCS
	 *
	 * @return bool
	 *
	 * @deprecated  It seems we are no longer doing this check since it complicates too much initial configuration.
	 */
	public static function registration_number_matches( $numberWP, $numberCS ) {
		$numberWP = strtolower( preg_replace( "/[^[:alnum:]]/u", "", $numberWP ) );
		$numberCS = strtolower( preg_replace( "/[^[:alnum:]]/u", "", $numberCS ) );

		//return $numberWP === $numberCS;
		return true; // TODO Decide what to do, remove this validation or implement somehow in WP¿
	}

	/**
	 * Check if there exist logs for the selected date
	 *
	 * @since   1.0.0
	 */
	public function check_for_log() {
		try {
			$this->check_ajax_wizard_allowed();

			$result = false;
			$date   = esc_attr( $_REQUEST['date'] );

			$logs_dir  = plugin_dir_path( __DIR__ ) . "logs" . DIRECTORY_SEPARATOR;
			$file_name = "contasimple_" . date( 'd-m-Y', strtotime( str_replace( '/', '-', $date ) ) ) . ".log";
			$full_path = $logs_dir . $file_name;

			if ( file_exists( $full_path ) ) {
				$result = true;
			} else {
			}
		} catch ( \Exception $e ) {
		}

		echo wp_json_encode( $result );
		wp_die();
	}

	/**
	 * Download log file for the selected date
	 *
	 * @param $date
	 *
	 * @since   1.0.0
	 */
	public function download_cs_log( $date ) {
		$logs_dir  = plugin_dir_path( __DIR__ ) . "logs" . DIRECTORY_SEPARATOR;
		$file_name = "contasimple_" . date( 'd-m-Y', strtotime( str_replace( '/', '-', $date ) ) ) . ".log";
		$full_path = $logs_dir . $file_name;

		header( 'Content-Type: application/text' );
		header( 'Content-Disposition: attachment; filename=' . $file_name );
		header( 'Pragma: no-cache' );

		readfile( $full_path );

		exit();
	}

	/**
	 * Create a new numbering series.
	 *
	 * @since   1.16.0
	 */
	public function create_new_series() {
		try {
			$this->check_ajax_wizard_allowed();

			$result = array(
				'success' => false
			);

			$name = esc_attr( $_REQUEST['name'] );
			$type = esc_attr( $_REQUEST['type'] );
			$mask = esc_attr( $_REQUEST['mask'] );

			$cs_language = Contasimple_WC_Helpers::get_contasimple_equivalent_current_wp_locale();

			$data = array(
				'name' => $name,
				'type' => $type,
				'mask' => $mask
			);

			$new_series = $this->cs->createInvoiceNumberingFormat( $data, $cs_language );

			$result['success'] = true;
			$result['element'] = '<option value="' . $new_series->getId() . '">' . $new_series->getName() . '</option>';

		} catch ( \Exception $e ) {
			$result['error'] = $e->getMessage();
		}

		echo wp_json_encode( $result );
		wp_die();
	}

	/**
	 * Attach a PDF file to our invoice email class
	 *
	 * @param   $attachments
	 * @param   null $email_id
	 * @param   null $order
	 * @param   null $cs_invoice
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	public function attach_cs_invoice( $attachments, $email_id = null, $order = null, $cs_invoice = null ) {

		// Only for our custom CS Invoice email type.
		if ( $email_id === 'wc_cs_invoice_generated' && ! empty( $cs_invoice ) ) {

			$id     = $cs_invoice->externalID;
			$date   = date( 'Y-m-d', strtotime( $cs_invoice->date_sync ) ); //$order->get_date_completed()->date( 'Y-m-d' );
			$period = Contasimple_CPT::get_period_from_date( $date );
			$number = $cs_invoice->number;

			$pdf_as_string = $this->cs->getInvoicePDFasString( $id, $period, $number );

			$upload_dir = wp_upload_dir();

			if ( ! empty( $upload_dir['basedir'] ) ) {
				$plugin_dirname = $upload_dir['basedir'] . '/cs_pdf_invoices';
				if ( ! file_exists( $plugin_dirname ) ) {
					wp_mkdir_p( $plugin_dirname );
				}
			}

			$file = mb_ereg_replace( "([^\w\s\d\-_~,;\[\]\(\).])", '', $cs_invoice->number );
			$file = mb_ereg_replace( "([\.]{2,})", '', $file );

			$file_path = $plugin_dirname . DIRECTORY_SEPARATOR . $file . '.pdf';

			file_put_contents( $file_path, $pdf_as_string );

			$attachments[] = $file_path;
		}

		return $attachments;
	}

	/**
	 * Allow screen options in invoices page
	 *
	 * @since   1.0.0
	 */
	public function add_options() {

		$option = 'per_page';
		$args = array(
			'label' => __('Orders per page', 'contasimple'),
			'default' => 10,
			'option' => 'orders_per_page'
		);

		add_screen_option( $option, $args );

		// Adds help screen, enhancement?
		/*
		get_current_screen()->add_help_tab( array(
			'id'		=> 'custom',
			'title'		=> __('Custom'),
			'content'	=>
				'<p>' . __('This screen provides access to all of your posts. You can customize the display of this screen to suit your workflow.') . '</p>'
		) );
		*/
	}

	/**
	 * Add our desired options to options screen
	 *
	 * Like how many per page
	 *
	 * @since   1.0.0
	 */
	public function set_screen_options() {

		add_filter('set-screen-option', array(&$this, 'set_option'), 10, 3);
	}

	public function set_option($status, $option, $value) {

		return $value;
	}

	/**
	 * Add a menu entry and define page URL for previous orders importation
	 *
	 * @since   1.0.0
	 */
	public function contasimple_add_previous_woocommerce_orders() {

		// Register the hidden sub-menu.
		$hook = add_submenu_page(
			'woocommerce' // Use the parent slug as usual.
			, __( 'Import previous orders', 'contasimple' )
			, ''
			, 'manage_options'
			, 'create_cs_invoices'
			, array(&$this, 'display_create_cs_invoices' )
		);

		add_action( "load-$hook", array (&$this, 'add_options') );
	}

	/**
	 * Make the new entry menu look nice
	 *
	 * @param   $submenu_file
	 *
	 * @return  string
	 *
	 * @since   1.0.0
	 */
	function contasimple_admin_submenu_filter( $submenu_file ) {

		global $plugin_page;

		$hidden_submenus = array(
			'create_cs_invoices' => true,
		);

		// Select another submenu item to highlight (optional).
		if ( $plugin_page && isset( $hidden_submenus[ $plugin_page ] ) ) {
			$submenu_file = 'edit.php?post_type=shop_order';
		}

		// Hide the submenu.
		foreach ( $hidden_submenus as $submenu => $unused ) {
			remove_submenu_page( 'woocommerce', $submenu );
		}

		return $submenu_file;
	}

	/**
	 * Create a page to import invoices from previous orders
	 *
	 * This makes use of the WP List Table class.
	 *
	 * @since   1.0.0
	 */
	public function display_create_cs_invoices() {

		$myListTable = new Contasimple_WP_List_Table_WC_Orders();

		$doaction = $myListTable->current_action();

		// If there's a form action, process it!
		if ( $doaction ) {
			switch ( $doaction ) {
				case 'create':
					$this->logger->logTransactionStart( 'Called: ' . __METHOD__ . ' with bulk invoice create action.' );

					$order_ids = array_reverse( apply_filters( 'request', $_REQUEST['order_ids'] ) );
					$total = count( $order_ids );
					$count = 0;

					// If there are any checked rows, get their IDs and iterate and add the invoice
					if ( $total > 0 ) {
						$unique_series_prefix = substr(md5(microtime()),rand(0,26),4) . "_";
						foreach ( $order_ids as $order_id ) {
							// If order has refunds see if we can add it or better skip.
							$order = Contasimple_WC_Backward_Compatibility::get_order_from_id( $order_id );
							$order_invoices = CS_Invoice_Sync::get_invoices_from_order( $order_id );
							$has_refunds = Contasimple_WC_Backward_Compatibility::get_invoice_sign_from_order( $order ) < 0;

							if ($this->invoice_can_be_added_from_previous( $order_invoices, $has_refunds ) ) {
								$contasimple_invoice_sync = CS_Invoice_Sync::create_empty( $order_id );
								if ( ! empty( $contasimple_invoice_sync ) ) {
									if ( $this->properly_configured() ) {
										$contasimple_invoice_sync->companyID = $this->get_config()->getCompanyId();
										$contasimple_invoice_sync->save();
										$count++;
										$this->logger->log( 'Added CS invoice to queue from order id ' . $order_id . ' with mask ' . $contasimple_invoice_sync->mask );
									} else {
										$this->logger->log( 'Trying to add previous invoices to the queue but configuration missing? Should not be here.' );
									}
								} else {
									$this->logger->log( 'Error creating an entry into the database for the previous order.' );
								}
							} else {
								$this->logger->log( 'Order has refunds and no original invoice found, skipping refund.' );
							}
						}

						// We must redirect the user to this same page without the post data, otherwise we could potentially duplicate invoices!
						$new_url = add_query_arg(
							array (
								'count' => $count,
								'total' => $total,
							),
							menu_page_url( 'create_cs_invoices', false )
						);

						wp_redirect( $new_url, 303 );
					}
					break;
			}
		} else {
			$count = filter_input( INPUT_GET, 'count' );
			$total = filter_input( INPUT_GET, 'total' );;

			// Only if we added these two query args previously, it means we already added invoices and we want to inform the end user
			if ( !empty( $count ) && ! empty ( $total ) ) {
				if ($total == $count) {
					$result_mesasge = new Contasimple_Notice( sprintf( __( 'Invoices successfully added to the queue.', 'contasimple' ), $count, $total ), 'success' );
				} else {
					$result_mesasge = new Contasimple_Notice( sprintf( __( '%1$d out of %2$d invoices added. Some errors where found.', 'contasimple' ), $count, $total ), 'error' );
				}

				$result_mesasge->render();
			}

			// In any case, build the table list and show remaining orders
			$myListTable->render_before_table();
			$myListTable->prepare_items();
			$myListTable->display();
			$myListTable->render_after_table();
		}
	}

	/**
	 * Check that the order total and vat amounts and the amounts gathered by the plugin match before sending
	 *
	 * @param $order_subtotal
	 * @param $order_tax_total
	 * @param $cs_invoice
	 * @param $strategy
	 * @param $sign
	 * @return int 1 if the amounts do exactly match, 0 if there is a small difference allowed (constant threshold), -1 if the difference is greater than the defined rounding threshold.
	 * @throws Exception If there is an error that goes beyond amount validation, and we need to raise a special case exception to display a different message.
	 *
	 * @since   1.0.0
	 */
	public function validate_invoice_amounts( $order_subtotal, $order_tax_total, &$cs_invoice, $strategy = 1, $sign = 1 ) {

		$totalWithoutVat = 0;
		$totalVat = 0;
		$totalReAmount = 0;
		$totalIrpfAmount = 0;
		$vatRate = 0;

		if ( !empty( $cs_invoice['lines'] ) ) {
			foreach ($cs_invoice['lines'] as $key => $line) {

				// The API can accept a different VAT amount despite the VAT rate, but we must not allow this to happen
				// automatically as it is most likely an error and cannot go unnoticed.
				$vat_computation_remainder = abs( $line['totalTaxableAmount'] * ( $line['vatPercentage'] + $line['rePercentage'] ) / 100 - $line['vatAmount'] - $line['reAmount'] );

				// If invoice already grouped, allow slightly higher differences in total amounts, for example 0.0012
				// might pass as it is almost 1 cent difference. The invoice is already marked as synced with rounding
				// errors so the user is informed and the amounts with full decimals will show in the invoice in the
				// advanced comments.
				if ($strategy > 1) {
					$vat_computation_remainder_without_rounding = $vat_computation_remainder;
					$vat_computation_remainder = round( $vat_computation_remainder, CS_MAX_DECIMALS, PHP_ROUND_HALF_UP );

					if ( ($vat_computation_remainder_without_rounding > 0 + CS_ROUNDING_THRESHOLD) && !($vat_computation_remainder > 0 + CS_ROUNDING_THRESHOLD) ) {
						$cs_invoice['warning'] = SYNCED_WITH_DISCREPANCY;
					}
				}

				if ( $vat_computation_remainder > 0 + CS_ROUNDING_THRESHOLD ) {
					$this->logger->log( "Line #[" . $key . "]: Total Taxable Amount * VAT percentage / 100 - VAT amount "
						. " does not add to zero or allotted threshold [" . $vat_computation_remainder .
						" difference]" );
					return -1;
				}

				// The API won't digest unit price x qty != total taxable amount, account for it.
				$qty_per_unit_price_remainder = abs( $line['quantity'] * $line['unitAmount'] * ( 1 - $line['discountPercentage'] / 100 ) - $line['totalTaxableAmount'] );
				if ( $qty_per_unit_price_remainder > 0 + CS_ROUNDING_THRESHOLD ) {
					$this->logger->log( "Line #[" . $key . "]: Unit price x qty x discount percentage - total taxable amount "
						. " does not add to zero or allotted threshold [" . $qty_per_unit_price_remainder .
						" difference]" );
					return -1;
				}

				// Line-specific checks seem OK, add amounts and keep iterating.
				$totalWithoutVat += $line['totalTaxableAmount'] * $sign;
				$totalVat += $line['vatAmount'] * $sign;
				if ( !empty( $line['reAmount'] ) ) $totalReAmount += $line['reAmount'] * $sign;
                if ( !empty( $line['vatPercentage'] ) ) $vatRate = $line['vatPercentage'];
			}
		}

		// Global amount checks.
		$order_expected_retention = round(( $order_subtotal * $cs_invoice['retention_percentage'] / 100 ) * $sign, CS_MAX_DECIMALS, PHP_ROUND_HALF_UP);

		// The epsilon is very small number used because of PHP issues with float comparison due to internal
		// representation and is equivalent to comparing with zero.
		$epsilon = 0.0001;

		if ( !empty( $cs_invoice['retentionAmount'] ) ) {
			$totalIrpfAmount = $cs_invoice['retentionAmount'];

			// The IRPF amount that we reported to CS should be equal to the original subtotal multiplied by the IRPF
			// rate, if not, then some of the line items did not have IRPF and we cannot let it sync.
			if ( abs ( $order_expected_retention -  $totalIrpfAmount ) < $epsilon  ) {
				$this->logger->log( 'Order IRPF values in WC match the calculated by CS, OK!' );
			} else {
				throw new Exception( 'Order IRPF (' . $totalIrpfAmount . ') does not match CS IRPF total amounts (' . $order_expected_retention . '), KO!', IRPF_PER_LINE_NOT_ALLOWED );
			}
		}

		$totalTaxes =  $totalVat + $totalReAmount - $totalIrpfAmount;

		$totalAmountDifference = round( $order_subtotal - $totalWithoutVat, CS_MAX_DECIMALS, PHP_ROUND_HALF_UP );
		$totalVATAmountDifference = round( $order_tax_total - $totalTaxes, CS_MAX_DECIMALS, PHP_ROUND_HALF_UP );

		if ( abs( $totalAmountDifference ) < $epsilon && abs($order_tax_total - $totalTaxes ) < $epsilon ) {
			$this->logger->log( 'Order total and vat amounts match those calculated by CS, OK!' );
			return 1;

		} else if ( abs( $totalAmountDifference ) <= CS_ROUNDING_THRESHOLD && abs( $totalVATAmountDifference ) <= CS_ROUNDING_THRESHOLD  ) {
			// Amounts differ due to rounding issues but up to a certain tolerable threshold,
			// so add the remaining cents as a new invoice line so that total amounts will match in both platforms.
			//return $this->handleAllowedDifferenceViaAdditionalLine($cs_invoice, $totalWithoutVat, $totalTaxes, $order_subtotal, $order_tax_total, $vatRate, $sign );
			return $this->handleAllowedDifferenceViaRedistribution($cs_invoice, $totalWithoutVat, $totalTaxes, $order_subtotal, $order_tax_total );
		} else {
			$this->logger->log( 'WC Order total (' . $order_subtotal . ') and VAT amounts (' . $order_tax_total
			                    . ') DO NOT match those calculated by CS ('
			                    . $totalWithoutVat . ' and ' . $totalTaxes . ' respectively), KO!');
			return -1;
		}
	}

	/**
	 * Check customer NIF validity based on country defined REGEX
	 *
	 * We retrieve the countries list from the CS API
	 *
	 * @param $nif
	 * @param $countryIsoCode
	 *
	 * @return bool
	 *
	 * @since   1.0.0
	 */
	public function is_valid_nif( $nif, $countryIsoCode ) {

		$countries = $this->get_cached_cs_countries();

		foreach ( $countries as $country ) {
			if ( $country->getIsoCodeAlpha2() == $countryIsoCode ) {
				$pattern = '/' . $country->getNifValidationRegex() . '/';

				if ( ! preg_match( $pattern, $nif ) ) {
					return false;
				}
			}
		}

		return true;
	}

	/**
	 * Get the customer country in CS based on WC country ID
	 *
	 * @param $customer_country
	 *
	 * @return int If not matching found, will return 999 (other for CS)
	 *
	 * @since   1.0.0
	 */
	public function get_cs_country_id_from_wc_iso( $customer_country ) {

		$customer_country_in_cs = 999;
		$countries = $this->get_cached_cs_countries();

		foreach ( $countries as $country ) {
			if ( $country->getIsoCodeAlpha2() == $customer_country ) {
				$customer_country_in_cs = $country->getId();
				break;
			}
		}

		return $customer_country_in_cs;
	}

	/**
	 * Retrieve countries from CS API
	 *
	 * We use transients to cache them temporally since this should not change frequently.
	 *
	 * @return  mixed|\Swagger\Client\Model\CountryApiModel[]
	 *
	 * @since   1.0.0
	 */
	public function get_cached_cs_countries() {
		// Read about WP Transient API.
		$countries = get_transient( 'country_API_results' );

		// If not already set, fetch countries from the API and save the transient.
		if ( false === $countries ) {
			try {
				$countries = self::getInstance()->get_service()->getCountries( 'true' );
				set_transient( 'country_API_results', $countries, COUNTRIES_EXPIRATION_IN_SECONDS );
			} catch ( \Exception $e ) {
				$this->logger->log( 'Exception thrown while trying to load CS countries: ' . $e->getMessage() );
				$countries = array();
			}
		} else {
			// We avoid calling the API for subsequent NIF checks until the transient expires.
			$countries = get_transient( 'country_API_results' );
		}

		return $countries;
	}


	/**
	 * Return an array with VAT and RE values and checks that additional VAT rates are either RE or IRPF
	 *
	 * TODO The internals of this method should be reviewed, probably there's a better way to handle this.
	 * Also it only works for Spain.
	 *
	 * @param $order
	 * @param $taxes
	 *
	 * @return array An array with the VAT and R.E. amounts and percentages found (0 for each not found).
	 *
	 * @throws Exception If none of the additional vat rates are either RE or IRPF
	 */
	public function calculate_cs_line_taxes ( $item, $order, $attempt ) {

		$type = Contasimple_WC_Backward_Compatibility::get_order_item_type( $item );

		switch( $type ) {

			case 'line_item':
				$product    = Contasimple_WC_Backward_Compatibility::get_product_from_order_item( $item );
				$item_taxes = Contasimple_WC_Backward_Compatibility::get_order_item_taxes( $item, $product, $order, $attempt );
				break;

			case 'shipping':
				$item_taxes = Contasimple_WC_Backward_Compatibility::get_order_shipping_item_taxes( $item, $order );
				break;

			case 'fee':
				$item_taxes = Contasimple_WC_Backward_Compatibility::get_order_fee_item_taxes( $item ); //$item->get_taxes()['total'];
				break;

            case 'coupon':
                $item_taxes = Contasimple_WC_Backward_Compatibility::get_order_coupon_item_taxes( $item, $order );

                break;
		}

		$cs_line_taxes = array(
			'vatAmount'            => 0,
			'vatPercentage'        => 0,
			'reAmount'             => 0,
			'rePercentage'         => 0,
			'retention_percentage' => 0,
			'retentionAmount'      => 0,

		);

		if ( ! isset( $item_taxes ) || count( $item_taxes ) == 0 ) {
			return $cs_line_taxes;
		}

		$is_req = false;
		$is_irpf = false;

		$deleted_taxes = false;

		foreach ( $item_taxes as $tax_id => $tax_amount ) { // https://docs.woocommerce.com/wc-apidocs/source-class-WC_Tax.html#651-673
			// Skip weird cases of empty value taxes but forming part of the tax array.
			if ( empty( $tax_amount ) ) {
				unset( $item_taxes[$tax_id] );
				continue;
			}

			// At this point we have an array of tax_ids as indexes and computed tax amounts (in currency) as values.
			// Ex. 15 => "3.45", ...
			// But, we should not directly fetch the tax % rate from the DB based on the rate id because if the user happens to
			// update the value of that tax entity (ie: removes 21% and sets 10%) we will get a rate value that does NOT equal the
			// one used when the order was set in the cart back in the day.
			if ( version_compare( WC_VERSION, '2.7', '<' ) || ! method_exists( 'WC_Order_Item_Tax', 'get_rate_percent' ) ) {
				// Get tax rate % directly from it's ID. Not recommended but we will keep it like this in WC prior to
				// 3.0 because with the old API it is difficult to get the used value during the checkout.
				// This can cause issues if the user updates the value of the tax entity.
				$tax_name = WC_Tax::get_rate_label($tax_id);
				$tax_name_lower = strtolower( $tax_name );
				$tax_rate = Contasimple_WC_Backward_Compatibility::get_tax_rate( $tax_id );
			} else {
				// Recent WC versions.
				// So, in the end, once we got the rate_id and the amounts, we have to travel back to the order 'tax_lines'
				// item array and look for the rate % value during THAT time, to be 100% certain that we are accurate.
				foreach ($order->get_taxes() as $order_item_tax_id => $order_item_tax) {
					if ($order_item_tax->get_rate_id() == $tax_id) {
						$tax_name = $order_item_tax->get_label();
						$tax_name_lower = strtolower( $tax_name );
						$tax_rate = $order_item_tax->get_rate_percent();
						break;
					}
				}
			}

			// Should not happen, but what if tax deleted or cannot be determined?
			if ($tax_amount > 0 && $tax_rate == 0) {
				if ( 'coupon' == $type ) {
					$deleted_taxes = false;
					$this->logger->log( "Tax rate from id is 0 and this is a coupon code." );
				} else {
					$deleted_taxes = true; // maybe, our best guess.
					$this->logger->log( "Tax rate from id is 0 but vat amount detected, maybe tax was deleted?" );
				}

				$this->logger->log( "Trying to calculate tax rate from amounts with formula..." );
				$tax_rate = Contasimple_WC_Backward_Compatibility::calculate_tax_rate_from_amounts( $item, $tax_amount );

				$found_matching_tax_rate = false;

				foreach( $order->get_taxes() as $order_tax ) {

					// If taxes look very similar, most likely it's a rounding issue and we can pick the WC order tax rate.
					if ( method_exists( $order_tax, 'get_rate_percent' ) ) {
						$rate_percent = $order_tax->get_rate_percent();
					} else { // Fix for WC 3.5.x
						$rate_percent = WC_Tax::_get_tax_rate( $order_tax->get_rate_id() )['tax_rate'];
					}

					if ( abs( $rate_percent - $tax_rate) < 1 ) {
						$found_matching_tax_rate = true;
						$this->logger->log( "Found a matching VAT rate in the order (" .
							$rate_percent . '%) for the auto-calculated rate ' . $tax_rate . '%, updating...' );

						$tax_rate = $rate_percent;
						break;
					}
				}

				// Special case for coupons: we have to be careful to check that total amount discounts are not
				// being mixing different VAT types. If applies to both 21% and 10% VAT then this is not allowed.
				if ( 'coupon' == $type ) {
					if ( !$found_matching_tax_rate ) {
						throw new \Exception( '', COUPON_TOTAL_AMOUNT_WITH_MORE_THAN_ONE_TAX );
					} else {
						// Make sure that every product item (not shipping, etc) applies the same VAT rate.
						foreach( $order->get_items( 'line_item' ) as $item ) {
							$product    = Contasimple_WC_Backward_Compatibility::get_product_from_order_item( $item );
							$item_taxes = Contasimple_WC_Backward_Compatibility::get_order_item_taxes( $item, $product, $order, $attempt );

							if ( empty( $item_taxes ) || count( $item_taxes ) > 1 )
								throw new \Exception( '', COUPON_TOTAL_AMOUNT_WITH_MORE_THAN_ONE_TAX );

							$tax_rate_for_each_item = Contasimple_WC_Backward_Compatibility::get_tax_rate( array_keys( $item_taxes )[0] );

							if ( $tax_rate != $tax_rate_for_each_item )
								throw new \Exception( '', COUPON_TOTAL_AMOUNT_WITH_MORE_THAN_ONE_TAX );
						}
					}
				}

				$this->logger->log( "Calculated VAT rate: " . $tax_rate );
			}

			// If any of these values are found in the tax line title, we assume it's a valid R.E tax
			// TODO Simplify this or find a better strategy!
			if ( strpos( $tax_name_lower, 'equivalencia' ) === 0 ||
			     strpos( $tax_name_lower, 're' ) === 0 ||
			     strpos( $tax_name_lower, 'r.e' ) === 0 ||
			     strpos( $tax_name_lower, '0.5' ) === 0 ||
			     strpos( $tax_name_lower, '0,5' ) === 0 ||
			     strpos( $tax_name_lower, '1.4' ) === 0 ||
			     strpos( $tax_name_lower, '1,4' ) === 0 ||
			     strpos( $tax_name_lower, '1.75' ) === 0 ||
			     strpos( $tax_name_lower, '1,75' ) === 0 ||
			     strpos( $tax_name_lower, '5.2' ) === 0 ||
			     strpos( $tax_name_lower, '5,2' ) === 0 ||
			     $tax_rate == 5.2 ||
			     $tax_rate == 1.4 ||
			     $tax_rate == 1.75
			) {
				$cs_line_taxes['reAmount']     = $tax_amount;
				$cs_line_taxes['rePercentage'] = $tax_rate;

				$is_req = true;
			}

			else if ( strpos( $tax_name_lower,'irpf' ) === 0 || strpos( $tax_name_lower,'i.r.p.f' ) === 0) {
				// IRPF is not part of a line in CS, but we return it to build the total sum for the invoice.
				$is_irpf = true;
				$cs_line_taxes['retention_percentage'] = $tax_rate * -1;
				$cs_line_taxes['retentionAmount'] = $tax_amount * -1;
			}

			else {
				$cs_line_taxes['vatPercentage'] = $tax_rate;
				$cs_line_taxes['vatAmount'] = $tax_amount;
			}
		}

		if (!(
			0 === count( $item_taxes ) ||
			1 === count( $item_taxes ) && ! $is_req ||             // Only VAT or IRPF but not R.E is OK
			2 === count( $item_taxes ) && $is_req && ! $is_irpf || // Only VAT and R.E is OK
			2 === count( $item_taxes ) && $is_irpf && ! $is_req || // Only VAT and IRPF is OK
			3 === count( $item_taxes ) && $is_req && $is_irpf      // Only VAT, R.E and IRPF is OK
		))
		{
			$this->logger->log("Item taxes: " . count( $item_taxes ) .
			                   ", is_req: "  . ( $is_req == true ? "true" : "false" ) .
			                   ", is_irpf: " . ( $is_irpf == true ? "true" : "false" ) .
			                   ", taxes: " . json_encode( $item_taxes ) ) .
							   ", deleted taxes: " . ( $deleted_taxes ? "true" : "false" );

			if ( $deleted_taxes ) {
				throw new \Exception( '', DELETED_TAXES );
			} else {
				throw new \Exception( '', TAXES_PER_LINE_TOO_COMPLEX );
			}
		}

		return $cs_line_taxes;
	}

	public function add_line_to_cs_invoice(	&$cs_invoice, &$cs_invoice_without_rounding,
		$concept,
		$unit_amount,
		$quantity,
		$total_taxable_amount,
        $discount_percentage,
		$item,
		$sign,
		$order,
        $attempt
	) {

		$line_taxes = $this->calculate_cs_line_taxes( $item, $order, $attempt );

		// For now, keep a line with all it's decimal details.
		$line = array(
			'concept'            => $concept,
			'unitAmount'         => $unit_amount,
			'quantity'           => $quantity * $sign, // handle refund via qty * -1
			'totalTaxableAmount' => $total_taxable_amount * $sign,
            'discountPercentage' => $discount_percentage,
			'vatPercentage'      => $line_taxes['vatPercentage'],
			'vatAmount'          => $line_taxes['vatAmount'] * $sign,
			'rePercentage'       => $line_taxes['rePercentage'],
			'reAmount'           => $line_taxes['reAmount'] * $sign
		);

		// Round the line > this is what we will send to CS.
		$line_rounded = $this->round_cs_line_item( $line );

		$cs_invoice['lines'][] = $line_rounded;
		$cs_invoice_without_rounding['lines'][] = $line;

		// IRPF (if any) is not accounted at the line level in CS, but the total order.
		$cs_invoice['retentionAmount'] += $line_taxes['retentionAmount'] * $sign;
		$cs_invoice_without_rounding['retentionAmount'] = $cs_invoice['retentionAmount'];

		// If a line has no IRPF percentage, let's keep the previous value, maybe this line has a 0 price and should
		// not make the invoice to break.
		if ( $line_taxes['retention_percentage'] > 0 ) {
			$cs_invoice['retention_percentage'] = $line_taxes['retention_percentage'];
			$cs_invoice_without_rounding['retention_percentage'] = $cs_invoice['retention_percentage'];
		}
	}

	/**
	 * Handles the minimum difference allowed for the invoice to be synced.
	 *
	 * Given a Contasimple invoice array that has a total amount and/or tax amount only differing from the original order
	 * amounts by the minimum allowed amount (ex: 1 cent), this method fixes the problem by adding an additional line
	 * so that both total amounts are exact before sending the invoice.
	 *
	 * @deprecated This fix is not valid anymore. Use handleAllowedDifferenceViaRedistribution() instead.
	 *
	 * @param array $cs_invoice       An array containing the Contasimple invoice data that is being validated.
	 * @param float $totalWithoutVat  The total amount without taxes calculated by Contasimple algorithm from the order lines.
	 * @param float $totalTaxes       The total tax amount calculated by Contasimple algorithm from the order lines.
	 * @param float $order_subtotal   The total amount without taxes as stored by the CMS.
	 * @param float $order_tax_total  The total tax amount as stored by the CMS.
	 * @param float $vatRate          The vat rate applied to the invoice.
	 * @param int $sign               1 if regular invoice, -1 if refund. Used to multiply all other relevant amounts.
	 *
	 * @return int @see validate_invoice_amounts()
	 */
	protected function handleAllowedDifferenceViaAdditionalLine( &$cs_invoice, $totalWithoutVat, $totalTaxes, $order_subtotal, $order_tax_total, $vatRate, $sign ) {

		// If less than this (ej: 0.001) do not bother trying to fit the amount, is meaningless.
		$minimum = 0.01;

		$totalAmountDifference = round( $order_subtotal - $totalWithoutVat, CS_MAX_DECIMALS, PHP_ROUND_HALF_UP );
		$totalVATAmountDifference = round( $order_tax_total - $totalTaxes, CS_MAX_DECIMALS, PHP_ROUND_HALF_UP );

		if ( abs( $totalAmountDifference ) >= $minimum ) {
			$this->logger->log( 'Order total computed by CS differs slightly from WC, sync OK but with WARNING.' );
			$line = array(
				'concept'            => __( 'Rounding adjustment for total price without VAT', 'contasimple' ),
				'unitAmount'         => $order_subtotal - $totalWithoutVat,
				'quantity'           => 1 * $sign, // handle refund via qty * -1
				'totalTaxableAmount' => $order_subtotal - $totalWithoutVat,
				'vatPercentage'      => $vatRate,
				'vatAmount'          => 0,
				'rePercentage'       => 0,
				'reAmount'           => 0,
				'discountPercentage' => 0
			);

			$cs_invoice['lines'][] = $this->round_cs_line_item( $line );
		}

		if ( abs( $totalVATAmountDifference ) >= $minimum ) {
			$this->logger->log( 'Total VAT computed by CS differs slightly from WC, sync OK but with WARNING.' );
			$line = array(
				'concept'            => __( 'Rounding adjustment for VAT amount', 'contasimple' ),
				'unitAmount'         => 0,
				'quantity'           => 1 * $sign, // handle refund via qty * -1
				'totalTaxableAmount' => 0,
				'vatPercentage'      => $vatRate,
				'vatAmount'          => ( $order_tax_total - $totalTaxes ) * $sign,
				'rePercentage'       => 0,
				'reAmount'           => 0,
				'discountPercentage' => 0
			);

			$cs_invoice['lines'][] = $this->round_cs_line_item( $line );
		}

		// This strategy should always work.
		return 0;
	}

	/**
	 * Handles the minimum difference allowed for the invoice to be synced.
	 *
	 * Given a Contasimple invoice array that has a total amount and/or tax amount only differing from the original order
	 * amounts by the minimum allowed amount (ex: 1 cent), this method fixes the problem by redistributing that amount to one of
	 * the existing lines, so that both total amounts are exact before sending the invoice.
	 *
	 * @param array $cs_invoice       An array containing the Contasimple invoice data that is being validated.
	 * @param float $totalWithoutVat  The total amount without taxes calculated by Contasimple algorithm from the order lines.
	 * @param float $totalTaxes       The total tax amount calculated by Contasimple algorithm from the order lines.
	 * @param float $order_subtotal   The total amount without taxes as stored by the CMS.
	 * @param float $order_tax_total  The total tax amount as stored by the CMS.
	 *
	 * @return int @see validate_invoice_amounts()
	 */
	protected function handleAllowedDifferenceViaRedistribution(&$cs_invoice, $totalWithoutVat, $totalTaxes, $order_subtotal, $order_tax_total ) {

		$minimum = 0.01;

		$totalAmountDifference = round( $order_subtotal - $totalWithoutVat, CS_MAX_DECIMALS, PHP_ROUND_HALF_UP );
		$totalVATAmountDifference = round( $order_tax_total - $totalTaxes, CS_MAX_DECIMALS, PHP_ROUND_HALF_UP );
		$notesMsg = '';

		$linesSorted = $cs_invoice['lines'];

		// Sort items with highest price first.
		uasort( $linesSorted, function( $a, $b ) {
			return $b['totalTaxableAmount'] - $a['totalTaxableAmount'];
		});

		if ( abs( $totalAmountDifference ) >= $minimum ) {
			$this->logger->log( 'Order total computed by CS differs slightly from WC, sync OK but with WARNING.' );
			$foundTaxableAmountCandidate = false;
			$conceptBase = "";

			// Look where we can place the mismatching (ie: 1 cent amount), it has to revalidate the original validation formula.
			foreach ( $linesSorted as $key => $line ) {
				$qty_per_unit_price_remainder = abs( $line['quantity'] * $line['unitAmount'] * ( 1 - $line['discountPercentage'] / 100 ) - ( $line['totalTaxableAmount'] + $totalAmountDifference ) );
				$qty_per_unit_price_remainder = round( $qty_per_unit_price_remainder, CS_MAX_DECIMALS, PHP_ROUND_HALF_UP );

				if ( $qty_per_unit_price_remainder <= 0 + CS_ROUNDING_THRESHOLD ) {
					$foundTaxableAmountCandidate = true;
					$conceptBase = $line['concept'];
					$cs_invoice['lines'][$key]['totalTaxableAmount'] = $line['totalTaxableAmount'] + $totalAmountDifference;

					if ( $cs_invoice['lines'][$key]['quantity'] == 1 ) {
						// Also modify the unit amount, otherwise the end user will see something like 40 x 1 = 40.01
						// 20 x 2 = 40.01 is weird as well but at least could be justified by representing 20.004.. as 20
						// which would give 40.008 rounded as 40.01
						$cs_invoice['lines'][$key]['unitAmount'] = $line['unitAmount'] + $totalAmountDifference;
					}

					$this->logger->log( "Line #[" . $key . "]: Added " . $totalAmountDifference . " amount to account for rounding issues in total taxable amount." );
					break;
				}
			}

			if ( $foundTaxableAmountCandidate == false ) {
				// If there was taxable amount mismatch and we could not fit it at any line, we can already return as failed.
				return -1;
			}

			$notesMsg .= ' ' . sprintf( __( 'A difference of %1$.2f has been added to concept \'%2$s\' to balance the total of the invoice base amount due to rounding differences between Contasimple and WooCommerce.', 'contasimple' ), $totalAmountDifference, $conceptBase );
		}

		if ( abs( $totalVATAmountDifference ) >= $minimum ) {
			$this->logger->log( 'Total VAT computed by CS differs slightly from WC, sync OK but with WARNING.' );
			$foundVatAmountCandidate = false;
			$conceptVat = "";

			foreach ( $linesSorted as $key => $line ) {
				$vat_computation_remainder = abs( $line['totalTaxableAmount'] * ( $line['vatPercentage'] + $line['rePercentage'] ) / 100 - ( $line['vatAmount'] + $totalVATAmountDifference ) - $line['reAmount'] );
				$vat_computation_remainder = round( $vat_computation_remainder, CS_MAX_DECIMALS, PHP_ROUND_HALF_UP );

				if ( $vat_computation_remainder <= 0 + CS_ROUNDING_THRESHOLD ) {
					$foundVatAmountCandidate = true;
					$conceptVat = $line['concept'];
					$cs_invoice['lines'][$key]['vatAmount'] = $line['vatAmount'] + $totalVATAmountDifference;

					$this->logger->log( "Line #[" . $key . "]: Added " . $totalVATAmountDifference . " amount to account for rounding issues in total vat amount." );
					break;
				}
			}

			if ( $foundVatAmountCandidate == false ) {
				// If there was VAT amount mismatch and we could not fit it at any line, also return as failed.
				return -1;
			}

			$notesMsg .= ' ' . sprintf( __( 'A difference of %1$.2f has been added to concept \'%2$s\' to balance the total of the invoice VAT amount due to rounding differences between Contasimple and WooCommerce.', 'contasimple' ), $totalVATAmountDifference, $conceptVat );
		}

		$cs_invoice['notes'] .= $notesMsg;

		return 0;
	}

	/**
	 * Returns the difference between the rounded line and the original line
	 *
	 * @deprecated as of 1.4.2 > We are using the triple strategy now.
	 *
	 * @param $original_line
	 * @param $line_rounded
	 *
	 * @return number
	 */
	public function get_accumulated_rounding_error( $original_line, $line_rounded ) {

		$line_error = abs(
			    ( $original_line['unitAmount'] * $original_line['quantity'] + $original_line['vatAmount'] )
			  - ( $line_rounded['unitAmount'] * $line_rounded['quantity'] + $line_rounded['vatAmount'] )
		);

		return $line_error;
	}

    /**
     * Gets the IRPF total amount for the current order
     *
     * @param $order
     *
     * @return mixed
     */
    public function get_irpf_total( $order ) {

        $req = $order->get_data()['tax_lines'];

        $vatIrpfAmount = 0;

        foreach ($req as $tx_line){
            $lbl_tax = $tx_line['label'];
            $lbl_lower = strtolower($lbl_tax);
            $rate_id = $tx_line['rate_id'];


            if ( strpos($lbl_lower,'irpf') === 0 || strpos($lbl_lower,'i.r.p.f') === 0){
                $vatIrpfAmount += $tx_line['tax_total'];
            }
        }


        return $vatIrpfAmount;
    }

    public function get_irpf_shipping( $shipping_taxes ) {

	    $vatIrpfAmount = 0;

	    if ( isset( $shipping_taxes ) ) {

		    foreach ( array_shift( $shipping_taxes ) as $key => $tx_line ) {
			    $rate_id = $key;
			    $lbl_tax = WC_Tax::_get_tax_rate( $rate_id )['tax_rate_name'];
			    $lbl_lower = strtolower($lbl_tax);

			    if ( strpos($lbl_lower,'irpf') === 0 || strpos($lbl_lower,'i.r.p.f') === 0){
				    $vatIrpfAmount += $tx_line;
			    }
		    }
	    }

	    return $vatIrpfAmount;
    }

    /**
     * Gets the IRPF percentage for the current order
     *
     * @param $order
     *
     * @return mixed
     */
    public function get_irpf_percentage($order) {

	    $vatIrpfPercentage = 0;

        $req = $order->get_data()['tax_lines'];

        foreach ($req as $tx_line){
            $lbl_tax = $tx_line['label'];
            $lbl_lower = strtolower($lbl_tax);
            $rate_id = $tx_line['rate_id'];


            if ( strpos($lbl_lower,'irpf') === 0 || strpos($lbl_lower,'i.r.p.f') === 0){
                $vatIrpfPercentage = WC_Tax::_get_tax_rate( $rate_id )['tax_rate'];
            }
        }
        return round( $vatIrpfPercentage, CS_MAX_DECIMALS, PHP_ROUND_HALF_UP );
    }

    public static function send_error_sync_mail( $contasimple_invoice, $sync_result )
    {
        // We need to force the mailer init so that it can register the hook 'woocommerce_' for our custom invoice email, it does not load by default.
        WC()->mailer();

        $res = apply_filters( 'woocommerce_cs_invoice_error_sync', $contasimple_invoice, $sync_result, 2 );

        return $res;
    }

	/**
	 * Checks that a new invoice can be added to the sync pool
	 *
	 * @param $order_id
	 * @param $order_invoices
	 * @param $args
	 * @return bool
	 *
	 * @since 1.7.0
	 */
	private function invoice_can_be_added( $order_id, $order_invoices, $args ) {

		$isRefund = !empty( $args );
		$order = Contasimple_WC_Backward_Compatibility::get_order_from_id( $order_id );

		$total_invoiced = 0;
		$total_refunded = 0;

		foreach ( $order_invoices as $order_invoice ) {

			if ( $order_invoice->amount > 0 ) {
				$total_invoiced += $order_invoice->amount;
			} else {
				$total_refunded += $order_invoice->amount;
			}
		}

		$canBeAdded = ( !$isRefund && $order->get_total() > $total_invoiced ) ||
			          ( $isRefund && $order->get_total_refunded() > $total_refunded ) ||
				      ( $isRefund && $args['amount'] == 0);

		return $canBeAdded;
	}

	/**
	 * Checks that a new invoice can be added to the sync pool (during adding from previous).
	 *
	 * When the order status (completed or refunded) hook is triggered, we need to create an invoice and send it to CS.
	 * However, there is a problem that the admin could keep changing the order status forever and that would trigger
	 * a new invoice generation, causing duplicates.
	 *
	 * This method will check if the WP hook is eligible to add the new invoice or
	 * if we should just resume the queue to sync already marked invoices, as any change to the original invoice will
	 * be automatically accounted for later in the sync process (attention: this only works with invoices not synced OK yet).
	 *
	 * This might change in the future, but for the time being we decided that an invoice can be synced if:
	 * 1) Is the first one with a positive value (a regular invoice)
	 * 2) We are coming from the refund hook, but already exists an invoice (otherwise it does not make sense to create
	 *    an order slip if we have not officially invoiced the order, this might happen if status 'Refunded' is picked
	 *    before even picking the 'Completed' status.
	 *
	 * @param $order_invoices  An array of (already existing) Contasimple_Invoice_Sync entities.
	 * @param $isRefund        Indicates if we are coming from a refund (true) or an invoice (false)
	 * @since 1.4.0
	 *
	 * @return bool
	 */
    private function invoice_can_be_added_from_previous( $order_invoices, $isRefund ) {

	    $canBeAdded = count( $order_invoices ) == 0 && ! $isRefund ||
	                  count( $order_invoices ) == 1 && (float) $order_invoices[0]->amount > 0 && $isRefund;

	    return $canBeAdded;
    }

	/**
	 * Determine if we should try to transfer the discount coupons as CS discounts (or additional lines),
	 * or if we should rather transfer the already discounted line prices.
	 *
	 * Reasons why we might want to skip dealing with the discounts:
	 * - WooCommerce version lower than 3.2 makes it difficult due to missing API (WC_Discounts)
	 * - Introduces rounding issues difficult to deal with.
	 *
	 * @param int $attemtps Pass down the current sync attempt. We only use discounts during the 1st attempt.
	 * @return bool True if we should transfer the discounts, false otherwise.
	 */
	private function can_compute_discounts_separately($attemtps = 1 ) {

	    if ( $attemtps > 1 || version_compare( WC_VERSION, '3.2', '<' ) ) {
	        return false;
	    } else {
	        return true;
	    }
	}

	/**
	 * Gets the private notes text that will be added to the Contasimple invoice.
	 *
	 * Use this to transfer any details that will be lost from the WooCommerce context and might be of interest to
	 * the shop owner.
	 *
	 * @param $order_id
	 * @param $sign
	 * @param $nif
	 *
	 * @return string
	 */
	public function get_invoice_notes( $order_id, $sign, $nif ) {

		if ( $sign < 0 ) {
			$invoice_type = __( 'Order refund', 'contasimple');
		} else {
			$invoice_type = __( 'Order', 'contasimple');
		}

		$message = sprintf(
			__( '%1$s synchronized from WooCommerce (order #%2$d)', 'contasimple' ),
			$invoice_type,
			$order_id
		);

		$order = Contasimple_WC_Backward_Compatibility::get_order_from_id( $order_id );
		$customer_country = Contasimple_WC_Backward_Compatibility::get_billing_country( $order );
		$coupon_codes = Contasimple_WC_Backward_Compatibility::get_order_used_coupon_codes( $order );

		foreach ( $coupon_codes as $coupon_code ) {
			$message .= CS_EOL . __( 'Applied discount coupon', 'contasimple') . ": " . $coupon_code;
		}

		$nif_invalid = empty( $nif ) || !$this->is_valid_nif( $nif, $customer_country );

		if ( $nif_invalid ) {
			$customer = Contasimple_WC_Backward_Compatibility::get_customer_full_name( $order );

			if ( !empty( $customer ) || !empty( $nif ) ) {
				$message .= CS_EOL . sprintf(
					__( 'Customer name and NIF (if available): %1$s %2$s', 'contasimple' ),
					$customer,
					empty( $nif ) ? '' : '(' . $nif . ')'
				) . CS_EOL;
			}
		}

		return $message;
	}

	/**
	 * Gets the Contasimple company identifier name.
	 *
	 * @return string A string value. Example: 'NIF' for Spain.
	 *                Returns an empty string if the country is not found.
	 */
	public function get_company_identifier_name() {

		$config_company_identifier_name = '';

		// Look for the fiscal region ID number name (ie: NIF for Spain)
		foreach ( $this->get_cached_cs_countries() as $country ) {
			if ( $country->getIsoCodeAlpha2() == $this->get_config()->getCountryISOCode() ) {
				$regions = $country->getFiscalRegions();
				if ( count( $regions ) > 0 ) {
					$config_company_identifier_name = strval($regions[0]['company_identifier_name']);
				}
				break;
			}
		}

		return $config_company_identifier_name;

	}

	public static function cs_number_format( $number, $max_decimals, $decimal_separator, $thousands_separator, $currency_symbol, $currency_placing_mask, $accounting ) {

		// Get rid of trailing zeroes.
		$number = (float)$number;

		// Determine how many relevant decimals do we have.
		$decimals = ( (int) $number != $number ) ? (strlen($number) - strpos($number, '.')) - 1 : 0;

		if ( $decimals < $max_decimals ) {
			$max_decimals = $decimals;
		}

		// Build the base localized number, ex: 1.234,567000
		$formatted_number = number_format( $number, $max_decimals, $decimal_separator, $thousands_separator );

		// Place the currency where it should based on the mask. Ex: 1.234,56 €
		$formatted_number = str_replace( '%1', $formatted_number, $currency_placing_mask );
		$formatted_number = str_replace( '%2', $currency_symbol, $formatted_number );

		// If the locale wants accounting style, remove the - and add the ()
		if ( $accounting && $number < 0) {
			$formatted_number = str_replace( '-', '', $formatted_number );
			$formatted_number = "(" . $formatted_number . ")";
		}

		return $formatted_number;
	}

	public function concurrency_control_enabled() {
		$wc_options = get_option( 'woocommerce_integration-contasimple_settings' );

		// Only disable this feature if explicitly set in the settings.
		if ( !empty( $wc_options ) && isset( $wc_options['enable_mutex'] ) && 'no' === $wc_options['enable_mutex'] ) {
			$enabled = false;
		} else {
			$enabled = true;
		}

		return $enabled;
	}
}
