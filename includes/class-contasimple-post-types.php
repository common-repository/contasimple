<?php
/**
 * This file defines a Custom Post Type (CPT) and its helper methods.
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
 * Contasimple custom post types (invoice)
 *
 * @link       http://www.contasimple.com
 * @since      1.0.0
 *
 * @package    contasimple
 * @subpackage contasimple/includes
 * @author     Contasimple S.L. <soporte@contasimple.com>
 */
class Contasimple_CPT {

	protected $logger;

	protected $unmarked = 0;
	protected $pending  = 0;
	protected $synced   = 0;
	protected $modified = 0;
	protected $error    = 0;
	protected $limit    = 0;
	protected $warning  = 0;

	/**
	 * Initialize class.
	 *
	 * Check if we are the correct post type and add filter hooks to handle custom behaviour.
	 *
	 * @since    1.0.0
	 */
	public function init() {
		global $typenow;

		$this->logger = CSLogger::getDailyLogger();

		if ( empty( $typenow ) ) {
			// try to pick it up from the query string.
			if ( isset( $_GET['post'] ) ) {
				$post     = get_post( filter_input( INPUT_GET, 'post' ) );
				$the_type = $post->post_type;
			} else {
				$the_type = null;
			}
		} else {
			$the_type = $typenow;
		}

		if ( 'cs_invoice' === $the_type ) {

			WC()->mailer();

			add_filter( 'admin_head', array( $this, 'add_invoice_custom_action_buttons_css' ) );
			add_filter( 'post_row_actions', array( $this, 'remove_actions' ), 10, 2 );
			add_filter( 'views_edit-cs_invoice', array( $this, 'get_summary' ) );
			add_filter( 'views_edit-cs_invoice', array( $this, 'add_modal_manual_sync' ) );
			add_filter( 'bulk_actions-edit-cs_invoice', array( $this, 'remove_bulk_actions_dropdown' ) );
			add_filter( 'manage_cs_invoice_posts_columns', array( $this, 'add_contasimple_invoice_columns' ) );
			add_filter( 'manage_cs_invoice_posts_custom_column', array( $this, 'add_column_content' ) );
			add_filter( 'restrict_manage_posts', array( $this, 'sync_contasimple_invoices_now' ), 1 );
			add_filter( 'parse_query', array( $this, 'admin_cs_invoice_posts_filter' ) );
			add_action( 'pre_get_posts', array( $this, 'cs_invoices_extended_search' ) );
		}
	}

	/**
	 * Register Custom Post Type
	 *
	 * Will show a new CPT entry in the WP admin menu to access the Posts Edit page.
	 *
	 * @since 1.0.0
	 */
	public function register() {

		// Only register the post type if the wizard has been run, as it is not allowed by specs to have access to invoicing info & sync process
		// if no company is configured and has proper access to the API.

		if ( Contasimple_Admin::getInstance()->properly_configured() ) {
			register_post_type( 'cs_invoice',
				array(
					'labels'              => array(
						'name'               => __( 'Contasimple Invoices', 'contasimple' ),
						'singular_name'      => __( 'Contasimple Invoice', 'contasimple' ),
						'menu_name'          => __( 'Invoices', 'contasimple' ),
						'add_new_item'       => __( 'Add New Invoice', 'contasimple' ),
						'edit_item'          => __( 'View Invoice', 'contasimple' ),
						'new_item'           => __( 'New Invoice', 'contasimple' ),
						'view_item'          => __( 'View Invoice', 'contasimple' ),
						'all_items'          => __( 'Invoices', 'contasimple' ),
						'search_items'       => __( 'Search Invoice', 'contasimple' ),
						'not_found'          => __( 'No Invoices found', 'contasimple' ),
						'not_found_in_trash' => __( 'No Invoices found in Trash', 'contasimple' ),
					),
					'description'         => __( 'This is where you can find all invoices synced to contasimple.', 'contasimple' ),
					'exclude_from_search' => true,
					'publicly_queryable'  => false,
					'public'              => false,
					'show_ui'             => true,
					'capability_type'     => 'post',
					'capabilities'        => array(
						'read_post'    => 'do_not_allow',
						'create_posts' => 'do_not_allow', // false < WP 4.5, credit @Ewout.
						'delete_posts' => 'do_not_allow',
					),
					'show_in_menu'        => current_user_can( 'manage_woocommerce' ) ? 'woocommerce' : true,
					'hierarchical'        => false,
					'has_archive'         => false,
					'query_var'           => true,
					'supports'            => array( 'title' ),
					'rewrite'             => false,
					'show_in_nav_menus'   => false,
				)
			);
		}
	}

	/**
	 * Adds styling to custom columns.
	 *
	 * Mainly setting proper widths and icons.
	 *
	 * @since 1.0.0
	 */
	public function add_invoice_custom_action_buttons_css() {

		echo '<style>.post-type-cs_invoice .view.cs_sync::after { font-family: woocommerce; content: "\e031" !important; }</style>';
		echo '<style>.post-type-cs_invoice .view.cs_stop::after { font-family: woocommerce; content: "\e033" !important; }</style>';
		echo '<style>.post-type-cs_invoice .view.cs_resume::after { font-family: woocommerce; content: "\e031" !important; }</style>';
		echo '<style>.post-type-cs_invoice .view.cs_email::after { font-family: woocommerce; content: "\e02d" !important;  }</style>';
		echo '<style>.post-type-cs_invoice .view.cs_email_sent::after { font-family: icomoon; content: "\e902" !important; color:green;  }</style>';
		echo '<style>.post-type-cs_invoice .view.cs_email_failed::after { font-family: icomoon; content: "\e903" !important; color:#B40404;  }</style>';
		echo '<style>.post-type-cs_invoice .view.cs_processing::after { font-family: dashicons; content: "\f463" !important; }</style>';
		echo '<style>.post-type-cs_invoice .view.cs_view::after { font-family: icomoon; content: "\e900" !important; color: #4A6C9A;   }</style>';
		echo '<style>.post-type-cs_invoice .view.cs_pdf::after { font-family: icomoon; content: "\e901" !important; color: #B40404; }</style>';
		echo '<style>.post-type-cs_invoice .view.cs_pdf.disabled::after { font-family: icomoon; content: "\e901" !important; color: #a0a5aa!important; }</style>';
		echo '<style>.post-type-cs_invoice .view.disabled::after { color: #a0a5aa!important; }</style>';
		echo '<style>.post-type-cs_invoice [id^=cb-select-all] { display: none; }</style>';

		echo '<style>.post-type-cs_invoice .manage-column.column-cs_invoice_title { width: 75px; }</style>';
		/*echo '<style>.post-type-cs_invoice .manage-column.column-cs_number { max-width: 160px; }</style>';*/ /* cs_customer_nif */
		echo '<style>.post-type-cs_invoice .manage-column.column-cs_total { width: 90px; }</style>';
		/*echo '<style>.post-type-cs_invoice .manage-column.column-date { max-width: 100px; }</style>';*/
		echo '<style>.post-type-cs_invoice .manage-column.column-cs_state { width: 70px; }</style>';
		/*echo '<style>.post-type-cs_invoice .manage-column.column-cs_message { max-width: 180px; }</style>';*/
		/*echo '<style>.post-type-cs_invoice .manage-column.column-cs_last_sync { max-width: 110px; }</style>';*/
		echo '<style>.post-type-cs_invoice .manage-column.column-order_actions { width: 100px; }</style>';
	}

