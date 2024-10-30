<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://www.contasimple.com
 * @since      1.0.0
 *
 * @package    contasimple
 * @subpackage contasimple/includes
 * @author     Contasimple S.L. <soporte@contasimple.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * The core plugin class.
 *
 * Used to load dependencies, define internationalization, set admin-specific hooks and
 * public-facing site hooks.
 *
 * It also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 */
class Contasimple {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Contasimple_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	protected $logger;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	public static $plugin_name = 'contasimple';

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	public static $version = '1.30.0';

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->load_dependencies();
		$this->register_logger();
		$this->set_locale();
		$this->track_fatal_errors();
		$this->load_wc_integration();
		$this->load_custom_types();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include, amongst others, the following files that make up the plugin:
	 *
	 * - Plugin_Name_Loader. Orchestrates the hooks of the plugin.
	 * - Plugin_Name_i18n. Defines internationalization functionality.
	 * - Plugin_Name_Admin. Defines all hooks for the admin area.
	 * - Plugin_Name_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * Needed in some cases for backward compatibility. Tested up to WP 3.8 & WC 2.1
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/wp-backward-compatibility.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-contasimple-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-contasimple-i18N.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-contasimple-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-contasimple-public.php';

		/**
		 * The class responsible for rendering admin messages as WP notices
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-contasimple-notice.php';

		/**
		 * The class responsible for creating tables and stuff during activation
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-contasimple-install.php';

		/**
		 * The class responsible for saving/retrieving the invoice sync data from the DB
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-contasimple-invoice-sync.php';

		/**
		 * The class responsible for registering custom post types (invoice)
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-contasimple-post-types.php';

		/**
		 * The class responsible for loading the custom WP List Table to create invoices from previous orders
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-contasimple-wp-list-table-wc-orders.php';

		/**
		 * This class contains some general purpose helper methods.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-contasimple-wc-helpers.php';

		/**
		 * Imports Swagger Codegen API wrappers
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'autoload.php';

		/**
		 * Common to all Contasimple eCommerce plugins, logs errors for reporting
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/common/CSLogger.php';

		/**
		 * Common to all Contasimple eCommerce plugins, facade for handy usage of the API wrappers with session handling
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/common/CSService.php';

		/**
		 * Common to all Contasimple eCommerce plugins, extends Swagger Codegen API Config class with domain-specific configuration properties
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/common/CSConfig.php';

		/**
		 * Common to all Contasimple eCommerce plugins, defines an interface for storing CS configuration.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/common/CSConfigManager.php';

		/**
		 * Control concurrency
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/common/web_mutex.php';

		/**
		 * This is WC implementation of previously defined CSConfigManager interface.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-contasimple-wc-config-manager.php';

		$this->loader = new Contasimple_Loader();
	}

	/**
	 * Register custom logger on plugins loaded
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function register_logger() {

		add_action( 'plugins_loaded', array( $this, 'register_contasimple_logger') );
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Contasimple_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {

		$plugin_i18n = new Contasimple_i18n();

		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Load integration with WooCommerce
	 *
	 * Uses the WC_Integration_Contasimple class to load this plugin as a WC Integration and registers
	 * the hook with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_wc_integration() {

		add_action( 'plugins_loaded', array( $this, 'init_integration' ) );
		add_filter( 'woocommerce_email_classes', array( $this, 'add_cs_invoiced_order_woocommerce_email' ) );
		add_action( 'before_woocommerce_init', function() {
			if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
				\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility(
					'custom_order_tables',
					'contasimple/contasimple.php',
					true
				);
			}
		} );
	}

	/**
	 * Register a WP Custom Post Type (CPT) for CS Invoices
	 *
	 * A CPT will be used to show a list of synced invoices based on WP own API to extend native post types.
	 * See class-contasimple-post-types.php to see how it is defined and related helper functions.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_custom_types() {

		$contasimple_cpt = new Contasimple_CPT();

		add_action( 'admin_init', array( $contasimple_cpt, 'init' ) );
		add_action( 'init', array( $contasimple_cpt, 'register' ) );
	}

	/**
	 * Register hook for shutdown
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function track_fatal_errors() {

		add_action( 'shutdown', array( $this, 'contasimple_shutdown_handler' ) );

	}
	/**
	 * Init integration with WooCommerce
	 *
	 * Makes this plugin work not only as a WP plugin, but a WC plugin as well.
	 * Checks that WC is installed before loading and controls that the CS plugin is already configured. If it is not,
	 * it will present the configuration wizard on 'WooCommerce > Integrations > Contasimple' tab. If it already is, will
	 * show advanced plugin settings.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function init_integration() {

		// Allows us to catch PHP critical errors and send them to our custom Logging file what users can send to CS for customer support.
		// add_action( 'shutdown', array( $this, 'contasimple_shutdown_handler' ) );

		// Checks if WooCommerce is installed.
		if ( class_exists( 'WC_Integration' ) ) {

			include_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-contasimple-wc-integration.php';

			// Register the integration.
			add_filter( 'woocommerce_integrations', array( $this, 'add_integration' ) );

			if ( $this->is_a_contasimple_section() ) {
				$this->define_common_admin_hooks();
			}

			if ( isset( $_SERVER['QUERY_STRING'] ) ) {
				$the_query = esc_url( $_SERVER['QUERY_STRING'] ); // WPCS: input var okay; sanitization okay.
			} else {
				$the_query = '';
			}

			$contasimple_admin = Contasimple_Admin::getInstance();

			// If contasimple plugin is not fully configured we lock the user in the configuration wizard screen.
			if ( ! $contasimple_admin->properly_configured() ) {

				$this->define_pre_config_admin_hooks();

				$msg_error = __( 'Contasimple plugin needs to be configured before being used. You can do so by clicking', 'contasimple' );
				$msg_here  = __( 'here', 'contasimple' );

				$target_query_string = 'page=wc-settings&tab=integration&section=integration-contasimple';
				$target_url          = admin_url( 'admin.php?' . $target_query_string );

				if ( strpos( $the_query, 'section=integration-contasimple' ) == false ) {
					new Contasimple_Notice( sprintf( '%1$s <a href="%3$s">%2$s</a>.', $msg_error, $msg_here, $target_url ), 'error' );
				}
			} else {

				// So the plugin is properly configured and we have all company data needed to start syncing.
				// Define needed hooks to display invoices and to capture WooCommerce order complete hooks where we will want to trigger the sync process.
				$this->define_post_config_admin_hooks();
				$this->define_public_hooks();
			}

			// Warn about missing PHP PECL intl module, needed currency formatting in CS line detailed description.
			if ( strpos( $the_query, 'section=integration-contasimple' ) !== false || strpos( $the_query, 'tab=integration' ) !== false ) {
				if ( !extension_loaded("intl" ) ) {
					new Contasimple_Notice( __( 'The PHP PECL Intl extension is not enable in this system. Contasimple uses this extension to format the invoice amounts according to the format configured in Contasimple. We recommend that you install this extension on your server. Otherwise, it is possible that some invoices that group concepts by tax rate do not show all the amounts formatted according to your settings in Contasimple.', 'contasimple' ), 'warning' );
				}
			}

			$this->run();

		} else {

			new Contasimple_Notice( __( 'WooCommerce not found!', 'contasimple' ), 'error' );

		}
	}

	/**
	 * Add a new integration to WooCommerce.
	 *
	 * Called by a filter, gets current WC integrations and injects our own.
	 *
	 * @since    1.0.0
	 * @access   public
	 *
	 * @param array $integrations   The integrations set by other plugins.
	 * @return array                The array expanded with our own integration.
	 */
	public function add_integration( $integrations ) {

		$integrations[] = 'WC_Integration_Contasimple';

		return $integrations;
	}

