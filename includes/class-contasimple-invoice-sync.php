<?php
/**
 * Contasimple file for Invoice Sync new class
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

const EMAIL_NOT_SENT = 0;
const EMAIL_SENT = 1;
const EMAIL_FAILED = -1;

/**
 * Class CS_Invoice_Sync
 *
 * This class loads/stores sync data from a custom table and exposes a few helper methods.
 *
 * Notice: We are using pascal case naming conventions here in some cases against WP codestyle guide to bring some consistency between implementations,
 * some of this methods and attributes are named this way in the CS ecosystem.
 */
class CS_Invoice_Sync {
	/**
	 * Internal id for the CPT
	 *
	 * @var int $ID;
	 */
	public $ID = 0;

	/**
	 * Id of the WooCommercre order (WC_Order post type)
	 *
	 * @var int $order_id;
	 */
	public $order_id = 0;

	/**
	 * Id of the WooCommerce partial order refund.
	 *
	 * @var array|int|mixed
	 */
	public $refund_id = 0;

	/**
	 * The refund hook args
	 *
	 * @var null
	 */
	public $args = null;

	/**
	 * The date of the latest synchronization with Contasimple (either success or failure)
	 *
	 * @var string $date_sync;
	 */
	public $date_sync = '';

	/**
	 * An internal int as a define constant that tells us the current invoice state.
	 *
	 * See \common\Service.php file for a list of enum states.
	 *
	 * @var int $state;
	 */
	public $state = NOT_SYNC;

	/**
	 * Id of the invoice in the Contasimple platform
	 *
	 * We need it to open external link to the invoice if needed be, or to download the PDF invoice via the CS API.
	 *
	 * @var int $externalID;
	 */
	public $externalID = 0;

	/**
	 * Id of company in CS that owns the invoice.
	 *
	 * This id will come from the API when running the configuration wizard.
	 * Needed when opening the invoice in CS Website from this plugin, as the companyID must be passed to the link as a get parameter to switch between companies.
	 *
	 * @var int $externalID;
	 */
	public $companyID = 0;

	/**
	 * Invoice number, already formatted via masking
	 *
	 * The number must be filled with a value that comes directly from the getNextInvoiceNumber() method from the CS API,
	 * since WooCommerce doesn't generate sequential invoice numbers without the help of external plugins.
	 *
	 * @var string $number;
	 */
	public $number = '';

	/**
	 * Number of syncs attempted
	 *
	 * @var int $attempts;
	 */
	public $attempts = 0;

	/**
	 * Total invoice amount (taxes included)
	 *
	 * Although this can be theoretically be accessed via the WC_Order class total price variable, we will store it on our own as
	 * it might come in handy (ie: WC formats refunds as strikethrough prices with a 0 value next to it, we prefer storing the current negative amount, etc).
	 *
	 * @var float $amount;
	 */
	public $amount = 0;

	/**
	 * Email status based upon a defined constant value
	 *
	 * Default is EMAIL_NOT_SENT (0)
	 * Set to EMAIL_SENT (1) if success
	 * Set to EMAIL_FAILED (-1) if an error occurs during the process
	 *
	 * @var int $mail_status;
	 */
	public $mail_status = EMAIL_NOT_SENT;

	/**
	 * Invoice mask
	 *
	 * @var string $mask The mask that we will use for this invoice.
	 */
	public $mask = '';

	/**
	 * Currency symbol the order was placed in.
	 *
	 * Although the order has this info, if an order is deleted we could not access the original currency anymore,
	 * so we keep a copy of this variable here in order to be able to display formatted amounts in out list page even
	 * if the order was deleted.
	 *
	 * @var string Ie: $
	 */
	public $order_currency = '';

	/**
	 * Currency symbol set in the fiscal region of the configured Contasimple company.
	 *
	 * Although we can always get this info from the stored settings, when we display info about a failed sync,
	 * we want to retrieve what currency was set at that moment, since company info can be updated at any time.
	 *
	 * @var string Ie: €
	 */
	public $cs_currency = '';

	/**
	 * API Error Message
	 *
	 * @since   1.0.4
	 *
	 * @var string $api_message This will only be filled if the API returns an error that is not already handled by the
	 * module.  The error messages are typically well-known messages that the plugin knows and that have set translations
	 * at the Wordpress level.  However, as the API grows and new use-cases arise, we need a way to allow some
	 * flexibility in case that the specifications change, and different error codes might be returned.
	 * By setting a new special API_ERROR special code, we will bypass the translations and just render the message
	 * returned by the API.
	 */
	public $api_message = null;

