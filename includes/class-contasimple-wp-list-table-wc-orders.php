<?php
/**
 * This file defines a Custom WP List Table to enable bulk invoice creation from previous orders.
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

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Contasimple_WP_List_Table_WC_Orders extends WP_List_Table {

    /**
     * Custom file logger for CS actions
     *
     * @var CSLogger
     */
    protected $logger;

	public function render_before_table() {
		/*?>
		<script type="text/javascript">
			jQuery(function(e) {
				e(document.body).on("init_tooltips", function() {
					var t = {
						attribute: "data-tip",
						fadeIn: 50,
						fadeOut: 50,
						delay: 200
					};
					e(".tips, .help_tip, .woocommerce-help-tip").tipTip(t),
						e(".parent-tips").each(function() {
							e(this).closest("a, th").attr("data-tip", e(this).data("tip")).tipTip(t).css("cursor", "help")
						})
				});
				e(document.body).trigger("init_tooltips");
			});
		</script>
		<?php*/
		echo '<div class="wrap"><h1 class="wp-heading-inline">' . __('Create invoices', 'contasimple') . '</h1>';
		echo '<form id="posts-filter" method="get">';
		echo '<input type="hidden" name="page" value="create_cs_invoices" />';
		/*?>
		<table class="form-table">
			<tr valign="top" class="">
				<th scope="row" class="titledesc">
					<?php echo __('Use different mask for this batch', 'contasimple') ?>
					<?php echo wc_help_tip( __('By default, Contasimple will generate a different series to sync previous order
					invoices to avoid any possible sequence alterations. If you have not synced any invoice yet to your account
					with the masks set in the \'Configuration\' section or if you are sure that the previous orders were
					completed after the last existing invoices on Contasimple website for this series, you can disable this option to
					 keep your desired numbering format.', 'contasimple') );
					?>
				</th>
				<td class="forminp forminp-checkbox">
					<fieldset>
						<legend class="screen-reader-text"><span><?php echo __('Enable security mask to avoid altering invoice sequential order.', 'contasimple') ?></span></legend>
						<label for="cs_enable_security_mask">
							<input name="cs_enable_security_mask" id="cs_enable_security_mask" type="checkbox" class="" value="1" checked="checked"><?php echo __('Enable security mask to avoid altering invoice sequential order.', 'contasimple') ?></label> 														</fieldset>
				</td>
			</tr>
		</table>

		<?php*/

		require plugin_dir_path( dirname( __FILE__ ) ) . 'admin/partials/contasimple-confirm-manual-sync.php';
	}

	public function render_after_table() {
		echo '</form>';
		echo '</div>';
	}

	public function get_columns() {

		$columns                     = array();
		$columns['cb']               = '<input type="checkbox" />';
		//$columns['ID']               = 'ID';
		$columns['order_status']     = '<span class="status_head tips" data-tip="' . esc_attr__( 'Status', 'woocommerce' ) . '">' . esc_attr__( 'Status', 'woocommerce' ) . '</span>';
		$columns['order_title']      = __( 'Order', 'woocommerce' );
		$columns['billing_address']  = __( 'Billing', 'woocommerce' );
		//$columns['shipping_address'] = __( 'Ship to', 'woocommerce' );
		//$columns['customer_message'] = '<span class="notes_head tips" data-tip="' . esc_attr__( 'Customer message', 'woocommerce' ) . '">' . esc_attr__( 'Customer message', 'woocommerce' ) . '</span>';
		//$columns['order_notes']      = '<span class="order-notes_head tips" data-tip="' . esc_attr__( 'Order notes', 'woocommerce' ) . '">' . esc_attr__( 'Order notes', 'woocommerce' ) . '</span>';
		$columns['order_date']       = __( 'Order date', 'woocommerce' );
		$columns['completed_date']   = __( 'Date completed', 'woocommerce' );
		$columns['order_total']      = __( 'Total', 'woocommerce' );
		//$columns['order_actions']    = __( 'Actions', 'woocommerce' );

		return $columns;
	}

	function get_bulk_actions() {

		$actions = array(
			'create' => __( 'Create', 'contasimple' ),
		);
		return $actions;
	}

	function prepare_items() {

		$columns = $this->get_columns();
		$hidden = array();
		$sortable = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$per_page = $this->get_items_per_page( 'orders_per_page', 10 );
		$current_page = $this->get_pagenum();

		$table_data = $this->fetch_table_data();
		$total_items = count( $table_data );

		$table_data = array_slice( $table_data, ( ( $current_page - 1 ) * $per_page ), $per_page );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );

		$this->items = $table_data;
	}

	function column_default( $item, $column_name ) {

		global $post;

		if ( Contasimple_WC_Backward_Compatibility::is_hpos_enabled() ) {
			$the_order = wc_get_order( $item['id'] );
			$post = get_post( $item['id'] );
		} else {
			$post = get_post( $item['ID'] );
		}

		if ( empty( $the_order ) ) {
			if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
				try{
					$the_order = new WC_Order( $post->ID );
				}
				catch(\Exception $ex){
					$the_order = new WC_Order();
				}
			} else {
				$the_order = wc_get_order( $post->ID );
			}
		}

		setup_postdata( $post );

		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
			$wc_admin_shop_order = new WC_Admin_CPT_Shop_Order();
			$wc_admin_shop_order->custom_columns( $column_name );
		} else if ( version_compare( WC_VERSION, '3.3.0', '<' ) ) {
			$wc_post_types = new WC_Admin_Post_Types();
			$wc_post_types->render_shop_order_columns( $column_name );
		} else {
			if( ! class_exists( 'WC_Admin_List_Table_Orders' ) ) {
				require_once( WC_ABSPATH . 'includes/admin/list-tables/class-wc-admin-list-table-orders.php' );
			}
			if ( 'order_title' === $column_name ) {
				$column_name = 'order_number';
			}
			$wc_list_table_orders = new WC_Admin_List_Table_Orders();
			$wc_list_table_orders->render_columns( $column_name, $post->ID );
		}

		switch ( $column_name ) {

			case 'completed_date':
				if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
					$date_completed = date( 'Y-m-d', strtotime( $the_order->completed_date ) );
					printf(
						'<time datetime="%1$s" title="%2$s">%3$s</time>',
						esc_attr( date( 'c', strtotime( $the_order->completed_date ) ) ),
						esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $the_order->completed_date ) ) ),
						esc_html( date_i18n( apply_filters( 'woocommerce_admin_order_date_format', get_option( 'date_format' ) ), strtotime( $the_order->completed_date ) ) )
					);
				} else {
					$date_completed = $the_order->get_date_completed();
					if ( !empty ( $date_completed ) ) {
						printf(
							'<time datetime="%1$s" title="%2$s">%3$s</time>',
							esc_attr( $date_completed->date( 'c' ) ),
							esc_html( $date_completed->date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ) ) ),
							esc_html( $date_completed->date_i18n( apply_filters( 'woocommerce_admin_order_date_format', get_option( 'date_format' ) ) ) )
						);
					}
				}
			/*
			default:
				return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
			*/
		}
	}

	function column_cb( $item ){

		if ( Contasimple_WC_Backward_Compatibility::is_hpos_enabled() ) {
			$key = 'id';
		} else {
			$key = 'ID';
		}

		return sprintf(
			'<input type="checkbox" name="order_ids[]" value="%1$s" />',
			$item[$key]
		);
	}


	public function fetch_table_data() {
		global $wpdb;

        $this->logger = CSLogger::getDailyLogger();

		$orderby = ( isset( $_GET['orderby'] ) ) ? esc_sql( $_GET['orderby'] ) : 'completed_date';
		$sorting = ( isset( $_GET['order'] ) ) ? esc_sql( $_GET['order'] ) : 'DESC';
		$where_period = ( !empty( $_GET['ADMIN_FILTER_PERIOD'] ) ) ? " AND CONCAT(date_format(pm.meta_value, '%Y'), '-', quarter(pm.meta_value), 'T') LIKE '" . esc_sql( $_GET['ADMIN_FILTER_PERIOD'] ) . "%'" : '';

		if ( Contasimple_WC_Backward_Compatibility::is_hpos_enabled() ) {
			$post_table = $wpdb->prefix . 'wc_orders';
			$postmeta_table = $wpdb->prefix . 'wc_order_stats';
            $completed_date_col = 'date_completed';
			$order_id_col = 'order_id';
			$post_type = 'type';
			$post_status = 'p.status';
            $date_where = "";
		} else {
			$post_table = $wpdb->posts;
			$postmeta_table = $wpdb->postmeta;
            $completed_date_col = 'meta_value';
			$order_id_col = 'post_id';
			$post_type = 'post_type';
			$post_status = 'post_status';
            $date_where = "AND pm.meta_key = '_completed_date'";
		}

		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
			$join = " LEFT JOIN {$wpdb->term_relationships} AS rel ON p.ID=rel.object_ID
						LEFT JOIN {$wpdb->term_taxonomy} AS tax USING( term_taxonomy_id )
						LEFT JOIN {$wpdb->terms} AS term USING( term_id )";
			$where_status = " AND tax.taxonomy = 'shop_order_status' AND term.slug IN ('completed', 'refunded')";
		} else {
			$join = '';
			$where_status = " AND {$post_status} IN ('wc-completed', 'wc-refunded', 'wc-processing', 'wc-on-hold')";
		}

		$user_query = "SELECT p.*, pm.{$completed_date_col} as completed_date
					   FROM {$post_table} as p
					   LEFT JOIN {$postmeta_table} as pm
					   ON pm.{$order_id_col} = p.ID {$date_where}
					   {$join}
					   WHERE 1=1
					   AND p.{$post_type} in ('shop_order')
					   {$where_status}
					   {$where_period}
					   AND p.ID not in (
							SELECT m.meta_value
							FROM {$wpdb->postmeta} as m
							LEFT JOIN {$wpdb->posts} as i
							ON i.ID = m.post_id
							WHERE i.post_type in ('cs_invoice')
							AND m.meta_key= 'order_id'
					   )
					   ORDER BY {$orderby} {$sorting}";

        $this->logger->log($user_query);

		// query output_type will be an associative array with ARRAY_A.
		$query_results = $wpdb->get_results( $user_query, ARRAY_A );

		// return result array to prepare_items.
		return $query_results;

		//return wc_get_orders(array('type' => array( 'shop_order_refund', 'shop_order' ), 'status' => array( 'wc-completed' ), 'limit' => -1));
	}

	public function extra_tablenav( $which ) {
		?>
		<div class="alignleft actions">
			<?php
			if ( 'top' === $which && ! is_singular() ) {
				ob_start();

				$current_v =  isset( $_GET['ADMIN_FILTER_PERIOD'] )? $_GET['ADMIN_FILTER_PERIOD']:'';
				?>
				<select name="ADMIN_FILTER_PERIOD">
					<option value=""><?php esc_html_e( 'All fiscal periods', 'contasimple' ); ?></option>
					<?php
					for ( $i = 0; $i < 10; $i++ ) {
						$year = date( 'Y' ) - $i;
						printf(
							'<option value="%s"%s>%s</option>',
							$year,
							$year == $current_v? ' selected="selected"':'',
							$year
						);
						for ( $j = 4; $j > 0; $j-- ) {
							$quarter = $year . '-' . $j . 'T';
							printf(
								'<option value="%s"%s>%s</option>',
								$quarter,
								$quarter == $current_v? ' selected="selected"':'',
								$quarter
							);
						}
					}
					?>
				</select>
				<?php
				/**
				 * Fires before the Filter button on the Posts and Pages list tables.
				 *
				 * The Filter button allows sorting by date and/or category on the
				 * Posts list table, and sorting by date on the Pages list table.
				 *
				 * @since 2.1.0
				 * @since 4.4.0 The `$post_type` parameter was added.
				 * @since 4.6.0 The `$which` parameter was added.
				 *
				 * @param string $post_type The post type slug.
				 * @param string $which     The location of the extra table nav markup:
				 *                          'top' or 'bottom' for WP_Posts_List_Table,
				 *                          'bar' for WP_Media_List_Table.
				 */
				do_action( 'restrict_manage_posts', $this->screen->post_type, $which );

				$output = ob_get_clean();

				if ( ! empty( $output ) ) {
					echo $output;
					submit_button( __( 'Filter' ), '', 'filter_action', false, array( 'id' => 'post-query-submit' ) );
				}
			}

			if ( ! version_compare( WC_VERSION, '2.7', '<' ) ) {
				if ( $this->is_trash && current_user_can( get_post_type_object( $this->screen->post_type )->cap->edit_others_posts ) && $this->has_items() ) {
					submit_button( __( 'Empty Trash' ), 'apply', 'delete_all', false );
				}
			}
			?>
		</div>
		<?php
		/**
		 * Fires immediately following the closing "actions" div in the tablenav for the posts
		 * list table.
		 *
		 * @since 4.4.0
		 *
		 * @param string $which The location of the extra table nav markup: 'top' or 'bottom'.
		 */
		do_action( 'manage_posts_extra_tablenav', $which );

	}

	/**
	 * Override bulk actions to add a 'cs' name attribute to the 'Apply' button.
	 *
	 * @param string $which
	 *
	 * @since 1.16
	 */
	protected function bulk_actions( $which = '' ) {
		if ( is_null( $this->_actions ) ) {
			$this->_actions = $this->get_bulk_actions();

			/**
			 * Filters the items in the bulk actions menu of the list table.
			 *
			 * The dynamic portion of the hook name, `$this->screen->id`, refers
			 * to the ID of the current screen.
			 *
			 * @since 3.1.0
			 * @since 5.6.0 A bulk action can now contain an array of options in order to create an optgroup.
			 *
			 * @param array $actions An array of the available bulk actions.
			 */
			$this->_actions = apply_filters( "bulk_actions-{$this->screen->id}", $this->_actions ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores

			$two = '';
		} else {
			$two = '2';
		}

		if ( empty( $this->_actions ) ) {
			return;
		}

		echo '<label for="bulk-action-selector-' . esc_attr( $which ) . '" class="screen-reader-text">' . __( 'Select bulk action' ) . '</label>';
		echo '<select name="action' . $two . '" id="bulk-action-selector-' . esc_attr( $which ) . "\">\n";
		echo '<option value="-1">' . __( 'Bulk actions' ) . "</option>\n";

		foreach ( $this->_actions as $key => $value ) {
			if ( is_array( $value ) ) {
				echo "\t" . '<optgroup label="' . esc_attr( $key ) . '">' . "\n";

				foreach ( $value as $name => $title ) {
					$class = ( 'edit' === $name ) ? ' class="hide-if-no-js"' : '';

					echo "\t\t" . '<option value="' . esc_attr( $name ) . '"' . $class . '>' . $title . "</option>\n";
				}
				echo "\t" . "</optgroup>\n";
			} else {
				$class = ( 'edit' === $key ) ? ' class="hide-if-no-js"' : '';

				echo "\t" . '<option value="' . esc_attr( $key ) . '"' . $class . '>' . $value . "</option>\n";
			}
		}

		echo "</select>\n";

		submit_button( __( 'Apply' ), 'action', 'cs', false, array( 'id' => "doaction$two" ) );
		echo "\n";
	}
}