	/**
	 * Render the summary as a WP Notice
	 *
	 * A summary of all listed invoices current state.
	 *
	 * @param array $views Not needed for us but must keep since it is passed down with the hook call.
	 * @param bool $is_ajax Special handing if it is an ajax request.
	 *
	 * @return null|string
	 *
	 * @since 1.0.0
	 */
	public function get_summary( $views, $is_ajax = false ) {
		global $post_type, $pagenow;

		// Bail out if not on shop order list page or explicitly an ajax call.
		if ( 'admin-ajax.php' !== $pagenow ) {
			if ( 'edit.php' !== $pagenow || 'cs_invoice' !== $post_type ) {
				return null;
			}
		}

		$this->calculate_states_count();

		$message = __( 'Synchronization is up to date. Everything is ok.', 'contasimple' );
		$type    = 'success';

		if ( $this->limit > 0 ) {
			/* translators: %1$s: Context for 'here' as in: ...by clicking here */
			$message = sprintf( __( 'One or more invoices can not be synchronized because you have reached the limit of your plan. To continue uploading invoices, you must upgrade to a higher plan by clicking %1$s and try to sync again.', 'contasimple' ), '<a href=' . URL_CS_UPGRADE . ' target="_blank">' . __( 'here', 'contasimple' ) . '</a>' );
			$type    = 'error';
		} elseif ( $this->error > 0 ) {
			/* translators: %1$d: a number, like 5*/
			$message = sprintf( __( 'There are %1$d synchronization errors. Please check the error messages.', 'contasimple' ), $this->error );
			$type    = 'error';
		} elseif ( $this->modified > 0 ) {
			/* translators: %1$d: a number, like 5*/
			$message = sprintf( __( 'There are %1$d modified invoices that need to be synced again.', 'contasimple' ), $this->modified );
			$type    = 'warning';
		} elseif ( $this->pending > 0 ) {
			/* translators: %1$d: a number, like 5*/
			$message = sprintf( __( 'There are %1$d invoices pending to be synced.', 'contasimple' ), $this->pending );
			$type    = 'warning';
		} elseif ( $this->warning > 0 ) {
			$message = sprintf( __( 'Synchronization is up to date. All pending invoices were synced but some require your attention. Please check the warning messages.', 'contasimple' ), $this->warning );
			$type    = 'warning';
		}

		$summary = new Contasimple_Notice( $message, $type, $is_ajax );

		if ( $is_ajax ) {
			return $summary->render();
		} else {
			$summary->render();

			return null; // We get rid of actions as well.
		}
	}

	public function add_modal_manual_sync( $views ) {
		require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/contasimple-confirm-manual-sync.php';
    }

	/**
	 * Iterate posts and keep a count of each state.
	 *
	 * This array of states count is used by the custom Notice that will display on top as a summary.
	 *
	 * @throws \Exception In case something goes wrong.
	 *
	 * @since 1.0.0
	 */
	public function calculate_states_count() {

		try {
			$args = array(
				'post_type' => 'cs_invoice',
				'posts_per_page ' => -1,
				'nopaging' => true,
				);

			$posts = get_posts( $args );

			foreach ( $posts as $post ) {
				switch ( $post->state ) {
					case NOT_SYNC:
						$this->unmarked ++;
						break;
					case PENDING:
						$this->pending ++;
						break;
					case SYNC_OK:
						$this->synced ++;
						break;
					case SYNCED_WITH_INVALID_VAT_NUMBER:
					case SYNCED_NATIONAL_WITHOUT_TAXES:
					case SYNCED_WITH_ROUNDING_ISSUES:
					case SYNCED_WITH_DISCREPANCY:
						if ( ! empty( get_option( 'woocommerce_integration-contasimple_settings' ) ) && array_key_exists( 'show_warnings', get_option( 'woocommerce_integration-contasimple_settings' ) ) && 'yes' === get_option( 'woocommerce_integration-contasimple_settings' )['show_warnings'] ) {
							$this->warning ++;
						}
						break;
					case CHANGED:
						$this->modified ++;
						break;
					case PAUSED:
						// Not decided if paused counts towards the global message state.
						break;
					case PLAN_LIMIT_REACHED:
					case PLAN_LIMIT_REACHED_ENTITIES:
						$this->limit ++;
						break;
					default:
						$this->error ++;
						break;
				}
			}
		} catch ( \Exception $e ) {
			throw $e;
		}
	}

	/**
	 * Add custom columns for invoice CPT metadata.
	 *
	 * This deals with which columns to show. See add_column_content() for the actual content of the columns.
	 *
	 * @param array $existing_columns The original columns.
	 *
	 * @return array With added columns by us.
	 *
	 * @since 1.0.0
	 */
	public function add_contasimple_invoice_columns( $existing_columns ) {

		$columns = array();

		$config_company_identifier_name = Contasimple_Admin::getInstance()->get_company_identifier_name();

		if ( !empty( get_option( 'woocommerce_integration-contasimple_settings' ) )
			&& !empty( get_option( 'woocommerce_integration-contasimple_settings' )['nif_custom_text'])
		) {
			$nif_label = get_option( 'woocommerce_integration-contasimple_settings' )['nif_custom_text'];
		} elseif ( !empty( $config_company_identifier_name ) ) {
			$nif_label = sprintf( __('%s / Company id', 'contasimple'), $config_company_identifier_name );
		} else {
			$nif_label = __( 'Company id', 'contasimple' );
		}

		foreach ( $existing_columns as $existing_column_key => $existing_column ) {

			// Exchange default title column by custom cs_title, because the original column would escape HTML chars and won't allow us to generate the order link URL.
			if ( 'title' !== $existing_column_key ) {
				$columns[ $existing_column_key ] = $existing_column;
			}

			// By checking a certain existing column we can inject the desired custom columns in the order that we want.
			if ( 'title' === $existing_column_key ) {
				$columns['cs_invoice_title'] = ucfirst( __( 'order', 'contasimple' ) );
				$columns['cs_number'] = __( 'Invoice Number', 'contasimple' );
				$columns['cs_customer_name'] = __( 'Company or name', 'contasimple' );
				$columns['cs_customer_nif'] = $nif_label;
				$columns['cs_total']  = __( 'Amount', 'contasimple' );
			}

			if ( 'date' === $existing_column_key ) {
				$columns[ $existing_column_key ] = $existing_column;
				$columns['cs_state']      = __( 'State', 'contasimple' );
				$columns['cs_message']    = __( 'Message', 'contasimple' );
				$columns['cs_last_sync']  = __( 'Last Sync', 'contasimple' );
				$columns['order_actions'] = __( 'Actions', 'contasimple' );
			}
		}

		return $columns;
	}