	/**
	 * Order completion date.
	 *
	 * @var null
	 */
	public $completed_date = null;

	/**
	 * CS_Invoice_Sync constructor.
	 *
     * @since    1.0.0
	 * @param    int $cs_invoice_id An ID to retrieve an existing invoice, or null to create one empty.
	 */
	public function __construct( $cs_invoice_id = null ) {

		if ( ! empty( $cs_invoice_id ) ) {
			$post = get_post( $cs_invoice_id );
			if ( ! empty( $post ) ) {
				$this->ID             = $post->ID;
				$this->order_id       = $post->order_id;
				$this->refund_id      = $post->refund_id;
				$this->args           = $post->args;
				$this->externalID     = $post->externalID;
				$this->companyID      = $post->companyID;
				$this->state          = $post->state;
				$this->date_sync      = $post->date_sync;
				$this->number         = $post->number;
				$this->attempts       = $post->attempts;
				$this->amount         = $post->amount;
				$this->mail_status    = $post->mail_status;
				$this->mask           = $post->mask;
				$this->api_message    = $post->api_message;
				$this->order_currency = $post->order_currency;
				$this->cs_currency    = $post->cs_currency;
			}
		}
	}

	/**
	 * Save into database the currently set variable properties
	 *
	 * Returns true if updating values succeeds, false if any value could not be saved (which doesn't really mean that nothing was saved, keep in mind).
	 *
     * @since    1.0.0
	 * @return   bool
	 */
	public function save() {

		$updated = true;
		$invoice_meta = get_post_meta( $this->ID );

		foreach ( $invoice_meta as $key => $val ) {
			if ( $key == 'args' ) {
				if ( unserialize( $val[0] ) != $this->$key ) {
					$updated &= update_post_meta( $this->ID, $key, $this->$key );
				}
			} else {
				if ( $val[0] != $this->$key ) {
					$updated &= update_post_meta( $this->ID, $key, $this->$key );
				}
			}
		}

		return (bool) $updated;
	}

	/**
	 * Insert the post into database (first time)
	 *
	 * As a blank invoice with slightly default value modifications depending if the order is in the status complete or refund state.
	 *
     * @since    1.0.0
	 * @param    int $order_id The WC order id.
	 *
	 * @return   bool
	 * @throws   Exception When unexpected behaviour occurs.
	 */
	public function create( $order_id, $refund_id = null, $args = null ) {

		if ( ! empty( $this->ID ) ) {
			throw new Exception( 'Already created' );
		}

		if ( empty( $order_id ) ) {
			throw new Exception( 'Empty order_id' );
		}

        try{
            $order = new WC_Order( $order_id );
        }
		catch (\Exception $ex){
            $order = new WC_Order();
        }
		if ( empty( $order ) ) {
			throw new Exception( 'Cannot load order data' );
		}

        if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
            $order_status = $order->status;
        } else {
            $order_status = $order->get_status();
        }

		if ( 'refunded' === $order_status && ! version_compare( WC_VERSION, '2.2', '<' ) ) {
			$invoice_type = __( 'Credit note', 'contasimple' );
		} else {
			$invoice_type = __( 'Invoice', 'contasimple' );
		}

		$date = empty( $refund_id ) ?
			Contasimple_WC_Backward_Compatibility::get_order_completed_date( $order ) :
			Contasimple_WC_Backward_Compatibility::get_order_refunded_date( $order, $refund_id );

		$date_gmt = get_gmt_from_date( $date );

		$cs_invoice_new_data = array(
			'post_title'    => sprintf( '%s %s #%d', $invoice_type, __( 'order', 'contasimple' ), $order_id ),
			'post_content'  => '',
			'post_type'     => 'cs_invoice',
			'post_status'   => 'publish',
			'post_author'   => get_current_user_id(),
            'post_date'     => $date,
			'post_date_gmt' => $date_gmt,
		);

		// Insert the post into the database.
		$invoice_id = wp_insert_post( $cs_invoice_new_data );

