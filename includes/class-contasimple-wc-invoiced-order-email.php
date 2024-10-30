<?php
/**
 * This file defines a new WooCommerce Email type for attaching CS Invoices as PDFs
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

if ( ! function_exists( 'wc_get_template_html') ) {

    function wc_get_template_html( $template_name, $args = array(), $template_path = '', $default_path = '' ) {
        ob_start();
        wc_get_template( $template_name, $args, $template_path, $default_path );
        return ob_get_clean();
    }
}

/**
 * A custom Invoiced Order WooCommerce Email class
 *
 * @link       http://www.contasimple.com
 * @since      1.0.0
 *
 * @package    contasimple
 * @subpackage contasimple/includes
 * @author     Contasimple S.L. <soporte@contasimple.com>
 */
class Contasimple_WC_Invoiced_Order_Email extends WC_Email {

	/**
	 * CS Invoice to use for the attachments.
	 *
	 * @var object|bool
	 */
	public $invoice;

	/**
	 * Set email defaults
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// Set ID, this simply needs to be a unique name.
		$this->id = 'wc_cs_invoice_generated';

		// Set to use the customer email as the recipient, otherwise a specific recipient should be set up.
		$this->customer_email = true;

		// This is the title in WooCommerce Email settings.
		$this->title = __( 'Invoice Generated', 'contasimple' );

		// This is the description in WooCommerce email settings.
		$this->description = __( 'Invoice Generated notification emails are sent when an invoice is successfully generated and synced to Contasimple.com', 'contasimple' );

		// These are the default heading and subject lines that can be overridden using the settings.
		$this->heading = __( 'Order Invoice', 'contasimple' );
		$this->subject = __( 'Order Invoice', 'contasimple' );

		// These define the locations of the templates that this email should use, we'll just use the new order template since this email is similar.
		$this->template_base  = untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) ) . '/templates/';
		$this->template_html  = 'emails/customer-cs-invoice.php';
		$this->template_plain = 'emails/plain/customer-cs-invoice.php';

		$this->placeholders   = array(
			'{site_title}'   => $this->get_blogname(),
			'{order_date}'   => '',
			'{order_number}' => '',
		);

		// Trigger right after the invoice is synced as OK.
		add_filter( 'woocommerce_cs_invoice_generated_sync', array( $this, 'trigger' ), 10, 3 );

		// Call parent constructor to load any other defaults not explicity defined here.
		parent::__construct();

		// This sets the recipient to the settings defined below in init_form_fields().
		$this->recipient = $this->get_option( 'recipient' );
	}

	/**
	 * Determine if the email should actually be sent and setup email merge variables
	 *
	 * @param   mixed  $order       The Order
     * @param   mixed  $cs_invoice  The CS invoice entity
     * @param   bool   $manual      If we are manually sending the invoice or not (on hook)
	 *
	 * @return  mixed true/false if success/error respectively or a 'not found' string if skipped
     *
	 * @since   1.0.0
	 */
	public function trigger( $order = false, $cs_invoice = false, $manual = false ) {

		// Bail if no order ID is present.
		if ( empty( $order ) || empty ( $cs_invoice ) ) {
			return;
		}

        if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
            $date_completed = date( 'Y-m-d', strtotime( Contasimple_WC_Backward_Compatibility::get_order_completed_date( $order ) ) );
        } else {
            $date_completed = Contasimple_WC_Backward_Compatibility::get_order_completed_date( $order, 'Y-m-d'); // $order->get_date_completed()->date('Y-m-d');
        }

		// Bail if customer NIF not available (clientes varios) as it should not be required
		if ( empty( Contasimple_WC_Backward_Compatibility::get_order_meta( $cs_invoice->order_id, 'NIF', true) ) ) { // ( ! in_array( $this->object->get_shipping_method(), array( 'Three Day Shipping', 'Next Day Shipping' ) ) ) {
			return;
		}

        $billing_email = ( version_compare( WC_VERSION, '2.7', '<' ) ) ? $order->billing_email : $order->get_billing_email();
        $date_created = ( version_compare( WC_VERSION, '2.7', '<' ) ) ? $order->order_date : wc_format_datetime( $order->get_date_created() );