	/**
	 * Add custom column content.
	 *
	 * This deals with what to put in the custom columns.
	 *
	 * @param string $column The column content.
	 */
	public function add_column_content( $column ) {
		global $post;

		$the_order = Contasimple_WC_Backward_Compatibility::get_order_from_id( $post->order_id );

		switch ( $column ) {

			case 'cs_invoice_title':

				$pad = version_compare( WC_VERSION, '2.2', '<' ) ? '' : '#';

				// If the order was deleted or cannot be loaded, do not create a link, just display which ID it was.
				if ( ! empty( $the_order ) ) {
					$order_link = ' <a href="' . admin_url( 'post.php?post=' . absint( $post->order_id ) . '&action=edit' ) . '" class="row-title"><strong>' . $pad . esc_attr( $the_order->get_order_number() ) . '</strong></a>';
				} else {
					$order_link = ' <strong>' . $pad . esc_attr( $post->order_id ) . '</strong>';
				}

				echo '<span class="row-title">' . $order_link . '</span>'; // WPCS: XSS ok, sanitization ok.

				if ( ! version_compare( WC_VERSION, '2.2', '<' ) ) {
					echo '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details', 'woocommerce' ) . '</span></button>';
				}

				break;

			case 'cs_number':
				if ( '' !== $post->number ) {
					echo esc_attr( $post->number );
				} else {
					echo esc_attr__( 'Pending assignment', 'contasimple' );
				}

				break;

			case 'cs_customer_name':
                if ( ! empty( $the_order ) ) {
                    if ( $address = $the_order->get_formatted_billing_address() ) {
                        echo esc_html( preg_replace( '#<br\s*/?>#i', ', ', $address ) );
                    } else {
                        echo '&ndash;';
                    }
                } else {
                    echo '&ndash;';
                }

				break;

			case 'cs_customer_nif':
				$nif = Contasimple_WC_Backward_Compatibility::get_order_meta( $post->order_id, 'NIF', true );

				if ( !empty( $nif ) ) {
					echo esc_attr( $nif );
				} else {
					echo '&ndash;';
				}

				break;

			case 'cs_state':
				echo $this->get_state_html( $post->state ); // WPCS: XSS ok, sanitization ok.
				break;

			case 'cs_message':
				$contasimple_invoice_sync = new CS_Invoice_Sync( $post->ID );

				echo $this->get_message_html(); // WPCS: XSS ok, sanitization ok.

				if ( $post->ID > 0 && ! empty( $contasimple_invoice_sync ) ) {
					if ( (int)Contasimple_Admin::get_config_static()->getCompanyId() !== (int)$contasimple_invoice_sync->companyID ) {
						echo '<p>' . __( 'In a previous company', 'contasimple' ) . '</p>'; // WPCS: XSS ok, sanitization ok.
					}
				}

				break;

			case 'cs_last_sync':
				echo $this->get_date_sync_html( $post->date_sync ); // WPCS: XSS ok, sanitization ok.
				break;

			case 'cs_total':
				if ( isset( $post->amount ) && '' !== $post->amount && $this->is_synced_state( $post->state ) ) {
					if ( isset( $post->order_currency ) && '' !== $post->order_currency ) {
						// Since 1.4.2 we keep the order currency in our meta post data...
						echo esc_attr( $post->amount . $post->order_currency );
					} else {
						// ...But previous versions won't have this data, let's get it from the order as previously.
						if ( empty( $the_order ) ) {
							// If the order was deleted cannot be helped, just display the amount.
							echo esc_attr( $post->amount );
						} else {
							if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
								$currency = $the_order->get_order_currency();
							} else {
								$currency = $the_order->get_currency();
							}
							echo esc_attr( $post->amount . get_woocommerce_currency_symbol( $currency ) );
						}
					}
				}
				break;

			case 'order_actions':
				// Actually, since the last requirements changed to sync only automatically via a FIFO queue, most of those are not user anymore.
				// Still, keep for a while since this could change again.
				switch ( $post->state ) {
					case NOT_SYNC:
					case PENDING:
						echo $this->render_action_buttons( $post, array(
							'cs_stop'
						) ); // WPCS: XSS ok, sanitization ok.
						break;
					case SYNC_OK:
					case SYNCED_WITH_INVALID_VAT_NUMBER:
					case SYNCED_NATIONAL_WITHOUT_TAXES:
					case SYNCED_WITH_ROUNDING_ISSUES:
					case SYNCED_WITH_DISCREPANCY:
						echo $this->render_action_buttons( $post, array(
							'cs_view',
							'cs_pdf',
							'cs_email',
						) ); // WPCS: XSS ok, sanitization ok.
						break;
					case PAUSED:
						// $actions = array_merge( $actions, $this->get_cs_actions( $post, array( 'cs_sync' ) ) );
						break;
					case CHANGED:
						// $actions = array_merge( $actions, $this->get_cs_actions( $post, array( 'cs_view', 'cs_sync' ) ) );
						break;
					case PAYMENT_TOO_COMPLEX:
						// $actions = array_merge( $actions, $this->get_cs_actions( $post, array( 'cs_view', 'cs_stop' ) ) );
						break;
					default:
						// Valid for most ERROR states, allow both sync and stop actions.
						//$actions = array_merge($actions, $this->get_cs_actions( $post, array( 'cs_sync', 'cs_stop' ) ) );
						echo $this->render_action_buttons( $post, array(
							'cs_sync',
							'cs_stop'
						) ); // WPCS: XSS ok, sanitization ok.
				}

