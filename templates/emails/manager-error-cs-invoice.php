<?php

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

/**
 * @hooked WC_Emails::email_header() Output the email header
 */
do_action( 'woocommerce_email_header', $email_heading, $email ); ?>

<?php
    $wc_email_base_color = get_option( 'woocommerce_email_base_color' );

?>
    <p><?php echo sprintf( esc_html__( 'The invoice sync for your order #%s could not be synchronized. Please check the order and fix it so it can be synchronized correctly', 'contasimple' ), esc_attr( $cs_invoice->order_id ) ); ?></p>

    <p><?php echo esc_html__( 'Summary', 'contasimple' ); ?>:<br/>
        <ol>
            <li style="margin-bottom:10px;">
                <span style="color: <?php echo $wc_email_base_color; ?>"><?php echo esc_html__( 'Order ID', 'contasimple' ); ?>:</span>
                <br/>
                <?php echo esc_attr( $cs_invoice->order_id ); ?>
            </li>
            <li style="margin-bottom:10px;">
                <span style="color: <?php echo $wc_email_base_color; ?>""><?php echo esc_html__( 'Synchronization Attempt Date', 'contasimple' ); ?>:</span>
                <br/>
                <?php echo esc_attr( $cs_invoice->date_sync ); ?>
            </li>
            <li style="margin-bottom:10px;">
                <span style="color: <?php echo $wc_email_base_color; ?>""><?php echo esc_html__( 'Error Message', 'contasimple' ); ?>:</span>
                <br/>
                <?php echo esc_attr( $error_message ); ?>
            </li>
        </ol>
    </p>

<?php
/**
 * @hooked WC_Emails::email_footer() Output the email footer
 */
do_action( 'woocommerce_email_footer', $email );


