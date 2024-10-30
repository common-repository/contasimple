<?php
/**
 * Contasimple Integration for WooCommerce
 *
 * @link       http://www.contasimple.com
 * @since      1.0.0
 *
 * @package    contasimple
 * @category   contasimple/includes
 * @author     Contasimple S.L. <soporte@contasimple.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

if ( ! class_exists( 'WC_Integration_Contasimple' ) ) :
	/**
	 * Contasimple Integration Class
	 *
	 * @since    1.0.0
	 */
	class WC_Integration_Contasimple extends WC_Integration {

		/**
		 * A reference to the Contasimple_Admin class that defines a few methods needed for checks.
		 *
		 * @since    1.0.0
		 *
		 * @var $contasimple_admin
		 */
		private $contasimple_admin;

		/**
		 * Init and hook-in the integration.
		 *
		 * @since    1.0.0
		 */
		public function __construct() {

			global $pagenow;

			$this->id                 = 'integration-contasimple';
			$this->method_title       = __( 'Contasimple', 'contasimple' );
			$this->method_description = __( 'Welcome to Contasimple.', 'contasimple' );

			$this->contasimple_admin = Contasimple_Admin::getInstance();

			// Custom handling of post actions.
			$this->post_process();

			// Only try to add the integration forms if we are under the desired section since we try to connect with the API
			// to validate the access and would be very unefficient to do this on very constructor call.
			if ( ( 'admin.php' === $pagenow && isset( $_GET['page'] ) && ('wc-settings' === $_GET['page'] || 'create_cs_invoices' === $_GET['page'] ) )
			     || ( 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && 'cs_invoice' === $_GET['post_type'] ) ) {

				// Load the settings, depending on if configuration is needed or not.
				if ( ! $this->contasimple_admin->properly_configured() ) {
					$this->init_form_wizard_fields();
					if ( ( 'edit.php' === $pagenow && isset( $_GET['post_type'] ) && 'cs_invoice' === $_GET['post_type'] ) ) {
						wp_redirect( admin_url() );
						exit();
					}
				} else {
					try {
						$company = $this->contasimple_admin->get_service()->getCurrentCompany();

						if ( ! empty( $company ) ) {
							$this->init_after_wizard_form( $company );
						} else {
							throw new \Exception( "Could not load CS company data.", CANNOT_READ_COMPANY_DATA );
						}
					} catch ( \Exception $e ) {
						$this->handle_exception( $e );
					}
				}
			}
		}

		/**
		 * Initialize integration settings form fields.
		 *
		 * These are only needed in the wizard, will let us pick payment methods to tie to Contasimple counterparts.
		 *
		 * @since    1.0.0
		 */
		public function init_form_wizard_fields() {

			$enabled_payment_gateways = array();
			$payment_gateways_obj     = new WC_Payment_Gateways();

			foreach ( $payment_gateways_obj->payment_gateways() as $gateway ) {
				$enabled_payment_gateways[] = array(
					'name'        => $gateway->id,
					'displayName' => $gateway->title,
				);
			}

			// Add an additional one that will be picked if something goes wrong or if new methods are added in the future.
			$enabled_payment_gateways[] = array(
				'name'        => 'default',
				'displayName' => __( 'Default method', 'contasimple' ),
			);

			$this->form_fields = array(
				'wizard'    => array(
					'title'             => __( 'Configuration Wizard', 'contasimple' ),
					'custom_attributes' => array(
						'paymentModulesList' => $enabled_payment_gateways
					),
					'type'              => 'modal',
				),
                'enable_logs'           => array(
                    'title'       => __( 'Enable Logs', 'contasimple' ),
                    'type'        => 'checkbox',
                    'label'       => __( 'Enable logging', 'contasimple' ),
                    'default'     => 'yes',
                    'description' => __( 'Contasimple error logs are needed in order to be able to help you in case you might have issues with the plugin. It is recommended to keep them enabled.', 'contasimple' ),
                ),
				'enable_mutex'    => array(
					'title'       => __( 'Concurrency control', 'contasimple' ),
					'type'        => 'checkbox',
					'label'       => __( 'Enable concurrency protection', 'contasimple' ),
					'default'     => 'yes',
					'description' => __( 'By default, Contasimple will implement a locking mechanism to ensure that invoices never are created duplicated in Contasimple under rare cases where an order might get marked as Completed twice in a very short time frame. This mechanism might cause problems in some conditions. If you are experiencing freezing of the site and new invoices are not being synced when an order is completed, you can disable this feature and see if the problem disappears.', 'contasimple' ),
				),
                'log_files_date'        => array(
                    'title' => __( 'Log files', 'contasimple' ),
                    'type'  => 'text',
                    'class' => 'datepicker',
                    'label' => __( 'Log files', 'contasimple' ),
                ),
                'log_files_button'      => array(
                    'title'       => __( 'Download', 'contasimple' ),
                    'type'        => 'button',
                    'id'          => 'logDownloadButton',
                    'class'       => 'button-primary',
                    'css'         => 'margin-top:8px',
                    'description' => __( 'Select the date for which you want to download the log file and click the \'Download\' button. This file can be sent to Contasimple\'s customer service to help you trooubleshoot any issues with the module .', 'contasimple' ) . __( 'Then, please contact us first by filling the form in the \'contact area\' in our website and we will provide you with further instructions about how to send us the logs.', 'contasimple' ),
                ),
				'version'   => array(
					'title' => __( 'Module version', 'contasimple' ),
					'type'  => 'label',
					'css'   => '',
					'text'  => Contasimple::$version,
				),
			);
		}

		/**
		 * Initialize integration settings form fields.
		 *
		 * These will be needed after the wizard is completed.
		 */
		public function init_form_fields( $company = null ) {

			if ( ! empty( $company ) ) {
				$nif     = '';
				$address = '';

				if ( ! empty( $company->getExtraInformation() ) ) {
					$address = $company->getExtraInformation()->getEntity()->getAddress();
					$nif = $company->getExtraInformation()->getEntity()->getNif();
				}

				$contasimple_admin = Contasimple_Admin::getInstance();
				$config_company_identifier_name = $contasimple_admin->get_company_identifier_name();

                if ( !empty( get_option( 'woocommerce_integration-contasimple_settings' ) )
                    && !empty( get_option( 'woocommerce_integration-contasimple_settings' )['nif_custom_text'])
                ) {
                    $nif_label = get_option( 'woocommerce_integration-contasimple_settings' )['nif_custom_text'];
                } elseif ( !empty( $config_company_identifier_name ) ) {
					$nif_label = sprintf( __('%s / Company identifier', 'contasimple'), $config_company_identifier_name );
				} else {
					$nif_label = __( 'Company identifier', 'contasimple' );
				}

				$default_option = ' - ' . __('Select an option', 'contasimple') . ' - ';

				$numbering_invoices_series_options = array($default_option);
				$numbering_rectifying_series_options = array($default_option);

				$cs_language = Contasimple_WC_Helpers::get_contasimple_equivalent_current_wp_locale();

				$invoiceNumberingFormatsList = $this->contasimple_admin->get_service()->getInvoiceNumberingFormats($cs_language);

				foreach ($invoiceNumberingFormatsList as $numberingFormat) {
					if ($numberingFormat->getType() == 'Normal')
						$numbering_invoices_series_options[$numberingFormat->getId()] = $numberingFormat->getName();
				}

				foreach ($invoiceNumberingFormatsList as $numberingFormat) {
					if ($numberingFormat->getType() == 'Rectifying')
						$numbering_rectifying_series_options[$numberingFormat->getId()] = $numberingFormat->getName();
				}

				$this->form_fields = array(
					'summary'               => array(
						'title'    => __( 'Summary', 'contasimple' ),
						'email'    => $this->contasimple_admin->get_config()->getUsername(),
						'company'  => $company->getOrganizationName(),
						// 'currency' => $company->getCountry()->getCurrency()->getShortName(),
						'nif'      => $nif,
						'address'  => $address,
						'type'     => 'summary',
						'desc_tip' => false,
					),
					'configuration-section' => array(
						'title' => __( 'Configuration', 'contasimple' ),
						'type'  => 'custom_heading',
						'icon'  => 'dashicons-admin-generic',
					),
					'invoices_series' => array(
						'title'       => __( 'Autonumber formatting for invoices', 'contasimple' ),
						'type'        => 'select',
						'options'     => $numbering_invoices_series_options,
						'default'     => 0,
						'description' => __( 'Select which defined series in Contasimple you want to use for the numbering sequence of your invoices.', 'contasimple' ),
					),
					'receipts_series' => array(
						'title'       => __( 'Autonumber formatting for receipts', 'contasimple' ),
						'type'        => 'select',
						'options'     => $numbering_invoices_series_options,
						'default'     => 0,
						'description' => __( 'Select which defined series in Contasimple you want to use for the numbering sequence of your receipts.', 'contasimple' ),
					),/*
					'invoices_mask'         => array(
						'title'       => __( 'Autonumber formatting for invoices', 'contasimple' ),
						'type'        => 'text',
						'default'     => __( 'WC-INV-AAAA-######', 'contasimple' ),
						'description' => __( 'Specify the format for the numbering sequence of your invoices.', 'contasimple' ) . ' ' . __( 'Example: WC-INV-AAAA-###### will turn into a sequence like WC-INV-2017-000001', 'contasimple' ),
					),
					'receipts_mask'         => array(
						'title'       => __( 'Autonumber formatting for receipts', 'contasimple' ),
						'type'        => 'text',
						'default'     => __( 'WC-TICK-AAAA-######', 'contasimple' ),
						'description' => __( 'Specify the format for the numbering sequence of your receipts.', 'contasimple' ) . ' ' . __( 'Example: WC-TICK-AAAA-###### will turn into a sequence like WC-TICK-2017-000001', 'contasimple' ),
					),*/
					'new_series'    => array(
						'title'       => __( 'Create a new series', 'contasimple' ),
						'type'        => 'create_new_series',
						'id'          => 'seriesCreateButton',
						'class'       => 'button-primary',
						'css'         => 'margin-top:8px'
					),
					'nif_required'    => array(
						'title'       => $nif_label . ' ' . __('required', 'contasimple'),
						'type'        => 'checkbox',
						'label'       => __( 'Make this field required during the checkout process.', 'contasimple' ),
						'default'     => 'no',
						'description' => __( 'If enabled, customers will have to enter a valid value in this field before being able to complete an order.', 'contasimple' ),
					),
					'nif_custom_text'         => array(
						'title'       => __( 'Custom text for the NIF field', 'contasimple' ),
						'type'        => 'text',
						'default'     => __( '', 'contasimple' ),
						'description' => __( 'You can customize the text for the NIF field during the checkout process. If you leave this field blank, Contasimple will display by default a text with the format "NIF / Company identifier" where the NIF part changes based on the fiscal region of the selected company from Contasimple, and this text will be translatable by Wordpress. If you choose to enter a custom text, this text will be displayed for all users, but won\'t use the translation system.', 'contasimple' ),
					),
					'enable_sku'         => array(
						'title'       => 'SKU',
						'type'        => 'checkbox',
						'label'       => __( 'Add product SKU to the invoice line concept', 'contasimple' ),
						'default'     => 'no',
						'description' => __( 'If enabled, products SKUs will be added to Contasimple invoice lines as a part of the line concept.', 'contasimple' ),
					),
					'wc_minimum_status_to_sync' => array(
						'title'       => __( 'Mínimum order status needed to sync invoices', 'contasimple' ),
						'type'        => 'select',
						'options'     => array(
							'on-hold'    => __( 'On-Hold', 'contasimple' ),
							'processing' => __( 'Processing', 'contasimple' ),
							'completed'  => __( 'Completed', 'contasimple' )
						),
						'default'     => 'completed',
						'description' => __( 'Select which status triggers first the order invoice generation. The default is to sync invoices when orders reach the \'Completed\' status. You can choose to sync invoices when the order status is set to \'Processing\' or \'On-hold\' instead.', 'contasimple' ) . ' ' . __( 'Note that regardless of this setting, the \'Completed\' status always creates an invoice if it has not yet been created. If you choose the \'Processing\' or \'On-hold\' status but an order status is set to \'Completed\' without being set first to processing or on-hold, the invoice will still be created. However, the reverse is not true.', 'contasimple' ),
					),
					'show_warnings'         => array(
						'title'       => __( 'Show warnings', 'contasimple' ),
						'type'        => 'checkbox',
						'label'       => __( 'Enable warnings', 'contasimple' ),
						'default'     => 'no',
						'description' => __( 'Detect if invoices that do not follow Contasimple fiscal region tax rules have been synced.', 'contasimple' ),
					),
					'enable_logs'           => array(
						'title'       => __( 'Enable Logs', 'contasimple' ),
						'type'        => 'checkbox',
						'label'       => __( 'Enable logging', 'contasimple' ),
						'default'     => 'yes',
						'description' => __( 'Contasimple error logs are needed in order to be able to help you in case you might have issues with the plugin. It is recommended to keep them enabled.', 'contasimple' ),
					),
					'enable_mutex'    => array(
						'title'       => __( 'Concurrency control', 'contasimple' ),
						'type'        => 'checkbox',
						'label'       => __( 'Enable concurrency protection', 'contasimple' ),
						'default'     => 'yes',
						'description' => __( 'By default, Contasimple will implement a locking mechanism to ensure that invoices never are created duplicated in Contasimple under rare cases where an order might get marked as Completed twice in a very short time frame. This mechanism might cause problems in some conditions. If you are experiencing freezing of the site and new invoices are not being synced when an order is completed, you can disable this feature and see if the problem disappears.', 'contasimple' ),
					),
					'log_files_date'        => array(
						'title' => __( 'Log files', 'contasimple' ),
						'type'  => 'text',
						'class' => 'datepicker',
						'label' => __( 'Log files', 'contasimple' ),
					),
					'log_files_button'      => array(
						'title'       => __( 'Download', 'contasimple' ),
						'type'        => 'button',
						'id'          => 'logDownloadButton',
						'class'       => 'button-primary',
						'css'         => 'margin-top:8px',
						'description' => __( 'Select the date for which you want to download the log file and click the \'Download\' button. This file can be sent to Contasimple\'s customer service to help you trooubleshoot any issues with the module .', 'contasimple' ) . __( 'Then, please contact us first by filling the form in the \'contact area\' in our website and we will provide you with further instructions about how to send us the logs.', 'contasimple' ),
					),
					'version'               => array(
						'title' => __( 'Module version', 'contasimple' ),
						'type'  => 'label',
						'css'   => '',
						'text'  => Contasimple::$version,
					),
				);

				// Refunds came with WC 2.2
				if ( ! version_compare( WC_VERSION, '2.2', '<' ) ) {

					/*
					$refunds_mask_section = array(
						'title'       => __( 'Autonumber formatting for refunds', 'contasimple' ),
						'type'        => 'text',
						'default'     => __( 'WC-NOTE-AAAA-######', 'contasimple' ),
						'description' => __( 'Specify the format for the numbering sequence of your refunds/credit notes.', 'contasimple' ) . ' ' . __( 'Example: WC-NOTE-AAAA-###### will turn into a sequence like WC-NOTE-2017-000001', 'contasimple' ),
					);*/

					$refunds_mask_section = array(
						'title'       => __( 'Autonumber formatting for refunds', 'contasimple' ),
						'type'        => 'select',
						'options'     => $numbering_rectifying_series_options,
						'default'     => 0,
						'description' => __( 'Select which defined series in Contasimple you want to use for the numbering sequence of your refunds.', 'contasimple' )
					);

					$this->form_fields = array_slice( $this->form_fields, 0, 3, true) + array('refunds_series' => $refunds_mask_section ) + array_slice($this->form_fields, 3, count($this->form_fields) - 1, true) ;
				}
			}
		}

		/**
		 * Driver to the global method for series validation logic.
		 * WC automatically calls this if a key exists that matches the 'validate_<key>_field()' function signature.
		 *
		 * @see validate_series_field()
		 * @since 1.16
		 */
		public function validate_invoices_series_field( $key ) {
			return $this->validate_series_field( $key );
		}

		/**
		 * Driver to the global method for series validation logic.
		 * WC automatically calls this if a key exists that matches the 'validate_<key>_field()' function signature.
		 *
		 * @see validate_series_field()
		 * @since 1.16
		 */
		public function validate_receipts_series_field ( $key ) {
			return $this->validate_series_field( $key );
		}

		/**
		 * Driver to the global method for series validation logic.
		 * WC automatically calls this if a key exists that matches the 'validate_<key>_field()' function signature.
		 *
		 * @see validate_series_field()
		 * @since 1.6
		 */
		public function validate_refunds_series_field ( $key ) {
			return $this->validate_series_field( $key );
		}

		/**
		 * Validates that a value has been selected from the corresponding dropwdown control in the settings page.
		 *
		 * @since 1.16
		 *
		 * @param string $key The key that identifies the option to validate.
		 *
		 * @return mixed The value to save, or the previous value if there is a validation error.
		 */
		protected function validate_series_field ( $key ) {

			// Get the posted value
			$value = $_POST[ $this->plugin_id . $this->id . '_' . $key ];

			// The option index must be different from the 'Select an option' placeholder.
			if ( !isset( $value ) || $value == 0 ) {

				switch ( $key ) {
					case 'receipts_series':
						$message = __( 'Please select which numbering series you wish to use for the receipts from the corresponding dropdown list.', 'contasimple' );
						break;
					case 'refunds_series':
						$message = __( 'Please select which numbering series you wish to use for the rectifying invoices from the corresponding dropdown list.', 'contasimple' );
						break;
					case 'invoices_series':
					default:
						$message = __( 'Please select which numbering series you wish to use for the invoices from the corresponding dropdown list.', 'contasimple' );
						break;
				}

				$this->errors[] = $message;

				// Pick old value from the database, otherwise WC would still save the given wrong value!
				$wc_options = get_option( 'woocommerce_integration-contasimple_settings' );

				if ( !empty( $wc_options ) && array_key_exists( $key, $wc_options ) ) {
					$value = $wc_options[$key];
				}
			}

			return $value;
		}

		/**
		 * Displays errors, if any, right after each field validation has been performed.
		 *
		 * @since 1.14
		 */
		public function sanitize_settings( $settings ) {

			if ( $this->errors ) {
				$this->display_errors();
				unset( $this->errors );
			}

			return $settings;
		}

		/**
		 * Generate Button HTML.
		 *
		 * @access public
		 * @param mixed $key the key.
		 * @param mixed $data the data.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public function generate_button_html( $key, $data ) {

			$field    = $this->plugin_id . $this->id . '_' . $key;
			$defaults = array(
				'class'             => 'button-secondary',
				'id'                => $field,
				'css'               => '',
				'custom_attributes' => array(),
				'desc_tip'          => false,
				'title'             => '',
			);

			$data = wp_parse_args( $data, $defaults );

			ob_start();
			?>

			<form id="form-cs-download-log" method="post" class="defaultForm form-horizontal" enctype="multipart/form-data">
				<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo wp_create_nonce('woocommerce-settings'); ?>">
				<input type="hidden" name="_wp_http_referer" value="<?php echo admin_url( 'admin.php?page=wc-settings&tab=integration&section=integration-contasimple'); ?>">
				<tr valign="top">
					<td></td>
					<td style="padding-top:0px">
						<fieldset>
							<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
							<?php
								echo $this->get_description_html( $data ); // XSS: sanitization okay.
							?>
							<button class="<?php echo esc_attr( $data['class'] ); ?>" type="submit" name="<?php echo empty( $data['id'] ) ? esc_attr( $field ) : esc_attr( $data['id'] ); ?>" id="<?php echo empty( $data['id'] ) ? esc_attr( $field ) : esc_attr( $data['id'] ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo esc_html( $this->get_custom_attribute_html( $data ) ); ?>><?php echo wp_kses_post( $data['title'] ); ?></button>
						</fieldset>
					</td>
				</tr>
			</form>

			<?php
			return ob_get_clean();
		}

		/**
		 * Generate create series control HTML.
		 *
		 * @access public
		 * @param mixed $key the key.
		 * @param mixed $data the data.
		 *
		 * @since 1.16.0
		 * @return string
		 */
		public function generate_create_new_series_html( $key, $data ) {

			$field    = $this->plugin_id . $this->id . '_' . $key;
			$defaults = array(
				'class'             => 'button-secondary',
				'id'                => $field,
				'css'               => '',
				'custom_attributes' => array(),
				'desc_tip'          => false,
				'title'             => '',
			);

			$data = wp_parse_args( $data, $defaults );

			ob_start();
			?>

			<form id="form-cs-create-new-series" method="post" class="defaultForm form-horizontal" enctype="multipart/form-data">
				<input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo wp_create_nonce('woocommerce-settings'); ?>">
				<input type="hidden" name="_wp_http_referer" value="<?php echo admin_url( 'admin.php?page=wc-settings&tab=integration&section=integration-contasimple'); ?>">
				<tr valign="top">
					<td></td>
					<td style="padding-top:0px">
						<fieldset>
							<legend class="screen-reader-text"><span><?php echo wp_kses_post( $data['title'] ); ?></span></legend>
							<p><?php echo __( 'If you need a different series from the listed above, you can also create a new series right from here and then select it in the dropdown controls above.', 'contasimple' ) ?></p>
							<p><?php echo __( 'Please enter a <b>name</b> for the series, the <b>numbering mask</b> that will be used to suggest the next invoice number, and then select to which <b>type of document</b> will apply.', 'contasimple' ) ?></p>
							<p style="margin-top: 10px"><?php echo __( 'Note: The mask can contain special characters that will be replaced for their corresponding value during the invoice creation:', 'contasimple' ) ?></p>
							<ul style="list-style: disc; margin-left: 30px">
								<li><?php echo __('<b>AA</b> and <b>AAAA</b> will be replaced by the current year in 2-digit or 4-digit format, respectively.', 'contasimple') ?></li>
								<li><?php echo __('The <b>#</b> symbol will be replaced by an incremental number. The number will have as many digits as the number of # symbols used.', 'contasimple') ?></li>
								<li><?php echo __('<b>Any other character</b> will be preserved.', 'contasimple') ?></li>
							</ul>
							<p style="margin-bottom: 10px"><?php echo __( 'Example: WC-INV-AAAA-### will give a sequence of numbers like WC-INV-2022-001, WC-INV-2022-002 ... and so on.', 'contasimple' ) ?></p>
							<input class="input-text regular-input" type="text" name="ajax-contasimple_new_series_name" id="ajax-contasimple_new_series_name" style="" value="" placeholder="<?php echo __('Ex: WooCommerce Invoices', 'contasimple') ?>">
						</fieldset>
						<fieldset>
							<input class="input-text regular-input" type="text" name="ajax-contasimple_new_series_mask" id="ajax-contasimple_new_series_mask" style="" value="" placeholder="<?php echo __('Ex: WC-INV-AAAA-#####', 'contasimple') ?>">
						</fieldset>
						<fieldset>
							<select class="select " name="ajax-contasimple_new_series_type" id="ajax-contasimple_new_series_type" style="">
								<option value="Normal" selected="selected"><?php echo __('Normal', 'contasimple') ?></option>
								<option value="Rectifying"><?php echo __('Rectifying', 'contasimple') ?></option>
							</select>
						</fieldset>
						<fieldset>
							<label style="padding-right: 8px;"><?php echo __('Preview', 'contasimple') ?>:</label>
							<label style="color: darkslateblue" id="ajax-contasimple_new_series_mask_output"></label>
						</fieldset>
						<fieldset>
							<button class="<?php echo esc_attr( $data['class'] ); ?>" type="submit" name="<?php echo empty( $data['id'] ) ? esc_attr( $field ) : esc_attr( $data['id'] ); ?>" id="<?php echo empty( $data['id'] ) ? esc_attr( $field ) : esc_attr( $data['id'] ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo esc_html( $this->get_custom_attribute_html( $data ) ); ?>><?php echo wp_kses_post( $data['title'] ); ?></button>
							<div class="new-series-spinner" style="display: inline-block;">
								<span class="spinner" style="position: absolute; margin-top: -15px;"></span>
							</div>
						</fieldset>
					</td>
				</tr>
			</form>

			<?php
			return ob_get_clean();
		}

		/**
		 * Generate Label HTML.
		 *
		 * @access public
		 * @param mixed $key the kjey.
		 * @param mixed $data the data.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public function generate_label_html( $key, $data ) {

			$field    = $this->plugin_id . $this->id . '_' . $key;
			$defaults = array(
				'class'    => '',
				'css'      => '',
				'desc_tip' => false,
				'title'    => '',
			);

			$data = wp_parse_args( $data, $defaults );

			ob_start();
			?>

			<tr valign="top">
				<td>
					<label class="<?php echo esc_attr( $data['class'] ); ?>" for="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo esc_html( $this->get_custom_attribute_html( $data ) ); ?>><?php echo wp_kses_post( $data['title'] ); ?></label>
				</td>
				<td>
					<fieldset>
					<label class="<?php echo esc_attr( $data['class'] ); ?>" for="<?php echo esc_attr( $field ); ?>" id="<?php echo esc_attr( $field ); ?>" style="<?php echo esc_attr( $data['css'] ); ?>" <?php echo esc_html( $this->get_custom_attribute_html( $data ) ); ?>><?php echo wp_kses_post( $data['text'] ); ?></label>
				</td>
			</tr>

			<?php
			return ob_get_clean();
		}

		/**
		 * Generate Modal HTML (for Bootstrap).
		 *
		 * @access public
		 * @param mixed $key the key.
		 * @param mixed $data the data.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public function generate_modal_html( $key, $data ) {

			$field    = $this->plugin_id . $this->id . '_' . $key;
			$defaults = array(
				'class'    => '',
				'css'      => '',
				'desc_tip' => false,
				'title'    => '',
			);

			$data = wp_parse_args( $data, $defaults );

			ob_start();

			require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/contasimple-wizard-display.php';

			return ob_get_clean();
		}

		/**
		 * Generate a custom HTML control with company data.
		 *
		 * @access public
		 * @param mixed $key the key.
		 * @param mixed $data the data.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public function generate_summary_html( $key, $data ) {

			$field    = $this->plugin_id . $this->id . '_' . $key;
			$defaults = array(
				'class'    => '',
				'css'      => '',
				'company'  => '',
				'desc_tip' => false,
				'title'    => '',
			);

			$data = wp_parse_args( $data, $defaults );

			ob_start();

			require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/contasimple-company-summary.php';

			return ob_get_clean();
		}

		/**
		 * Generate HTML links.
		 *
		 * Mainly to give access to the documentation PDF in different languages in config page.
		 *
		 * @access public
		 * @param mixed $key the key.
		 * @param mixed $data the data.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public function generate_links_html( $key, $data ) {

			$field    = $this->plugin_id . $this->id . '_' . $key;
			$defaults = array(
				'class'             => '',
				'css'               => '',
				'custom_attributes' => array(),
				'desc_tip'          => false,
				'title'             => '',
			);

			$data = wp_parse_args( $data, $defaults );

			ob_start();

			require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/contasimple-links.php';

			return ob_get_clean();
		}

		/**
		 * Generate HTML for custom heading and description in config page.
		 *
		 * Just to show a somewhat customized heading, the WP form helpers are too rigid.
		 *
		 * @access public
		 * @param mixed $key the key.
		 * @param mixed $data the data.
		 *
		 * @since 1.0.0
		 * @return string
		 */
		public function generate_custom_heading_html( $key, $data ) {

			$field    = $this->plugin_id . $this->id . '_' . $key;
			$defaults = array(
				'id'    => $field,
				'icon'  => '',
				'title' => '',
			);

			$data = wp_parse_args( $data, $defaults );

			ob_start();

			require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/contasimple-custom-heading.php';

			return ob_get_clean();
		}

		/**
		 * Handle post actions to current page.
		 *
		 * The WC_Settings helpers only post data for the fields defined with its API, we have to deal specifically with our own custom fields and buttons.
		 **/
		protected function post_process() {

			// If we are doing a custom post we have to inspect for custom post fields added by AJAX calls or other POST gimmick.
			if ( isset( $_REQUEST['confirm-unlink'] ) // Input var okay.
			     && true === (bool) $_REQUEST['confirm-unlink'] // Input var okay.
			     && wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) // Input var okay.
			) {
				$this->contasimple_admin->force_login();
			}

			if ( isset( $_REQUEST['confirm-reset'] ) // Input var okay.
			     && true === (bool) $_REQUEST['confirm-reset'] // Input var okay.
			     && wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) // Input var okay.
			) {
				$this->contasimple_admin->force_login();

				$invoices_posts = get_posts(
                    array(
                        'post_type' => 'cs_invoice',
                        'post_status' => array(
                            'publish',
                            'future' ),
                        'numberposts' => -1
                    )
                );

				foreach ( $invoices_posts as $invoice_post ) {
					// Delete's each post.
					wp_delete_post( $invoice_post->ID, true );
					// Set to False if you want to send them to Trash.
				}

				// TODO notice?
			}

			if ( isset( $_REQUEST['refresh'] ) // Input var okay.
			     && true === (bool) $_REQUEST['refresh'] // Input var okay.
			     && wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' ) // Input var okay.
			) {
				try {
					$company = $this->contasimple_admin->get_service()->getCurrentCompany();

					$cs_config = $this->contasimple_admin->get_config();

					$cs_config->setCurrencySymbol( $company->getExtraInformation()->getCurrencySymbol() );
					$cs_config->setCountryISOCode( $company->getCountry()->getIsoCodeAlpha2() );
					$cs_config->setFiscalRegionCode( $company->getFiscalRegion()->getCode() );
					$cs_config->setVatName( $company->getFiscalRegion()->getVatName() );
					$cs_config->setInvoiceCulture( $company->getExtraInformation()->getInvoiceCulture() );

					$this->contasimple_admin->set_config( $cs_config );

					new Contasimple_Notice( __( 'Contasimple company settings updated', 'contasimple' ), 'success' );
				} catch (\Exception $e) {
				}
			}

			// Download custom log file for selected date.
			if ( isset( $_REQUEST['log-date'] )
			     && ! empty( $_REQUEST['log-date'] )
				 && wp_verify_nonce( $_REQUEST['_wpnonce'], 'woocommerce-settings' )
			) {
				$this->contasimple_admin->download_cs_log( sanitize_text_field( wp_unslash( $_REQUEST['log-date'] ) ) ); // Input var okay.
			}

			if ( isset( $_REQUEST['sync'] )
				&& 'resume' === sanitize_text_field( wp_unslash( $_REQUEST['sync'] ) )
				&& wp_verify_nonce( $_REQUEST['_wpnonce'], 'resume' )
			) {
				add_action( 'init', array( $this->contasimple_admin, 'resume_queue_manually' ), 10, 0 );
			}
		}

		/**
		 * Handle exceptions thrown during the integration init.
		 * @param Exception $e
		 *
		 * @since 1.5
		 */
		protected function handle_exception( \Exception $e ) {

			// First of all, log the error for further investigation.
			if ( !empty( $this->contasimple_admin ) && !empty( $this->contasimple_admin->getLogger() ) ) {
				$this->contasimple_admin->getLogger()->log( "Exception thrown while trying to load the plugin during " . get_class( $this ) . " _construct : Code: " . $e->getCode(). ", Message: " . $e->getMessage() );
			}

			switch( $e->getCode() ) {
				case AUTH_DENIED:
                case INSUFFICIENT_RIGHTS_ERROR:
					$this->contasimple_admin->force_login();
					$this->init_form_wizard_fields();
					new Contasimple_Notice( __( 'Contasimple login failed. Either your company status or access credentials have changed. Please re-run the configuration wizard to start working with Contasimple again.', 'contasimple' ), 'error' );

					break;

				case HOST_UNREACHABLE:
					new Contasimple_Notice( __( 'Could not connect with Contasimple. Please make sure your server has Internet access and try again and if the problem persists, please contact us.', 'contasimple' ), 'error' );
					$this->init_after_wizard_form( new \Contasimple\Swagger\Client\Model\CompanyApiModel() );
					break;

				case API_ERROR:
					if ( !empty( $e->getMessage() ) ) {
						new Contasimple_Notice( $e->getMessage(), 'error' );
					} else {
						new Contasimple_Notice( __( 'An unknown error occurred trying to fetch your Contasimple account data, please contact us.', 'contasimple' ), 'error' );
					}
					$this->init_after_wizard_form( new \Contasimple\Swagger\Client\Model\CompanyApiModel() );
					break;

				case CANNOT_READ_COMPANY_DATA:
				default:
					new Contasimple_Notice( __( 'An unknown error occurred trying to fetch your Contasimple account data, please contact us.', 'contasimple' ), 'error' );
					$this->init_after_wizard_form( new \Contasimple\Swagger\Client\Model\CompanyApiModel() );
			}
		}

		/**
		 * Loads the config form with company data plus other CS options like invoicing mask, download log, etc.
		 * @param $company \Contasimple\Swagger\Client\Model\CompanyApiModel
		 *        An object containing info about the company.
		 *
		 * @since 1.5
		 */
		protected function init_after_wizard_form( $company = null ) {

			$this->init_form_fields( $company );
			$this->init_settings();

			add_action( 'woocommerce_update_options_integration_' . $this->id, array( $this, 'process_admin_options' ) );
			add_filter( 'woocommerce_settings_api_sanitized_fields_' . $this->id, array( $this, 'sanitize_settings' ) );
		}
	}

endif;