				break;
		}
	}

	/**
	 * Add sorting capabilities to CTP
	 *
	 * @param mixed $columns An array of columns to sort.
	 *
	 * @return mixed
	 *
	 * @since    1.0.0
	 */
	public function add_contasimple_invoice_sortable_capabilities( $columns ) {

		$columns['cs_invoice_title'] = 'cs_invoice_title';
		$columns['cs_number']        = 'cs_number';
		$columns['cs_state']         = 'cs_state';
		$columns['cs_last_sync']     = 'cs_last_sync';

		return $columns;
	}

	/**
	 * Add a filter as an HTML select control with possible invoice state values
	 *
	 * @since    1.0.0
	 */
	public function filter_contasimple_invoices() {

		global $pagenow, $typenow;

		if ( is_admin() && 'edit.php' === $pagenow && 'cs_invoice' === $typenow ) {
			$invoice_states = array(
				NOT_SYNC   => __( 'Unmarked', 'contasimple' ),
				PENDING    => __( 'Pending', 'contasimple' ),
				SYNC_OK    => __( 'Synced', 'contasimple' ),
				CHANGED    => __( 'Changed', 'contasimple' ),
				SYNC_ERROR => __( 'Error', 'contasimple' ),
			);

			// Create a custom nonce that we will use to verify when we receive the request.
			wp_nonce_field( 'cs_filter_custom_field', 'cs_filter_nonce' );
			?>

			<select name="cs_state" id="dropdown_cs_invoice_state">
				<option value="">
					<?php esc_html_e( 'All Invoice States', 'contasimple' ); ?>
				</option>

				<?php foreach ( $invoice_states as $id => $state ) : ?>
					<option
						value="<?php echo esc_attr( $id ); ?>" <?php echo esc_attr( isset( $_GET['cs_state'] ) ? selected( $id, $_GET['cs_state'], false ) : '' ); ?>>
						<?php echo esc_html( $state ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<?php
		}
	}

	/**
	 * Add 'Sync Now' and 'Create from previous orders' buttons before the list table
	 *
	 * @since    1.0.0
	 */
	public function sync_contasimple_invoices_now() {

		global $pagenow, $typenow;

		if ( is_admin() && 'edit.php' === $pagenow && 'cs_invoice' === $typenow ) {
			?>
			<div class="alignleft actions">
				<a class="button-primary" name="resume" id="edit-cs-resume-submit" href="<?php echo esc_url( wp_nonce_url( admin_url( 'edit.php?post_type=cs_invoice&sync=resume' ), 'resume' ) ); ?>">
					<span><?php esc_html_e( 'Sync pending invoices now', 'contasimple' ); ?></span>
				</a>
			</div>
			<div class="alignleft actions">
				<a class="button-primary" name="import" id="import-cs-invoices-submit" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin.php?page=create_cs_invoices' ), 'create_cs_invoices' ) ); ?>">
					<span><?php esc_html_e( 'Create from previous orders', 'contasimple' ); ?></span>
				</a>
			</div>
			<?php
		}
	}

	/**
	 * Add custom filtering options for our invoice CTP
	 *
	 * @param mixed $query The query that we intercept to add custom filtering.
	 * @return mixed
	 *
	 * @since    1.0.0
	 */
	public function admin_cs_invoice_posts_filter( $query ) {

		global $pagenow;

		// If not valid nonce just skip the filtering by our side and let WP keep its course.
		if ( ! isset( $_GET['cs_filter_nonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['cs_filter_nonce'] ), 'cs_filter_custom_field' ) ) {
			return $query;
		}

		$post_type     = filter_input( INPUT_GET, 'post_type' );
		$filter_action = filter_input( INPUT_GET, 'filter_action' );
		$state         = filter_input( INPUT_GET, 'cs_state' );

		if ( is_admin() && 'edit.php' === $pagenow && 'cs_invoice' === $post_type ) {
			if ( __( 'Filter' ) === $filter_action ) {
				if ( isset( $state ) && '' !== $state ) {

					// Since we 'condense' most error code states as only one <SELECT> 'Error' option for the end user to pick up,
					// we need to check for everything greater than 10, which is an error by our internal error handling standards.
					// See \includes\common\Service.php for defined const error codes.
					if ( intval( $state ) >= SYNC_ERROR ) {
						$condition = '>=';
					} else {
						$condition = '=';
					}

					$args = array(
						'meta_query' => array(
							array(
								'key'     => 'state',
								'type'    => 'numeric',
								'value'   => intval( $state ),
								'compare' => $condition,
							),
						),
					);

					$query->set( 'meta_query', $args );
				}
			}
		}

		return $query;
	}

	/**
	 * Output HTML to display state with a status icon and desired color.
	 *
	 * @param string $state Our desired state.
	 * @return string Resulting HTML, useful to perform DOM replace during AJAX calls.
	 *
	 * @since    1.0.0
	 */
	public static function get_state_html( $state ) {

		if ( ! empty( get_option( 'woocommerce_integration-contasimple_settings' ) ) && array_key_exists( 'show_warnings', get_option( 'woocommerce_integration-contasimple_settings' ) ) && 'yes' === get_option( 'woocommerce_integration-contasimple_settings' )['show_warnings'] ) {
			$colour = '#B89542';
		} else {
			$colour = 'Green';
		}

		switch ( $state ) {
			case NOT_SYNC:
			case PENDING:
				return '<span class="dashicons dashicons-update state"></span>';

			case SYNC_OK:
				return '<span class="dashicons dashicons-yes state" style="font-size: 2em; color:Green"></span>';

			case SYNCED_WITH_INVALID_VAT_NUMBER:
			case SYNCED_NATIONAL_WITHOUT_TAXES:
			case SYNCED_WITH_ROUNDING_ISSUES:
			case SYNCED_WITH_DISCREPANCY:
				return '<span class="dashicons dashicons-yes state" style="font-size: 2em; color:' . $colour . '"></span>';

			case CHANGED:
				return '<span class="dashicons dashicons-warning state" style="font-size: 2em; color:#B89542"></span>';

			case PAUSED:
				return '<span class="dashicons dashicons-controls-pause state" style="font-size: 2em; color:Silver"></span>';

			default:
				return '<span class="dashicons dashicons-warning state" style="font-size: 2em; color:Red"></span>';
		}
	}

	/**
	 * Output HTML to show date.
	 *
	 * @param   string $date The date.
	 * @return  string Resulting HTML, useful to perform DOM replace during AJAX calls.
	 *
	 * @since   1.0.0
	 */
	public static function get_date_sync_html( $date ) {

		return '<span class="date-sync">' . $date . '</span>';
	}

	/**
	 * Output HTML to show an explanation to the user based on internal code result
	 *
	 * @param   int $code The code as a defined constant.
	 * @return  string Resulting HTML, useful to perform DOM replace during AJAX calls.
	 *
	 * @since   1.0.0
	 */
	public static function get_message_html( $code = null, $api_message = null ) {
		global $post;

		if ( empty( $code ) ) {
			$code = !empty( $post->state ) ? $post->state : GENERIC_ERROR;
		}

		if ( empty( $api_message ) ) {
			$api_message = !empty( $post->api_message ) ? $post->api_message : '';
		}

		$msg_ok = '<span class="message" style="color:Green"> ' . __( 'Sync OK', 'contasimple' ) . '</span>';

		switch ( $code ) {
			case NOT_SYNC:
				return '<span class="message"> ' . __( 'Not marked for syncing', 'contasimple' ) . '</span>';

			case PENDING:
				return '<span class="message"> ' . __( 'Pending synchronization', 'contasimple' ) . '</span>';

			case SYNC_OK:
				return $msg_ok;

			case SYNCED_WITH_INVALID_VAT_NUMBER:
				if ( ! empty( get_option( 'woocommerce_integration-contasimple_settings' ) ) && array_key_exists( 'show_warnings', get_option( 'woocommerce_integration-contasimple_settings' ) ) && 'yes' === get_option( 'woocommerce_integration-contasimple_settings' )['show_warnings'] ) {
					return '<span class="message" style="color:#B89542"> ' . __( 'Sync OK, but invalid VAT number', 'contasimple' ) . '
								<i class="dashicons dashicons-editor-help cstooltip" style="color:#B89542" title="' . __( 'The customer has provided a VAT number. However, the VIES services could not validate the number. Therefore, the invoice has been synced marked as a national operation. In some cases, the VAT could be valid but missing from the EU database and give a false negative. If you are sure this is the case, please log into Contasimple and change the operation type to intra-community manually. You can also configure the WooCommerce European VAT number module to block registration with invalid numbers, if you suspect this is the case.', 'contasimple' ) . '">
								</i>
							 </span>';
				} else {
					return $msg_ok;
				}
				break;

			case SYNCED_NATIONAL_WITHOUT_TAXES:
				if ( ! empty( get_option( 'woocommerce_integration-contasimple_settings' ) ) && array_key_exists( 'show_warnings', get_option( 'woocommerce_integration-contasimple_settings' ) ) && 'yes' === get_option( 'woocommerce_integration-contasimple_settings' )['show_warnings'] ) {
					$vat_name = Contasimple_Admin::get_config_static()->getVatName();

					return '<span class="message" style="color:#B89542"> ' . sprintf( '%s %s', __( 'Sync OK, but missing', 'contasimple' ), $vat_name ) . '
								<i class="dashicons dashicons-editor-help cstooltip" style="color:#B89542" title="' . __( 'We detected one or more products without taxes in the order, which is usually due to a configuration error. Please check the invoice and correct it if it is a mistake, or if you really sell products without taxes you can deactivate this message in the Configuration section by changing the \'Show warnings\' switch to \'No\'', 'contasimple' ) . '"></i>
							</span>';
				} else {
					return $msg_ok;
				}
				break;

			case SYNCED_WITH_ROUNDING_ISSUES:
				if ( ! empty( get_option( 'woocommerce_integration-contasimple_settings' ) ) && array_key_exists( 'show_warnings', get_option( 'woocommerce_integration-contasimple_settings' ) ) && 'yes' === get_option( 'woocommerce_integration-contasimple_settings' )['show_warnings'] ) {
					$vat_name = Contasimple_Admin::get_config_static()->getVatName();

					return '<span class="message" style="color:#B89542"> ' . __( 'Sync OK, with minor rounding adjustments', 'contasimple' ) . '
								<i class="dashicons dashicons-editor-help cstooltip" style="color:#B89542" title="' . __( 'There was a slight difference in total amounts due to rounding differences between Contasimple and WooCommerce. To make both amounts match, a 1 cent difference has been added to the higher price concept.', 'contasimple' ) . '"></i>
							</span>';
				} else {
					return $msg_ok;
				}
				break;

			case SYNCED_WITH_DISCREPANCY:
				if ( ! empty( get_option( 'woocommerce_integration-contasimple_settings' ) ) && array_key_exists( 'show_warnings', get_option( 'woocommerce_integration-contasimple_settings' ) ) && 'yes' === get_option( 'woocommerce_integration-contasimple_settings' )['show_warnings'] ) {
						return '<span class="message" style="color:#B89542"> ' . __( 'Sync OK, with warnings', 'contasimple' ) . '
								<i class="dashicons dashicons-editor-help cstooltip" style="color:#B89542" title="' . __( 'The invoice has been synced with the total base amount and total amount after taxes as exactly reported by WooCommerce, however, Contasimple detected that when rounding all amounts to two decimals places (like most accounting systems work) the total amount should probably be one cent higher that the one reported by WooCommerce. This might happen when your products have more than two decimal digits in the base price per unit. Note that even if WooCommerce always displays product prices with two decimals, they might have more than two decimals internally in the database, and this impacts the calculations. Please try to configure WooCommerce to make all prices representable with two decimal places if you do not want to potentially be accounting for one less cent in your taxes.', 'contasimple' ) . '"></i>
							</span>';
				} else {
					return $msg_ok;
				}
				break;

			case CHANGED:
				return '<span class="message" style="color:#B89542"> ' . __( 'Changed since last sync', 'contasimple' ) . '</span>';

			case PAUSED:
				return '<span class="message" style="color:Silver"> ' . __( 'Removed from sync', 'contasimple' ) . '</span>';

			case INVOICE_REPEATED:
				return '<span class="message" style="color:Red"> ' . __( 'An invoice with the same invoice number and series already exists in Contasimple', 'contasimple' ) . '</span>';

			case TOTAL_AMOUNT_INVALID:
				return '<span class="message" style="color:Red"> ' . __( 'The invoice could not be synchronized due to problems with the validation of the amounts', 'contasimple' ) . '
				        <i class="dashicons dashicons-editor-help cstooltip" style="color:red" title="' . __( 'It could be due to rounding differences between Contasimple and WooCommerce or new features not yet supported by Contasimple, among others. The order will not be synchronized for security reasons. Please, send the log file of the attempted synchronization day of this order to the Contasimple support team.', 'contasimple' ) . '"></i>
				       </span>';

			case INVALID_MODEL:
				return '<span class="message" style="color:Red"> ' . __( 'There is an error with one or more invoice fields', 'contasimple' ) . '</span>';

			case INVALID_VAT:
				return '<span class="message" style="color:Red"> ' . __( 'The invoice applies a VAT rate that does not exist in the fiscal region configured in Contasimple', 'contasimple' ) . '</span>';

			case ENTIDADES_COUNTRY_NIF_VALIDATION_ERROR_1:
				return '<span class="message" style="color:Red"> ' . __( 'The format of the NIF entered is not correct for the user\'s country.', 'contasimple' ) . '</span>';

			case PAYMENT_SYNC_ERROR:
				return '<span class="message" style="color:Red"> ' . __( 'Invoice was synced but payments were not', 'contasimple' ) . '</span>';

			case PAYMENT_TOO_COMPLEX:
				return '<span class="message" style="color:Red"> ' . __( 'Invoice was synced but payments are too complex', 'contasimple' ) . '</span>';

			case OPERATION_TYPE_TOO_COMPLEX:
				return '<span class="message" style="color:Red"> ' . __( 'Could not determine operation type based on tax rate applied and customer/shop fiscal regions.', 'contasimple' ) . '</span>';

			case PLAN_LIMIT_REACHED:
				return '<span class="message" style="color:Red"> ' . __( 'Limit of invoices reached for the plan', 'contasimple' ) . '</span>';

			case PLAN_LIMIT_REACHED_ENTITIES:
				return '<span class="message" style="color:Red"> ' . __( 'Limit of customers reached for the plan', 'contasimple' ) . '</span>';

			case COMPANY_NIF_DISCREPANCY:
				return '<span class="message" style="color:Red"> ' . __( 'WooCommerce and Contasimple selected company NIF do not match', 'contasimple' ) . '</span>';

			case INVALID_LINES:
				return '<span class="message" style="color:Red"> ' . __( 'Invalid number of invoice lines', 'contasimple' ) . '</span>';

			case MISSING_ORDER:
				return '<span class="message" style="color:Red"> ' . __( 'The order could not be synced because it could not be loaded (Have you deleted it? Is there any technical problem?)', 'contasimple' ) . '</span>';

			case HOST_UNREACHABLE:
				return '<span class="message" style="color:Red"> ' . __( 'Could not connect with Contasimple', 'contasimple' ) . '</span>';

			case AGGREGATED_CUSTOMER_NOT_FOUND:
				return '<span class="message" style="color:Red"> ' . __( 'The <b>Multiple customers</b> to which all sales for which the customer has not specified the VAT number are attributed is not configured in Contasimple. You can configure it manually from the Contasimple website in the \'Configuration\' screen section, going first to \'Taxes\', and then selecting the desired customer in <b>Invoices to unidentified customers</b>', 'contasimple' ) . '</span>';

			case INVALID_CURRENCY:
				if ( isset( $post->cs_currency ) && '' !== $post->cs_currency ) {
					$cs_currency = $post->cs_currency;
				} else {
					$cs_currency = Contasimple_Admin::get_config_static()->getCurrencySymbol();
				}

				if ( isset( $post->order_currency ) && '' !== $post->order_currency ) {
					$order_currency = $post->order_currency;
				} elseif ( isset( $post ) ) {
					$the_order = Contasimple_WC_Backward_Compatibility::get_order_from_id( $post->order_id );
					$currency = Contasimple_WC_Backward_Compatibility::get_currency( $the_order );
					$order_currency = html_entity_decode( get_woocommerce_currency_symbol( $currency ) );
				} else {
					// We don't have the post/order at logging time but no problem we can inspect the order dump later.
					$order_currency = '';
				}

				$message = sprintf( __( 'The invoice cannot be synchronized because the order currency symbol (%s) and the one configured in Contasimple (%s) do not match. NOTE: If that is correct, you will have to synchronize the invoice manually by converting the amounts to Contasimple\'s currency, but if it is a Contasimple configuration error, remember to go to the plugin settings and click on "Update account settings" to refresh them before trying to sync the invoice again.', 'contasimple' ), $order_currency, $cs_currency );
				$message = str_replace("()", "", $message );

				return '<span class="message" style="color:Red"> ' . $message . '</span>';

			case AGGREGATED_CUSTOMER_CANNOT_CREATE:
				return '<span class="message" style="color:Red"> ' . __( 'It was not possible to configure the customer <b>Multiple customers</b> to which all sales for which the customer has not specified the VAT number are attributed. You can configure it manually from the Contasimple website in the \'Configuration\' screen section, going first to \'Taxes\', and then selecting the desired customer in <b>Invoices to unidentified customers</b>', 'contasimple' ) . '</span>';

			case CUSTOMER_CANNOT_CREATE:
				return '<span class="message" style="color:Red"> ' . __( 'The customer could not be created', 'contasimple' ) . '</span>';

			case INVOICE_INSERT_PERIOD_CLOSED:
				return '<span class="message" style="color:Red"> ' . __( 'The invoice could not be inserted because the period to which it belongs is closed', 'contasimple' ) . '</span>';

			case INVOICE_UPDATE_ORIGINAL_PERIOD_CLOSED:
				return '<span class="message" style="color:Red"> ' . __( 'The invoice could not be updated because the period to which it belongs is closed', 'contasimple' ) . '</span>';

			case INVOICE_UPDATE_NEW_PERIOD_CLOSED:
				return '<span class="message" style="color:Red"> ' . __( 'The invoice could not be updated because the selected period is closed', 'contasimple' ) . '</span>';

			case INVOICE_PAYMENT_INSERT_PERIOD_CLOSED:
				return '<span class="message" style="color:Red"> ' . __( 'The payment could not be completed because the period to which the invoice belongs is closed', 'contasimple' ) . '</span>';

			case ROUNDING_DISCREPANCY_ERROR:
				return '<span class="message" style="color:Red"> ' . __( 'The discrepancy between line prices before and after rounding is too high.', 'contasimple' ) . '</span>';

            case CANNOT_FIND_SYNC_STRATEGY:
	            return '<span class="message" style="color:Red"> ' . __( 'The invoice could not be synchronized because some of the order amounts cannot be rounded to two decimal places without causing a variation in the total amount of the invoice due to the rounding of the amount and Contasimple has not been able to automatically readjust the amounts so that the invoice has the exact same final amount as the original order. You will have to create this invoice manually in Contasimple and adjust the amounts so that the final total matches the WooCommerce order.', 'contasimple' ) . '</span>';

			case MAX_ATTEMPS_REACHED:
				return '<span class="message" style="color:Red"> ' . __( 'Maximum number of invoice syncing attempts reached.', 'contasimple' ) . '</span>';

			case INSUFFICIENT_RIGHTS_ERROR:
				return '<span class="message" style="color:Red"> ' . __( 'User permissions in the selected company do not support synchronization with WooCommerce, please use user credentials with the appropriate permissions.', 'contasimple' ) . '</span>';

			case TAXES_PER_LINE_TOO_COMPLEX:
				return '<span class="message" style="color:Red"> ' . __( 'The order has more than one tax. Contasimple only supports one tax per line. The invoice cannot be synced.', 'contasimple' ) . '</span>';

			case COUPON_TOTAL_AMOUNT_WITH_MORE_THAN_ONE_TAX:
				return '<span class="message" style="color:Red"> ' . __( 'The order could not be synced because it contains products with several different VAT rates and a fixed amount discount coupon has been applied to it. The program has not been able to reconstruct which part of the coupon applies to each product and cannot synchronize it, so it will have to be accounted for manually from the Contasimple application. To avoid this problem in the future, we recommend using percentage discount coupons, rather than fixed amount discounts.', 'contasimple' ) . '</span>';

			case IRPF_PER_LINE_NOT_ALLOWED:
				return '<span class="message" style="color:Red"> ' . __( 'The invoice cannot be synced because it applies IRPF tax to only certain items but Contasimple supports that tax only when applied to the whole invoice. It needs to be synced manually, split in two invoices, one with the items with IRPF and a second one with the items without IRPF.', 'contasimple' ) . '</span>';

			case DELETED_TAXES:
				return '<span class="message" style="color:Red"> ' . __( 'The order contains taxes that have been deleted and Contasimple could not determine the exact tax rates used. The invoice cannot be synced.', 'contasimple' ) . '</span>';

            case AUTH_DENIED:
	            return '<span class="message" style="color:Red"> ' . __( 'Contasimple login failed. Either your company status or access credentials have changed. Please re-run the configuration wizard to start working with Contasimple again.', 'contasimple' ) . '</span>';

			case NEXT_INVOICE_NUMBER_ERROR:
				return '<span class="message" style="color:Red"> ' . __( 'Next invoice number could not be retrieved', 'contasimple' ) . '</span>';

			case GET_CUSTOMER_ERROR:
				return '<span class="message" style="color:Red"> ' . __( 'The customer could not be retrieved', 'contasimple' ) . '</span>';

			case SYNC_ERROR:
				return '<span class="message" style="color:Red"> ' . __( 'Invoice Synchronization failed.', 'contasimple' ) . '</span>';

			case CANNOT_FIND_NUMBERING_SERIES:
				return '<span class="message" style="color:Red"> ' . __( 'The invoice could not be synchronized because there is no numbering series configured or either the selected series has been deleted or disabled from Contasimple. Please go to the settings page and choose a numbering series.', 'contasimple' ) . '</span>';

			case API_ERROR:
				return '<span class="message" style="color:Red"> ' . $api_message . '</span>';

			default:
				return '<span class="message" style="color:Red"> ' . __( 'Unknown error', 'contasimple' ) . '</span>';
		}
	}

	/**
	 * Set custom actions as an array of options based on invoice state.
	 *
	 * @param array $actions The actions.
	 * @return array With desired actions.
	 *
	 * @since 1.0.0
	 */
	public function add_invoice_custom_action_buttons( $actions ) {

		global $post;

		// Our custom sync state, different than WP/WC post state!
		switch ( $post->state ) {
			case NOT_SYNC:
			case PENDING:
				 //$actions = array_merge( $actions, $this->get_cs_actions( $post, array('cs_sync') ) );
				// Since 1.4.2 > Do not wait until error to remove invoices from queue.
				$actions = $this->get_cs_actions( $post, array( 'cs_stop' ) );
				break;
			case SYNC_OK:
			case SYNCED_WITH_INVALID_VAT_NUMBER:
			case SYNCED_NATIONAL_WITHOUT_TAXES:
			case SYNCED_WITH_ROUNDING_ISSUES:
			case SYNCED_WITH_DISCREPANCY:
				// $actions = array_merge( $actions, $this->get_cs_actions( $post, array('cs_view', 'cs_pdf' ) ) );
				$actions = $this->get_cs_actions( $post, array( 'cs_view', 'cs_pdf', 'cs_email' ) );
				break;
			case PAUSED:
				// $actions = array_merge($actions, $this->get_cs_actions($post, array('cs_sync')));
				break;
			case CHANGED:
				// $actions = array_merge($actions, $this->get_cs_actions($post, array('cs_view', 'cs_sync')));
				break;
			case PAYMENT_TOO_COMPLEX:
				// $actions = array_merge($actions, $this->get_cs_actions($post, array('cs_view', 'cs_stop')));
				break;
			default:
				// Valid for most ERROR states, allow both sync and stop actions.
				$actions = array_merge( $actions, $this->get_cs_actions( $post, array( 'cs_sync', 'cs_stop' ) ) );
		}

		//}

		return $actions;
	}

	/**
	 * Output HTML for the custom action buttons.
	 *
	 * Inspired by WC Custom actions.
	 *
	 * @param mixed $post The post.
	 * @param array $custom_actions The actions to iterate and render as HTML buttons.

	 * @return string Output HTML string to put into the custom column.
	 *
	 * @since 1.0.0
	 */
	public static function render_action_buttons( $post, $custom_actions ) {

		$html = '';

		if ( empty( $post ) || empty( $post->ID ) ) {
			$actions = array();
		} else {
			$actions = self::get_cs_actions( $post, $custom_actions );
		}

		foreach ( $actions as $action ) {

			if ( ! empty( esc_url( $action['url'] ) ) ) {
				$url = 'href="' . esc_url( $action['url'] ) . '"';
			} else {
				$url = '';
			}

			$the_action = esc_attr( $action['action'] );
			$name       = esc_attr( $action['name'] );

			$html .= "<p><a class='button tips $the_action' $url title='$name'></a></p>";
		}

		return $html;
	}

	/**
	 * Return custom actions as an array of parameters.
	 *
	 * Works in conjunction with render_action_buttons(), the former prepares parameters and the later formats the HTML.
	 *
	 * @param mixed $post The post.
	 * @param array $my_actions Existing actions.
	 *
	 * @return array Expanded actions array with our own actions added.
	 *
	 * @since 1.0.0
	 */
	public static function get_cs_actions( $post, $my_actions ) {

		$actions = array();
		$text_disabled   = __( 'This option is only available for the currently configured company.', 'contasimple' );
		$text_disabled_2 = __( 'Disabled for customers without NIF.', 'contasimple' );

		$contasimple_invoice_sync = new CS_Invoice_Sync( $post->ID );

		foreach ( $my_actions as $action ) {
			switch ( $action ) {

				case 'cs_sync':
					$actions['cs_sync'] = array(
						'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=cs_sync&order_id=' . $post->order_id . '&cs_invoice_id=' . $contasimple_invoice_sync->ID ), 'cs_sync' ),
						'name'      => __( 'Sync Invoice', 'contasimple' ),
						'action'    => "custom-action view cs_sync", // keep "view" class for a clean button CSS
					);
					break;

				case 'cs_stop':
					if ( !empty( $post ) && $post->ID > 0) {

						$actions['cs_stop']       = array(
							'url'    => wp_nonce_url( admin_url( 'admin-ajax.php?action=cs_stop&cs_invoice_id=' . $contasimple_invoice_sync->ID ), 'cs_stop' ),
							'name'   => __( 'Remove from sync queue', 'contasimple' ),
							'action' => 'custom-action view cs_stop',
						);
					}
					break;

				case 'cs_resume':
					/*
					$actions['cs_resume'] = array(
						'url'       => wp_nonce_url( admin_url( 'admin-ajax.php?action=cs_resume&order_id=' . $order->get_id() ), 'cs_resume' ),
						'name'      => __( 'Resume Syncing', 'contasimple' ),
						'action'    => "custom-action view cs_resume",
					);
					*/
					break;

				case 'cs_view':
					$actions['cs_view']       = array(
						'url'    => self::get_cs_invoice_link( $contasimple_invoice_sync->externalID, $contasimple_invoice_sync->companyID ),
						'name'   => __( 'View in Contasimple', 'contasimple' ),
						'action' => 'custom-action view cs_view',
					);
					break;

				case 'cs_pdf':
					$date                     = date( 'Y-m-d', strtotime( $post->post_date ) );
					$period                   = Contasimple_CPT::get_period_from_date( $date );

					if ( 0 > $contasimple_invoice_sync->amount ) {
						$tooltip   = __( 'Download Credit Note PDF', 'contasimple' );
						$css_class = 'custom-action view cs_pdf refunded';
					} else {
						$tooltip   = __( 'Download Invoice PDF', 'contasimple' );
						$css_class = 'custom-action view cs_pdf';
					}

					if ( (int) $contasimple_invoice_sync->companyID === (int) Contasimple_Admin::get_config_static()->getCompanyId() ) {
						$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=cs_pdf&number=' . $contasimple_invoice_sync->number . '&externalID=' . $contasimple_invoice_sync->externalID . '&period=' . $period ), 'cs_pdf' );
					} else {
						$url = '';
						$tooltip = $tooltip . '. ' . $text_disabled_2;
						$css_class = $css_class . ' disabled';
					}

					$actions['cs_pdf'] = array(
						'url'    => $url,
						'name'   => $tooltip,
						'action' => $css_class,
					);

					break;

				case 'cs_email':
					switch ( $contasimple_invoice_sync->mail_status ) {
						case EMAIL_NOT_SENT:
							$tooltip = __( 'Send invoice PDF to customer via email', 'contasimple' );
							$css_class  = 'custom-action view cs_email';
							break;
						case EMAIL_SENT:
							$tooltip = __( 'Resend invoice PDF to customer via email', 'contasimple' );
							$css_class  = 'custom-action view cs_email_sent';
							break;
						case EMAIL_FAILED:
							$tooltip = __( 'Try sending again invoice PDF to customer via email', 'contasimple' );
							$css_class  = 'custom-action view cs_email_failed';
							break;
					}

					if ( (int) $contasimple_invoice_sync->companyID === (int) Contasimple_Admin::get_config_static()->getCompanyId() ) {
						$url = wp_nonce_url( admin_url( 'admin-ajax.php?action=cs_email&cs_invoice_id=' . $post->ID ), 'cs_email' );

						// Wait, if no NIF on order we should block as well
                        $nif = Contasimple_WC_Backward_Compatibility::get_order_meta( $contasimple_invoice_sync->order_id, 'NIF', true );

						if ( empty( $nif ) ) {
							$tooltip   = $tooltip . '. ' . $text_disabled_2;
							$css_class = $css_class . ' disabled';
						}
					} else {
						$url       = '';
						$tooltip   = $tooltip . '. ' . $text_disabled;
						$css_class = $css_class . ' disabled';
					}

					$actions['cs_email'] = array(
						'url'    => $url,
						'name'   => $tooltip,
						'action' => $css_class,
					);

					break;
			}
		}

		return $actions;
	}

	/**
	 * Format the link to open the invoice directly in CS Website.
	 *
	 * @param int $id The invoice ID in CS.
	 * @param int $company_id The company ID that the CS invoice belongs to.
	 *
	 * @return string
	 *
	 * @since 1.0.0
	 */
	public static function get_cs_invoice_link( $id, $company_id ) {
		return URL_CS_WEB_EDIT_INVOICE . '?InvoiceID=' . (int) $id . '&action_cmpSet=' . (int) $company_id;
	}

	/**
	 * Generate a CS compliant period based on a given PS date string
	 *
	 * @param string $date A string representing a date for the entities on the WC format.
	 *
	 * @param bool $fullYear If true, will return the full year formatted as a period: Ex: 2023-T. false by default.
	 *
	 * @return string A string representing a period date, ex: '2017-1T'
	 * @throws Exception
	 */
	public static function get_period_from_date( $date, $fullYear = false ) {

		$dt        = new DateTime( $date );
		$month     = $dt->format( 'n' );
		$year      = $dt->format( 'Y' );
		$trimester = ceil( $month / 3 );

		if ( $fullYear ) {
			return $year . '-T';
		} else {
			return $year . '-' . $trimester . 'T';
		}
	}

	/**
	 * Handle PDF downloading for a certain invoice
	 *
	 * Notice: No method parameters as are all handled directly via request.
	 *
	 * @since 1.0.0
	 */
	public function cs_pdf() {
		$this->logger->logTransactionStart( 'Called: ' . __METHOD__ );

		$nonce  = filter_input( INPUT_REQUEST, '_wpnonce' );
		$action = filter_input( INPUT_REQUEST, 'action' );
		$id     = filter_input( INPUT_REQUEST, 'externalID' );
		$period = filter_input( INPUT_REQUEST, 'period' );
		$number = filter_input( INPUT_REQUEST, 'number' );

		if ( ! Contasimple_Admin::access_allowed( $nonce, $action ) ) {
			$this->logger->logTransactionEnd( 'Access not allowed. Configuration info is missing.' );
			/*
			$response = array(
				'redirect' => '',
			);
			*/
		} else {
			$this->cs = new CSService( $this->get_config() );
			$this->cs->getInvoicePDF( $id, $period, $number );
			exit();
		}
	}

	/**
	 * Remove unwanted actions from post list
	 *
	 * @param array   $actions The unwanted actions.
	 * @param WP_Post $post The post.
	 *
	 * @return array
	 *
	 * @since 1.0.0
	 */
	public function remove_actions( $actions, $post ) {

		unset( $actions['inline hide-if-no-js'] ); // "quick edit".
		unset( $actions['trash'] );
		unset( $actions['view'] );
		unset( $actions['edit'] );

		return $actions;
	}

	/**
	 * Disable bulk actions dropdown.
	 *
	 * We disabled them in favour of the automatic sync queue.
	 *
	 * @return array
	 */
	public function remove_bulk_actions_dropdown() {
		return array();
	}

	/**
	 * Allow filtering by title or meta fields
	 *
	 * @param WP_Query $query The initial query.
	 * @return The expanded query.
	 */
	public function cs_invoices_extended_search( $query ) {
		// Make sure we got a search query
		// and we're only modifying the main query.
		if ( ! ( $query->is_main_query() && ! empty( $query->get( 's' ) ) && 'cs_invoice' === $query->get( 'post_type' ) ) ) {
			return $query;
		}

		$search_term = filter_input( INPUT_GET, 's', FILTER_SANITIZE_NUMBER_INT ) ?: 0;

		$query->set('meta_query', [
			[
				'key' => 'number',
				'value' => $search_term,
				'compare' => 'LIKE',
			],
		]);

		add_filter( 'get_meta_sql', function( $sql ) {
			global $wpdb;

			static $nr = 0;
			if ( 0 != $nr++ ) {
				return $sql;
			}

			$sql['where'] = mb_eregi_replace( '^ AND', ' OR', $sql['where'] );

			return $sql;
		});

		return $query;
	}

	/**
     * Checks if current post state value is one equivalent to a synced state
     *
	 * @param int $state The value from the $post.
	 * @return bool True if it is a synced state, false otherwise.
     *
     * @since 1.7.0
	 */
	public function is_synced_state( $state ) {
	    return ( $state == SYNC_OK ||
                 $state == SYNCED_NATIONAL_WITHOUT_TAXES ||
                 $state == SYNCED_WITH_INVALID_VAT_NUMBER ||
                 $state == SYNCED_WITH_ROUNDING_ISSUES ||
				 $state == SYNCED_WITH_DISCREPANCY );
	}
}
