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

if ( empty( $order ) ) {
	$order = new WC_Order();
}

echo "= " . $email_heading . " =\n\n";

if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
    $order_status = $order->status;
} else {
    $order_status = $order->get_status();
}

if ( 'completed' === $order_status ) {
	echo __( 'The invoice for your order #' . $order->get_order_number() . ' is provided in this email as an attachment in PDF format.', 'contasimple' ) . "\n\n";
} elseif ( 'refunded' === $order_status ) {
	echo __( 'The credit note for your order #' . $order->get_order_number() . ' is provided in this email as an attachment in PDF format.', 'contasimple' ) . "\n\n";
}

echo "=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=\n\n";

echo apply_filters( 'woocommerce_email_footer_text', get_option( 'woocommerce_email_footer_text' ) );