		// If id > 0 means insert OK.
		if ( ! empty( $invoice_id ) ) {
			$this->ID        = $invoice_id;
			$this->order_id  = $order_id;
			$this->refund_id = $refund_id;
			$this->args      = is_serialized( $args ) ? $args : serialize( $args );
			$this->state     = PENDING; // default state.

			$success = add_post_meta( $this->ID, 'externalID', $this->externalID ) &&
					   add_post_meta( $this->ID, 'state', $this->state ) &&
					   add_post_meta( $this->ID, 'date_sync', $this->date_sync ) &&
					   add_post_meta( $this->ID, 'number', $this->number ) &&
					   add_post_meta( $this->ID, 'companyID', $this->companyID ) &&
					   add_post_meta( $this->ID, 'attempts', $this->attempts ) &&
					   add_post_meta( $this->ID, 'order_id', $this->order_id ) &&
			           add_post_meta( $this->ID, 'refund_id', $this->refund_id ) &&
			           add_post_meta( $this->ID, 'args', $this->args ) &&
					   add_post_meta( $this->ID, 'amount', $this->amount );
					   add_post_meta( $this->ID, 'mail_status', $this->mail_status );
					   add_post_meta( $this->ID, 'mask', $this->mask );
					   add_post_meta( $this->ID, 'api_message', $this->api_message);
			           add_post_meta( $this->ID, 'cs_currency', $this->cs_currency);
					   add_post_meta( $this->ID, 'order_currency', $this->order_currency);
			return false !== $success;
		} else {
			return false;
		}
	}

	/**
	 * Create empty  invoice statically
	 *
     * @since    1.0.0
	 * @param    int $order_id A WC order id.
	 *
	 * @return   bool|CS_Invoice_Sync
	 */
	public static function create_empty( $order_id, $refund_id = null, $args = null ) {

		$cs_invoice = new CS_Invoice_Sync();

		if ( $cs_invoice->create( $order_id, $refund_id, $args ) ) {
			return $cs_invoice;
		} else {
			return false;
		}
	}

	/**
	 * Get invoices pending of successful sync
	 *
	 * It depends a bit on each eCommerce but in this case we define sync pending as not synced with success.
	 * So an invoice marked with pending or any of the listed error states is a pending invoice.
	 * Stopped (like in PS due to the ability to manually sync) seems like it won't make it here since every invoice
	 * needs to be synced no matter what. Otherwise the queue cannot be resumed.
	 *
     * @since    1.0.0
	 * @return   array As a list of N instances of this class, sorted by order_completed field.
	 */
	public static function get_pending_invoices() {

		$args = array(
			'post_type'  => 'cs_invoice',
			'post_status' => array( 'publish', 'future' ),
            'order'      => 'ASC',
            'posts_per_page ' => -1,
            'nopaging' => true,
			'meta_query' => array(
				'relation' => 'OR', // Optional, defaults to "AND".
				array(
					'key'     => 'state',
					'type'    => 'numeric',
					'value'   => array( NOT_SYNC, PENDING, CHANGED ),
					'compare' => 'IN',
				),
				array(
					'relation' => 'AND',
					array(
						'key'     => 'state',
						'type'    => 'numeric',
						'value'   => PAYMENT_SYNC_ERROR,
						'compare' => '!=',
					),
					array(
						'key'     => 'state',
						'type'    => 'numeric',
						'value'   => SYNC_ERROR,
						'compare' => '>=',
					),
				),
			),
		);

		$cs_invoice_posts = get_posts( $args );

        if ( count($cs_invoice_posts) > 0 ) {
            // We would probably better get the order_completed date from the WP_Query but it's a little bit complex.
            // For now, just do a 2nd check by retrieving the order based on the invoice FK field and add inject
            // the completed_date field.
            foreach ( $cs_invoice_posts as $cs_invoice_post ) {
                $cs_invoice_with_date = new CS_Invoice_Sync( $cs_invoice_post->ID );

                try{
                    $order = new WC_Order($cs_invoice_with_date->order_id);
                }
                catch(\Exception $ex){
                    $order = new WC_Order();
                }

                if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
                    $completed_date = $order->completed_date;
                } else {
                    if( !empty($order->get_date_completed())){
                        $completed_date = $order->get_date_completed()->__toString( );
                    }
                    else{
                        $completed_date = '';
                    }

                }

                $cs_invoice_with_date->completed_date = $completed_date;
                $cs_invoices[] = $cs_invoice_with_date;
            }

            // Sort by order_complete date
            usort($cs_invoices, array(get_class(), "sort_invoice_by_date_completed_function"));

            // Return invoices sorted by completion date (payment) of their respective orders.
            return $cs_invoices;
        } else {
            return array();
        }
	}

    /**
     * Return an int to pass down to the sorting function
     *
     * @since    1.0.0
     * @param    $a WC_DateTime First date
     * @param    $b WC_DateTime Second date
     *
     * @return   false|int
     */
    protected static function sort_invoice_by_date_completed_function( $a, $b ) {

        return strtotime($a->completed_date) - strtotime($b->completed_date);
    }

    /**
     * Get all invoices associated to the same order
     *
     * This might be needed if we have to deal with more than one invoice per order. This was a common case in PS,
     * however at this moment we haven't explored this option in WC. Not used at this moment.
     * TODO: Think about use cases or maybe remove from the plugin.
     *
     * @since    1.0.0
     * @param    $order_id
     *
     * @return   array
     */
	public static function get_invoices_from_order( $order_id ) {

		$args = array(
			'post_type'  => 'cs_invoice',
			'order'      => 'ASC',
			'orderby'    => 'date',
			'meta_query' => array(
				array(
					'key'     => 'order_id',
					'type'    => 'numeric',
					'value'   => $order_id,
				),
			),
		);

		$cs_invoice_posts = get_posts( $args );
		$cs_invoices = array();

		foreach ( $cs_invoice_posts as $cs_invoice_post ) {
			$cs_invoices[] = new CS_Invoice_Sync( $cs_invoice_post->ID );
		}

		return $cs_invoices;
	}

	/**
	 * Update synchronization state in metadata
	 *
	 * @param int $externalID  The CS internal invoice ID retrieved from the API, so that we can show an external link to view on CS website.
	 * @param int $state       The state that we want to put the invoice in, can use constants defined in Service.php:
	 *                         NOT_SYNC = 0, SYNC_OK = 1, INVOICE_CHANGED = 2, PAUSED = 3, SYNC_ERROR = 10 (other error states defined as well as > 10).
	 * @param int $attempts    Attempt handling is done outside the method for whatever reason, pass 0 if you want to reset them or pass down $attempts++ to increment it.
	 *
	 * @return true            If everything is ok.
	 * @throws \Exception      If something goes wrong.
	 */
	public function update_sync_state( $externalID, $state, $api_message, $attempts = null ) {

		try {
			$this->state     = (int) $state;
			$this->api_message = $api_message;
			$this->date_sync = current_time( 'mysql' ); //date( 'Y-m-d H:i:s' );

			if ( isset( $externalID ) ) {
				$this->externalID = $externalID;
			}

			if ( isset( $attempts ) ) {
				$this->attempts = $attempts;
			}

			if ( ! $this->save() ) {
				throw new \Exception( __('Error trying to save the new state', 'contasimple' ), GENERIC_ERROR );
			}

			return true;
		} catch ( \Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Checks if this invoice is a refund.
	 * The check is based on whether there are stored arguments or not.
	 *
	 * @since 1.7.0
	 *
	 * @return bool
	 */
	public function is_a_refund() {

		$args = $this->get_unserialized_args();

		return ! empty( $args );
	}

	/**
	 * Returns the invoice stored arguments.
	 *
	 * Note: These arguments are filled only when the refund hook has been triggered,
	 * this means that having args means this is a refund or negative invoice.
	 *
	 * @since 1.7.0
	 *
	 * @return array|int|mixed|null
	 */
	public function get_unserialized_args() {
		if ( ! empty( $this->args ) ) {
			if ( is_serialized( $this->args ) ) {
				return unserialize( $this->args );
			} else {
				return $this->args;
			}
		} else {
			return null;
		}
	}

	public function get_partial_refund_total_base()
	{
		$args = $this->get_unserialized_args();

		if ( empty( $args ) || empty( $args['line_items'] ) ) {
			return null;
		}

		$total_base = null;

		foreach ( $args['line_items'] as $refund_line ) {
			$total_base += $refund_line['refund_total'];
		}

		return $total_base;
	}

	public function get_partial_refund_total_tax()
	{
		$args = $this->get_unserialized_args();

		if ( empty( $args ) || empty( $args['line_items'] ) ) {
			return null;
		}

		$total_base = null;

		foreach ( $args['line_items'] as $refund_line ) {
			$total_base += $refund_line['refund_total'];
		}

		return $args['amount'] - $total_base;
	}
}
