<?php
/**
 * Customer CS invoice email (plain text)
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/plain/customer-invoice.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see 	    https://docs.woocommerce.com/document/template-structure/
 * @author		WooThemes
 * @package 	WooCommerce/Templates/Emails/Plain
 * @version		2.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( empty( $email_heading ) ) {
	$email_heading = '';
}

if ( empty( $email ) ) {
	$email = '';
}

if ( empty( $error_message ) ) {
	$error_message = '';
}

if ( empty( $cs_invoice ) ) {
	$cs_invoice = new CS_Invoice_Sync();
}

echo "= " . $email_heading . " =\n\n";

echo sprintf( __( 'The invoice sync for your order #%s could not be synchronized. Please check the order and fix it so it can be synchronized correctly', 'contasimple' ), esc_attr( $cs_invoice->order_id ) ) . "\n\n";

echo __( 'Summary', 'contasimple' ) . ':' . "\n\n";

echo __( 'Order ID', 'contasimple' ) . ': ' . esc_attr( $cs_invoice->order_id ) . "\n\n";
echo __( 'Synchronization Attempt Date', 'contasimple' ) . ': ' . esc_attr( $cs_invoice->date_sync ) . "\n\n";
echo __( 'Error Message', 'contasimple' ) . ': ' . esc_attr( $error_message ) . "\n\n";


echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
