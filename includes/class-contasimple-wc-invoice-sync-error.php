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
class Contasimple_WC_Invoiced_Sync_Error extends WC_Email {

	/**
	 * @var The error message to display in the email to inform the admin user about what happened.
	 * It will be the same as the one displayed on the sync page at the row level.
	 */
	public $error_message;

    /**
     * Set email defaults
     *
     * @since 1.0.0
     */
    public function __construct() {

        // Set ID, this simply needs to be a unique name.
        $this->id = 'wc_cs_invoice_sync_error';

        // Set to use the customer email as the recipient, otherwise a specific recipient should be set up.
        $this->customer_email = false;

        // This is the title in WooCommerce Email settings.
        $this->title = __( 'Invoice Sync Error', 'contasimple' );

        // This is the description in WooCommerce email settings.
        $this->description = __( 'Invoice Synchronization failed.', 'contasimple' );

        // These are the default heading and subject lines that can be overridden using the settings.
        $this->heading = __( 'Invoice Sync Error', 'contasimple' );
        $this->subject = __( 'Invoice Sync Error', 'contasimple' );

        // These define the locations of the templates that this email should use, we'll just use the new order template since this email is similar.
        $this->template_base  = untrailingslashit( plugin_dir_path( dirname( __FILE__ ) ) ) . '/templates/';
        $this->template_html  = 'emails/manager-error-cs-invoice.php';
        $this->template_plain = 'emails/plain/manager-error-cs-invoice.php';

        $this->placeholders   = array(
            '{site_title}'   => $this->get_blogname(),
            '{order_date}'   => '',
            '{order_number}' => '',
        );

        // Allows to trigger the error mail sending process via apply_filters() with 2 params when there is a sync error.
        add_filter( 'woocommerce_cs_invoice_error_sync', array( $this, 'trigger' ), 10, 2 );

        // Call parent constructor to load any other defaults not explicity defined here.
        parent::__construct();

        // This sets the recipient to the settings defined below in init_form_fields().
        $this->recipient = $GLOBALS['user_email'];
    }

    /**
     * Determine if the email should actually be sent and setup email merge variables
     *
     * @param   mixed  $cs_invoice  The CS invoice entity
     * @param   mixed  $sync_result The API result returned while trying to sync, includes info about status and error.
     *
     * @return  mixed true/false if success/error respectively or a 'not found' string if skipped
     *
     * @since   1.0.0
     */
    public function trigger( $cs_invoice = false, $sync_result = false ) {

        // Bail if no order contasimple invoice entity and result object are present.
        if ( empty ( $cs_invoice ) || empty( $sync_result ) ) {
            return;
        }

        $date_sync = date( 'Y-m-d', strtotime( $cs_invoice->date_sync ) );
        $order_ID = $cs_invoice->order_id;
	    $error_message = strip_tags( $sync_result['message'] );

        $this->object = $cs_invoice;
	    $this->error_message = $error_message;
        $this->recipient = get_option( 'admin_email' );
        $this->placeholders['{invoice sync}'] = $date_sync;
        $this->placeholders['{order_number}'] = $order_ID;

	    // Do not send the email if mailing feature is disabled.
        if ( ( ! $this->is_enabled()) ) {
            return 'not sent';
        }

        // Finally send the email by composing the content from its different template parts.
        $result = $this->send( $this->get_recipient(), $this->get_subject(), $this->get_content(), null, null );

        return $result;
    }

    /**
     * Get email subject
     *
     * @since   1.0.0
     * @return  string
     */
    public function get_default_subject() {
        return __( 'Order Invoice {order_number} synchronization error for your {site_title} order', 'contasimple' );
    }

    /**
     * Get email heading
     *
     * @since   1.0.0
     * @return  string
     */
    public function get_default_heading() {
        return __( 'Invoice Sync Error', 'contasimple' );
    }

    /**
     * Get_content_html function.
     *
     * @since 0.1
     * @return string
     */
    public function get_content_html() {

        return wc_get_template_html( $this->template_html, array(
            'cs_invoice'    => $this->object,
	        'error_message' => $this->error_message,
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
	        'cs_invoice'    => $this->object,
	        'error_message' => $this->error_message,
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