        $this->object = $order;
		$this->invoice = $cs_invoice;
		$this->recipient                           = $billing_email;
		$this->placeholders['{order_date}']        = $date_created;
		$this->placeholders['{order_number}']      = $this->object->get_order_number();
		$this->placeholders['{cs_invoice_number}'] = $cs_invoice->number;

        // In these cases do not try to automatically send the email
        if ( ( ! $this->is_enabled() && false == $manual) || ( false == $manual && $date_completed != date('Y-m-d') ) ) {
			return 'not sent';
		}

		// Finally send the email by composing the content from its different template parts.
		$result = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), $this->get_headers(), $this->get_attachments() );

		return $result;
	}

	/**
	 * Get email attachments filter
     *
     * @since   1.0.0
	 * @return  string
	 */
	public function get_attachments() {
		return apply_filters( 'woocommerce_email_attachments', array(), $this->id, $this->object, $this->invoice );
	}

	/**
	 * Get email subject
	 *
	 * @since   1.0.0
	 * @return  string
	 */
	public function get_default_subject() {
		return __( 'Invoice {cs_invoice_number} available for your {site_title} order from {order_date}', 'contasimple' );
	}

	/**
	 * Get email heading
	 *
     * @since   1.0.0
	 * @return  string
	 */
	public function get_default_heading() {
		return __( 'Invoice {cs_invoice_number}', 'contasimple' );
	}

	/**
	 * Get_content_html function.
	 *
	 * @since 0.1
	 * @return string
	 */
	public function get_content_html() {

		return wc_get_template_html( $this->template_html, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => false,
			'email'         => $this,
		), '', $this->template_base );
	}

	/**
	 * Get_content_plain function.
	 *
	 * @since 0.1
	 * @return string
	 */
	public function get_content_plain() {
		return wc_get_template_html( $this->template_plain, array(
			'order'         => $this->object,
			'email_heading' => $this->get_heading(),
			'sent_to_admin' => false,
			'plain_text'    => true,
			'email'         => $this,
		), '', $this->template_base );
	}

	/**
	 * Initialize Settings Form Fields
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {
        /*
        if ( ! method_exists ( $this, 'get_email_type' ) ) {
            return $this->email_type && class_exists( 'DOMDocument' ) ? $this->email_type : 'plain';
        }*/

        // WC < 2.3 retrocompatibility
        if ( ! method_exists ( $this, 'get_email_type_options' ) ) {
            $types = array(
                'plain' => __( 'Plain text', 'woocommerce' )
            );
            if ( class_exists( 'DOMDocument' ) ) {
                $types['html'] = __( 'HTML', 'woocommerce' );
                $types['multipart'] = __( 'Multipart', 'woocommerce' );
            }
            return $types;
        } else {
            $mail_options =  $this->get_email_type_options();
        }

		$this->form_fields = array(
			'enabled'    => array(
				'title'   => __( 'Enable/Disable', 'woocommerce' ),
				'type'    => 'checkbox',
				'label'   => __( 'Enable this email notification', 'woocommerce' ),
				'default' => 'yes',
			),
			/*
			'recipient'  => array(
				'title'       => 'Recipient(s)',
				'type'        => 'text',
				'description' => sprintf( __( 'Enter recipients (comma separated) for this email. Defaults to <code>%s</code>.' ), esc_attr( get_option( 'admin_email' ) ) ),
				'placeholder' => '',
				'default'     => '',
			),*/
			'subject'    => array(
				'title'       => __( 'Subject', 'woocommerce' ),
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the email subject line. Leave blank to use the default subject: <code>%s</code>.', 'contasimple' ), $this->subject ),
				'placeholder' => '',
				'default'     => '',
			),
			'heading'    => array(
				'title'       => __( 'Email Heading', 'woocommerce' ),
				'type'        => 'text',
				'description' => sprintf( __( 'This controls the main heading contained within the email notification. Leave blank to use the default heading: <code>%s</code>.' , 'contasimple' ), $this->heading ),
				'placeholder' => '',
				'default'     => '',
			),
			'email_type' => array(
				'title'       => __( 'Email type', 'woocommerce' ),
				'type'        => 'select',
				'description' => __( 'Choose which format of email to send.', 'woocommerce' ),
				'default'     => 'html',
				'class'       => 'email_type',
				'options'     => $mail_options,
			),
		);
	}
} // end \WC_Expedited_Order_Email class