	/**
	 * Register all the hooks related to the admin area functionality
	 * of the plugin that are not dependant of proper configuration steps.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_common_admin_hooks() {

		$plugin_admin = Contasimple_Admin::getInstance();

		$this->loader->add_action( 'wp_ajax_check_for_log', $plugin_admin, 'check_for_log' );
		$this->loader->add_action( 'wp_ajax_create_new_series', $plugin_admin, 'create_new_series' );
		$this->loader->add_action( 'wp_ajax_cs_login', $plugin_admin, 'cs_login' );
		$this->loader->add_action( 'wp_ajax_cs_select_sync_order_status', $plugin_admin, 'cs_select_sync_order_status' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles', PHP_INT_MAX );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts', PHP_INT_MAX );
		$this->loader->add_action( 'clean_url', $plugin_admin, 'add_async_to_gtag');
	}

	/**
	 * Register all the hooks related to the admin area functionality
	 * of the plugin that will be needed BEFORE the plugin is properly configured.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_pre_config_admin_hooks() {

		$plugin_admin = Contasimple_Admin::getInstance();

		$this->loader->add_action( 'wp_ajax_cs_select_company', $plugin_admin, 'cs_select_company' );
		$this->loader->add_action( 'wp_ajax_cs_select_payment_methods', $plugin_admin, 'cs_select_payment_methods' );
		$this->loader->add_action( 'wp_ajax_cs_select_numbering_series', $plugin_admin, 'cs_select_numbering_series' );
	}

	/**
	 * Register all the hooks related to the admin area functionality
	 * of the plugin that will be needed AFTER the plugin is properly configured.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_post_config_admin_hooks() {

		$plugin_admin = Contasimple_Admin::getInstance();

		// Handle order state change related stuff, because this is when we want to trigger the sync process.
		$this->loader->add_action( 'woocommerce_order_status_completed', $plugin_admin, 'order_status_completed' );
		$this->loader->add_action( 'woocommerce_order_status_processing', $plugin_admin, 'order_status_processing' );
		$this->loader->add_action( 'woocommerce_order_status_on-hold', $plugin_admin, 'order_status_on_hold' );
		//$this->loader->add_action( 'woocommerce_payment_complete ', $plugin_admin, 'payment_complete' );
		$this->loader->add_action( 'woocommerce_refund_created', $plugin_admin, 'refund_created', 10, 2 );
		$this->loader->add_action( 'woocommerce_order_status_changed', $plugin_admin, 'order_status_changed', 10, 3 );
		$this->loader->add_action( 'woocommerce_admin_order_data_after_billing_address', $plugin_admin, 'add_custom_field_nif_checkout_edit_order' );

		// Register AJAX calls for custom action buttons.
		$this->loader->add_action( 'wp_ajax_cs_pdf', $plugin_admin, 'cs_pdf' );
		$this->loader->add_action( 'wp_ajax_cs_email', $plugin_admin, 'cs_email' );
		$this->loader->add_action( 'wp_ajax_cs_stop', $plugin_admin, 'cs_stop' );
		$this->loader->add_action( 'wp_ajax_cs_sync', $plugin_admin, 'cs_sync' );

		// Request CS PDF invoice and attach to email
		$this->loader->add_action( 'woocommerce_email_attachments', $plugin_admin, 'attach_cs_invoice', 10, 4 );

		// Other additions
		$this->loader->add_action( 'init', $plugin_admin, 'set_screen_options' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'contasimple_add_previous_woocommerce_orders' );
		$this->loader->add_action( 'submenu_file', $plugin_admin, 'contasimple_admin_submenu_filter' );

		// See custom post type class for remaining hook actions that are more CPT-centric.
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {

		$plugin_public = new Contasimple_Public( Contasimple::$plugin_name, Contasimple::$version );

		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_styles' );
		$this->loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'enqueue_scripts' );
		$this->loader->add_action( 'woocommerce_after_checkout_billing_form', $plugin_public, 'add_custom_field_nif_checkout' );
		$this->loader->add_action( 'woocommerce_checkout_update_order_meta', $plugin_public, 'add_custom_field_nif_checkout_update_order' );
		$this->loader->add_action( 'woocommerce_checkout_update_user_meta', $plugin_public, 'add_custom_field_nif_checkout_update_user' );
		$this->loader->add_action( 'woocommerce_checkout_process', $plugin_public, 'validate_valid_nif' );
	}

	/**
	 * Check if we are in the right place on the admin side to load custom hooks
	 *
	 * @since    1.0.0
	 * @access   public
	 * @return   bool
	 */
	public function is_a_contasimple_section() {

		global $pagenow;

		$page      = filter_input( INPUT_GET, 'page' );
		$post_type = filter_input( INPUT_GET, 'post_type' );

		return ( 'admin-ajax.php' === $pagenow ) || ('admin.php' === $pagenow && ( 'wc-settings' === $page || 'create_cs_invoices' === $page ) ) || ( 'edit.php' === $pagenow && 'cs_invoice' === $post_type );
	}

	/**
	 * Execution termination handler for fatal errors
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function contasimple_shutdown_handler() {

		if ( function_exists( 'error_get_last' ) ) {

			$e = error_get_last();

			if ( !is_null( $e ) ) {
				// Errors with the mutex locking file are expected, do not output them in the log file.
				if (strpos( $e['message'], 'class-contasimple-admin.php.lock' ) === false ) {
					if ( !is_null( $this->logger ) )
						$this->logger->log( '[' . $this->friendly_error_type($e['type']) . ']: ' . $e['message'] . ' on file: ' . $e['file'] . ' at line: ' . $e['line'] );
				}
			}
		}
	}

	private function friendly_error_type( $type ) {

		switch( $type ) {

			case E_ERROR: // 1 //
				return 'E_ERROR';

			case E_WARNING: // 2 //
				return 'E_WARNING';

			case E_PARSE: // 4 //
				return 'E_PARSE';

			case E_NOTICE: // 8 //
				return 'E_NOTICE';

			case E_CORE_ERROR: // 16 //
				return 'E_CORE_ERROR';

			case E_CORE_WARNING: // 32 //
				return 'E_CORE_WARNING';

			case E_COMPILE_ERROR: // 64 //
				return 'E_COMPILE_ERROR';

			case E_COMPILE_WARNING: // 128 //
				return 'E_COMPILE_WARNING';

			case E_USER_ERROR: // 256 //
				return 'E_USER_ERROR';

			case E_USER_WARNING: // 512 //
				return 'E_USER_WARNING';

			case E_USER_NOTICE: // 1024 //
				return 'E_USER_NOTICE';

			case E_STRICT: // 2048 //
				return 'E_STRICT';

			case E_RECOVERABLE_ERROR: // 4096 //
				return 'E_RECOVERABLE_ERROR';

			case E_DEPRECATED: // 8192 //
				return 'E_DEPRECATED';

			case E_USER_DEPRECATED: // 16384 //
				return 'E_USER_DEPRECATED';
		}

		return "";
	}

	/**
	 * Load CS errors file logger with init parameters.
	 *
	 * First use of the logger class also initializes it depending on WP_Options settings. From there onwards,
	 * we can simplify the usage by just doing CSLogger::getDailyLogger() because we get an already existing
	 * logger via the Singleton pattern. There's debate on whether this is a good approach or not
	 * (https://stackoverflow.com/questions/4595964/is-there-a-use-case-for-singletons-with-database-access-in-php/4596323#4596323),
	 * but WP complicates the scenario so we decided to go this way.
	 *
	 * @since    1.0.0
	 * @access   public
	 */
	public function register_contasimple_logger() {

		global $wp_version, $woocommerce;

		$logs_dir = CS_PLUGIN_PATH . 'logs' . DIRECTORY_SEPARATOR;
		$plugin_version = Contasimple::$version;

		if ( isset( $woocommerce ) ) {
			$cms_version = sprintf( "WordPress %s + WooCommerce %s", $wp_version, $woocommerce->version ) ;
		} else {
			$cms_version = sprintf( "WordPress %s + WooCommerce %s", $wp_version, 'disabled' ) ;
		}

		// If no setting available, assume logs must be enabled.
		if ( false == get_option( 'woocommerce_integration-contasimple_settings' ) || ! array_key_exists( 'enable_logs', get_option( 'woocommerce_integration-contasimple_settings' ) ) ) {
			$enabled = true;
		} elseif ( 'yes' === get_option( 'woocommerce_integration-contasimple_settings' )['enable_logs'] ) {
			$enabled = true;
		} else {
			$enabled = false;
		}

		$this->logger = CSLogger::getDailyLogger( $enabled, $plugin_version, $cms_version, $logs_dir );
	}

	/**
	 * Add a custom email to the list of emails WooCommerce should load
	 *
	 * @param array $email_classes available email classes.
	 * @return array filtered available email classes
	 *
	 * @since 1.0.0
	 */
	public function add_cs_invoiced_order_woocommerce_email( $email_classes ) {

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-contasimple-wc-invoiced-order-email.php';

		// Add the email class to the list of email classes that WooCommerce loads.
		$email_classes['Contasimple_WC_Invoiced_Order_Email'] = new Contasimple_WC_Invoiced_Order_Email();

		//Add Invoice Sync Error class to the list of email classes that WooCommerce loads.
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-contasimple-wc-invoice-sync-error.php';
        $email_classes['Contasimple_WC_Invoiced_Sync_Error'] = new Contasimple_WC_Invoiced_Sync_Error();
		return $email_classes;
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @access   public
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Contasimple_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}
}
