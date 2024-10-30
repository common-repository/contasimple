<?php
/**
 * Legacy functions and future functions that might not exist in previous versions
 *
 * @link       http://www.contasimple.com
 * @since      1.0.0
 *
 * @package    contasimple
 * @subpackage contasimple/includes
 * @author     Contasimple S.L. <soporte@contasimple.com>
 */

if ( ! function_exists( '_wp_json_prepare_data' ) ) {

	/**
	 * Prepares response data to be serialized to JSON.
	 *
	 * This supports the JsonSerializable interface for PHP 5.2-5.3 as well.
	 *
	 * @ignore
	 * @since 4.4.0
	 * @access private
	 *
	 * @param mixed $data Native representation.
	 * @return bool|int|float|null|string|array Data ready for `json_encode()`.
	 */
	function _wp_json_prepare_data( $data ) {
		if ( ! defined( 'WP_JSON_SERIALIZE_COMPATIBLE' ) || WP_JSON_SERIALIZE_COMPATIBLE === false ) {
			return $data;
		}

		switch ( gettype( $data ) ) {
			case 'boolean':
			case 'integer':
			case 'double':
			case 'string':
			case 'NULL':
				// These values can be passed through.
				return $data;

			case 'array':
				// Arrays must be mapped in case they also return objects.
				return array_map( '_wp_json_prepare_data', $data );

			case 'object':
				// If this is an incomplete object (__PHP_Incomplete_Class), bail.
				if ( ! is_object( $data ) ) {
					return null;
				}

				if ( $data instanceof JsonSerializable ) {
					$data = $data->jsonSerialize();
				} else {
					$data = get_object_vars( $data );
				}

				// Now, pass the array (or whatever was returned from jsonSerialize through).
				return _wp_json_prepare_data( $data );

			default:
				return null;
		}
	}
}

if ( ! function_exists( '_wp_json_sanity_check' ) ) {
	/**
	 * Perform sanity checks on data that shall be encoded to JSON.
	 *
	 * @ignore
	 * @since 4.1.0
	 * @access private
	 *
	 * @see wp_json_encode()
	 *
	 * @param mixed $data  Variable (usually an array or object) to encode as JSON.
	 * @param int   $depth Maximum depth to walk through $data. Must be greater than 0.
	 * @return mixed The sanitized data that shall be encoded to JSON.
	 */
	function _wp_json_sanity_check( $data, $depth ) {
		if ( $depth < 0 ) {
			throw new Exception( 'Reached depth limit' );
		}

		if ( is_array( $data ) ) {
			$output = array();
			foreach ( $data as $id => $el ) {
				// Don't forget to sanitize the ID!
				if ( is_string( $id ) ) {
					$clean_id = _wp_json_convert_string( $id );
				} else {
					$clean_id = $id;
				}

				// Check the element type, so that we're only recursing if we really have to.
				if ( is_array( $el ) || is_object( $el ) ) {
					$output[ $clean_id ] = _wp_json_sanity_check( $el, $depth - 1 );
				} elseif ( is_string( $el ) ) {
					$output[ $clean_id ] = _wp_json_convert_string( $el );
				} else {
					$output[ $clean_id ] = $el;
				}
			}
		} elseif ( is_object( $data ) ) {
			$output = new stdClass;
			foreach ( $data as $id => $el ) {
				if ( is_string( $id ) ) {
					$clean_id = _wp_json_convert_string( $id );
				} else {
					$clean_id = $id;
				}

				if ( is_array( $el ) || is_object( $el ) ) {
					$output->$clean_id = _wp_json_sanity_check( $el, $depth - 1 );
				} elseif ( is_string( $el ) ) {
					$output->$clean_id = _wp_json_convert_string( $el );
				} else {
					$output->$clean_id = $el;
				}
			}
		} elseif ( is_string( $data ) ) {
			return _wp_json_convert_string( $data );
		} else {
			return $data;
		}

		return $output;
	}
}

if ( ! function_exists( 'wp_json_encode' ) ) {
	/**
	 * Encode a variable into JSON, with some sanity checks.
	 *
	 * @since 4.1.0
	 *
	 * @param mixed $data    Variable (usually an array or object) to encode as JSON.
	 * @param int   $options Optional. Options to be passed to json_encode(). Default 0.
	 * @param int   $depth   Optional. Maximum depth to walk through $data. Must be
	 *                       greater than 0. Default 512.
	 * @return string|false The JSON encoded string, or false if it cannot be encoded.
	 */
	function wp_json_encode( $data, $options = 0, $depth = 512 ) {
		/*
		 * json_encode() has had extra params added over the years.
		 * $options was added in 5.3, and $depth in 5.5.
		 * We need to make sure we call it with the correct arguments.
		 */
		if ( version_compare( PHP_VERSION, '5.5', '>=' ) ) {
			$args = array( $data, $options, $depth );
		} elseif ( version_compare( PHP_VERSION, '5.3', '>=' ) ) {
			$args = array( $data, $options );
		} else {
			$args = array( $data );
		}

		// Prepare the data for JSON serialization.
		$args[0] = _wp_json_prepare_data( $data );

		$json = @call_user_func_array( 'json_encode', $args );

		// If json_encode() was successful, no need to do more sanity checking.
		// ... unless we're in an old version of PHP, and json_encode() returned
		// a string containing 'null'. Then we need to do more sanity checking.
		if ( false !== $json && ( version_compare( PHP_VERSION, '5.5', '>=' ) || false === strpos( $json, 'null' ) ) ) {
			return $json;
		}

		try {
			$args[0] = _wp_json_sanity_check( $data, $depth );
		} catch ( Exception $e ) {
			return false;
		}

		return call_user_func_array( 'json_encode', $args );
	}
}

/**
 * A class that holds helper methods to retrieve WC Order, Product and other features in a unified way to avoid
 * doing if/else WC_VERSION everywhere in the code.
 *
 * @since 1.4.0
 */
class Contasimple_WC_Backward_Compatibility {

	static function get_currency( $order ) {
		if ( empty( $order ) ) return false;
		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			return $order->get_order_currency();
		} else {
			return $order->get_currency();
		}
	}

	static function get_billing_country ( $order ) {
		if ( empty( $order ) ) return false;
		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			return  $order->billing_country;
		} else {
			return $order->get_billing_country();
		}
	}

	static function get_billing_state ( $order ) {
		if ( empty( $order ) ) return false;
		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			return  $order->billing_state;
		} else {
			return $order->get_billing_state();
		}
	}

	static function get_id ( $order ) {
		if ( empty( $order ) ) return false;
		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			return $order->id;
		} else {
			return $order->get_id();
		}
	}

	static function get_order_completed_date ( $order, $format = 'Y-m-d H:i:s' ) {
		if ( empty( $order ) ) return false;
		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			if ( empty( $order->completed_date ) ) {
				if ( empty( $order->modified_date ) ) {
					return false;
				} else {
					// Not really sure if this is Ok but will save sync of some orders whose data seems OK...
					return $order->modified_date;
				}
			} else {
				return $order->completed_date;
			}
		} else {
			if ( empty( $order->get_date_completed() ) ) {
				if ( empty( $order->get_date_modified() ) ) {
					return false;
				} else {
					// @since 1.11.0 we allow syncing orders with the 'Processing' status, they won't have
					// an order_completed_date yet, use the modified date instead.
					return $order->get_date_modified()->date_i18n( $format );
				}
			} else {
				return $order->get_date_completed()->date_i18n( $format );
			}
		}
	}

	static function get_order_status ( $order ) {
		if ( empty( $order ) ) return false;
		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			return $order->status;
		} else {
			return $order->get_status();
		}
	}

	static function get_order_item_tax_amount( $item ) {
		if ( empty( $item ) ) return false;
		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			return floatval($item['line_tax']);
		} else {
			return floatval($item['total_tax']);
		}
	}

	static function get_product_from_order_item ( $item ) {
		if ( empty( $item ) ) return false;
		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
			$productFactory = new WC_Product_Factory();
			return $productFactory->get_product( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
		} else {
			return wc_get_product( $item['variation_id'] ? $item['variation_id'] : $item['product_id'] );
		}
	}

	static function get_order_item_taxes ( $item, $product, $order, $attempt = 1 ) {
		if ( empty( $item ) ) return false;
		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {

			$taxes = array();
			$total_tax = $item['line_tax'];
			$_tax = new WC_Tax();
			$tmp_taxes = $_tax->get_rates($product->get_tax_class());

			foreach ( $tmp_taxes as $tax_id => $tax ) {
				$taxes[$tax_id] = $total_tax;
			}
		} elseif ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			$item_taxes = $item['line_tax_data'];
			if ( is_serialized( $item_taxes ) ) {
			    // TODO total/subtotal
				$taxes = maybe_unserialize( $item_taxes )['total'];
			}
		} else {
		    $discounts = Contasimple_WC_Backward_Compatibility::get_order_discounts( $order );
            $discount_percentage = Contasimple_WC_Backward_Compatibility::calculate_item_discount_percentage( $discounts, $item->get_id() );

            if ( $discount_percentage > 0 || $attempt > 1 || version_compare( WC_VERSION, '3.2', '<' )) {
                // We need the taxes with discount already applied
                $taxes = $item->get_taxes()['total'];
            } else {
                // Since we might add an additional line as an amount discount, we have to keep the full price here
                // so that it can be deducted later on...
                $taxes = $item->get_taxes()['subtotal'];
            }
		}

		return $taxes;
	}

	static function get_order_shipping_item_taxes( $item, $order ) {
		if ( empty( $item ) ) return false;

		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {

			// WC < 2.2 offers poor support to get tax rates bases on shipping methods as items, so, given that almost everybody
			// has dropped support already, we will find a compromise by assuming that only 1 shipping method is used
			// for each order and we will get the total rate + amount used as one CS invoice line. If more than 1 method
			// is detected, throw an error. It should not be a typical scenario.

			$shipping_taxes = array();
			$shipping_items = $order->get_items( 'shipping');

			if ( count( $shipping_items ) > 1 ) {
				// Throw and handle exception not more than 1 tax supported
				throw new \Exception( '', TAXES_PER_LINE_TOO_COMPLEX );
			}

			$shipping = array_shift( $shipping_items );
			$taxes = $order->get_taxes();

			$shipping_taxes_found = 0;
			$shipping_tax = 0;
			$tax_rate = 0;
			$shipping_total = $order->get_total_shipping();

			foreach ( $taxes as $tax ) {
				if ( isset( $tax['shipping_tax_amount'] ) ) {
					$shipping_taxes_found++;
					$shipping_tax = $tax['shipping_tax_amount'];
					$tax_rate = self::get_tax_rate( $tax['rate_id'] );
				}
			}

			if ( $shipping_tax != $order->get_shipping_tax() && 1 < $shipping_taxes_found ) {
				throw new \Exception( '', TAXES_PER_LINE_TOO_COMPLEX );
			}

			$shipping_taxes[$tax_rate] = $shipping_tax;

		} else if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			// There's still some differences between up to 2.6 and 3.0+ but not difficult to deal with.
			$shipping_taxes   = $item['taxes'];
			if( is_serialized( $shipping_taxes )) {
				$shipping_taxes = maybe_unserialize($shipping_taxes);
			}
		} else {
			$shipping_taxes = $item->get_taxes()['total'];
		}

		return $shipping_taxes;
	}

	static function get_order_shipping_item_amount( $item ) {
		if ( empty( $item ) ) return false;

		// There's still some differences between up to 2.6 and 3.0+ but not difficult to deal with.
		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			$unit_amount = $item['cost'];
		} else {
			$unit_amount = $item->get_total();
		}

		return $unit_amount;
	}

	static function get_tax_rate( $id ) {
		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
			global $wpdb;
			$tax = $wpdb->get_var( $wpdb->prepare( "SELECT tax_rate FROM {$wpdb->prefix}woocommerce_tax_rates WHERE tax_rate_id = %s", $id ) );
		} elseif ( version_compare( WC_VERSION, '2.5', '<' ) ) {
			$tax = WC_Tax::get_rate_percent( $id ); // TODO check
		} else {
			$tax = WC_Tax::_get_tax_rate( $id )['tax_rate'];
		}

		return round( $tax, CS_MAX_DECIMALS, PHP_ROUND_HALF_UP );
	}

	static function get_order_data( $order ) {
		if ( empty( $order ) ) return false;

		if ( version_compare( WC_VERSION, '3', '<' ) ) {
			// Only for WC up to 2.6. Use get_order_data_in_array() for WC 3+
			$order_data = get_post_meta($order->id);

			foreach( $order->get_items() as $item_key => $item_values ) {
				$order_data['lines'][$item_key] = $item_values;
			}

			return $order_data;

		} else {
			// Starting WC 3.0 the WC_Order inner object properties are not publicly accessible anymore
			// and our json_encode would ignore them, bringing less debug info when we call the logger.
			// Extract them with their getters before so that we can normally work with them as previously.
			$order_data = $order->get_data();

			foreach( $order->get_items() as $item_key => $item_values ) {
				$order_data['line_items'][$item_key] = $item_values->get_data();
			}

			foreach( $order->get_taxes() as $item_key => $item_values ) {
				$order_data['tax_lines'][$item_key] = $item_values->get_data();
			}

			foreach( $order->get_items('shipping') as $item_key => $item_values ) {
				$order_data['shipping_lines'][$item_key] = $item_values->get_data();
			}

			foreach( $order->get_items('fee') as $item_key => $item_values ) {
				$order_data['fee_lines'][$item_key] = $item_values->get_data();
			}

			if ( version_compare( WC_VERSION, '3.7', '<' ) ) {
				$coupons = $order->get_used_coupons();
			} else {
				$coupons = $order->get_coupon_codes();
			}

			foreach( $coupons as $item_key => $item_values ) {
				if(is_string($item_values)){
					$order_data['coupon_lines'][$item_key] = $item_values;
				}
				else{
					$order_data['coupon_lines'][$item_key] = $item_values->get_data();
				}
			}

			return $order_data;
		}
	}

	static function get_order_payment_method( $order ) {
		if ( empty( $order ) ) return false;

		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			return $order->payment_method;
		} else {
			return $order->get_payment_method();
		}
	}

	static function get_order_from_id ( $id ) {
		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
			try {
				$order = new WC_Order( $id );
			} catch(\Exception $ex) {
				$order = false; // Make it do the same as the new wc_get_order() from WC 2.2+
			}
		} else {
			$order = wc_get_order( $id );
		}

		return $order;
	}

	static function get_order_item_type( $item ) {
		if ( empty( $item ) ) return false;
		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			return $item['type'];
		} else {
			return $item->get_type();
		}
	}

	static function get_order_item_name( $item ) {
		if ( empty( $item ) ) return false;
		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			return $item['name'];
		} else {
			return $item->get_name();
		}
	}

	static function get_product_sku( $product ) {
		if ( empty( $product ) ) return false;
		if ( is_array( $product ) && ! is_object( $product ) ) {
			if ( ! empty ( $product['sku'] ) ) {
				return $product['sku'];
			} else {
				return false;
			}
		} else {
			return $product->get_sku();
		}
	}

	/**
	 * Returns a multiplier that can be used for amounts to change invoice sign at will.
	 * Useful to multiply calculated amounts and qty to send to CS invoice to change the sign.
	 * Ie: if it's a refund, multiply qty per returned -1 to obtain -3 items to sync.
	 *
	 * @param $order The woocommerce order
	 * @param $args Can be used if hook refund_created is triggered.
	 *
	 * @return bool|int Returns a 1 if the order is a regular invoice, or a -1 if the invoice is a full refund or a partial refund.
	 */
	static function get_invoice_sign_from_order( $order, $args = null ) {
		if ( empty( $order ) ) return false;

		if ( !empty( $args ) && is_serialized( $args ) ) {
			$args = unserialize( $args );
		}

		// No support for partial refunds in <2.2 so just check status or args
		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
			$sign = 'refunded' === self::get_order_status( $order )
			        || ! empty( $args ) ?
				- 1
				: 1;
		} else {
			$sign = 'refunded' === self::get_order_status( $order )
			        || ! empty( $args )
					|| count( $order->get_refunds() ) > 0 ?
				- 1
				: 1;
		}

		return $sign;
	}

	/**
	 * Calculate tax rate from total and tax amounts
	 * Use this only if you do not have info about the tax rate used.
	 *
	 * @param $item
	 *
	 * @return float|int
	 */
	static function calculate_tax_rate_from_amounts( $item, $tax_amount ) {
		if ( empty( $item ) ) return 0;

		$tax_rate = 0;

		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
			//TODO
		} else if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			if ($item['type'] == 'line_item') {
				$tax_rate = $tax_amount / $item['line_total'] * 100;
			}
			if ($item['type'] == 'shipping') {
				$tax_rate = $tax_amount / $item['cost'] * 100;
			}
			if ($item['type'] == 'coupon') {
                $tax_rate = $tax_amount / $item['discount'] * 100;
            }
		} else {
            if ($item['type'] == 'coupon') {
                $tax_rate = $tax_amount / $item->get_discount() * 100;
            } else {
                $tax_rate = $tax_amount / $item->get_total() * 100;
            }
		}

		return round( $tax_rate, CS_MAX_DECIMALS, PHP_ROUND_HALF_UP );
	}

	static function get_order_fee_item_taxes( $item ) {
		// TODO previous versions
		return $item->get_taxes()['total'];
	}

	static function get_order_coupon_item_taxes ( $item, $order ) {
        if ( empty( $item ) || empty( $order) ) return false;

		$taxes = array();

        if ( version_compare( WC_VERSION, '3.2', '<' ) ) {
            // Does not matter, due to poor support we are not going to use it.
        } else {
            $coupon = new WC_Coupon( $item->get_code() );

            // If it is a percentage coupon, skip this, as we already handled this in the line_item case.
            if ( $coupon->get_discount_type() !== 'percent' ) {

            	// Otherwise we need the tax class properties but the coupon_item does not provide this.
	            // Since guessing the rate with formula is prone to decimal error, we will try to look for a valid
	            // tax rate in the order object.
                if ($item->get_discount() != 0) {
                    $tax_rate = $item->get_discount_tax() / $item->get_discount() * 100; // 'ie: 21.06'
                } else {
                    $tax_rate = 0;
                }

	            $found_matching_tax_rate = false;

	            foreach( $order->get_taxes() as $order_tax ) {
	            	// If taxes look very similar, most likely it's a rounding issue and we can pick the WC order tax ID.
		            if ( method_exists( $order_tax, 'get_rate_percent' ) ) {
		            	$rate_percent = $order_tax->get_rate_percent();
		            } else { // Fix for WC 3.5.x
			            $rate_percent = WC_Tax::_get_tax_rate( $order_tax->get_rate_id() )['tax_rate'];
		            }
					if ( abs( $rate_percent - $tax_rate ) < 1 ) {
						$taxes[$order_tax->get_rate_id()] = $item->get_discount_tax();
						$found_matching_tax_rate = true;
						break;
					}
	            }

	            // If not found just add it with the 0 array key, the plugin will try to deal with this later.
	            if (!$found_matching_tax_rate) {
		            $taxes[] = $item->get_discount_tax();
	            }
            }
        }

        return $taxes;
    }

	static function get_customer_full_name( $order ) {
		if ( empty( $order ) ) return '';

		if ( version_compare( WC_VERSION, '2.7', '<' ) ) {
			$billing_first_name = $order->billing_first_name;
			$billing_last_name = $order->billing_last_name;
		} else {
			$billing_first_name = $order->get_billing_first_name();
			$billing_last_name = $order->get_billing_last_name();
		}

		return trim( $billing_first_name . ' ' . $billing_last_name );
	}

	static function get_order_discounts( $order ) {

        $discounts = new WC_Discounts( $order );

        foreach ( $order->get_items( 'coupon' ) as $coupon_item ) {
            $coupon_code = $coupon_item->get_code();
            $coupon_id   = wc_get_coupon_id_by_code( $coupon_code );

            // If we have a coupon ID (loaded via wc_get_coupon_id_by_code) we can simply load the new coupon object using the ID.
            if ( $coupon_id ) {
                $coupon_object = new WC_Coupon( $coupon_id );

            } else {

                // If we do not have a coupon ID (was it virtual? has it been deleted?) we must create a temporary coupon using what data we have stored during checkout.
                $coupon_object = new WC_Coupon();
                $coupon_object->set_props( (array) $coupon_item->get_meta( 'coupon_data', true ) );
                $coupon_object->set_code( $coupon_code );
                $coupon_object->set_virtual( true );

                // If there is no coupon amount (maybe dynamic?), set it to the given **discount** amount so the coupon's same value is applied.
                if ( ! $coupon_object->get_amount() ) {

                    // If the order originally had prices including tax, remove the discount + discount tax.
                    if ( $order->get_prices_include_tax() ) {
                        $coupon_object->set_amount( $coupon_item->get_discount() + $coupon_item->get_discount_tax() );
                    } else {
                        $coupon_object->set_amount( $coupon_item->get_discount() );
                    }
                    $coupon_object->set_discount_type( 'fixed_cart' );
                }
            }

            if ( $coupon_object ) {
                $discounts->apply_coupon( $coupon_object, false );
            }
        }

        return $discounts;
    }

    public static function calculate_item_discount_percentage( $discounts, $item_id ) {

        $discount_percentage = 0;

        // Accumulate percentages if it is this type.
        foreach ( $discounts->get_discounts() as $key => $discount ) {

            if ( array_key_exists( $item_id, $discount ) && $discount[$item_id] > 0  ) {

                $coupon = new WC_Coupon( $key );

                if ( $coupon->get_discount_type() == 'percent') {
                    $discount_percentage += $coupon->get_amount();
                }
            }
        }

        return $discount_percentage;
    }

    public static function get_order_used_coupon_codes( $order ) {
        if ( empty( $order ) ) return array();

        if ( version_compare( WC_VERSION, '3.7', '<' ) ) {
            $coupons = $order->get_used_coupons();
        } else {
            $coupons = $order->get_coupon_codes();
        }

        return $coupons;
    }

	/**
	 * Checks whether this is a return and is a partial return.
	 *
	 * @param $order The order
	 * @return bool True if this is a partial refund, false otherwise.
	 *
	 * @since 1.7.0
	 */
    public static function is_partial_refund($order, $order_refund_id ) {

    	if ( empty( $order_refund_id ) ) {
		    $is_partial_refund = false;

	    } else {
		    if ( version_compare( WC_VERSION, '2.2', '<' ) ) {
			    $is_partial_refund = false;

		    } elseif ( version_compare( WC_VERSION, '3', '<' ) ) {
			    $is_partial_refund = false;
			    $refunds = $order->get_refunds();

			    foreach ( $refunds as $refund ) {
				    if ( $refund->id == $order_refund_id && $refund->get_refund_amount() < $order->get_total() ) {
					    $is_partial_refund = true;
					    break;
				    }
			    }

		    } else {
			    $refunds = $order->get_refunds();
			    $is_partial_refund = false;

			    foreach ( $refunds as $refund ) {
				    if ( $refund->get_id() == $order_refund_id && $refund->get_amount() < $order->get_total() ) {
					    $is_partial_refund = true;
					    break;
				    }
			    }
		    }
	    }

	    return $is_partial_refund;
    }

	/**
	 * Returns the date the refund was created.
	 * @since 1.7.0
	 *
	 * @param $order
	 * @param $order_refund_id
	 * @return bool
	 */
	public static function get_order_refunded_date( $order, $order_refund_id, $format = 'Y-m-d H:i:s' ) {
		if ( empty( $order_refund_id ) ) return false;

		if ( version_compare( WC_VERSION, '2.2', '<' ) ) {

			// We should see how prior to 2.2 the date was retrieved but for now we will just use the
			// completed date.
			if ( empty( $order ) || empty( $order->completed_date ) ) {
				if ( empty( $order ) || empty( $order->modified_date ) ) {
					return false;
				} else {
					return $order->modified_date;
				}
			} else {
				return $order->completed_date;
			}

		} elseif ( version_compare( WC_VERSION, '3', '<' ) ) {
			$refunds = $order->get_refunds();

			foreach ( $refunds as $refund ) {
				if ( $refund->id == $order_refund_id ) {
					return $refund->date;
					break;
				}
			}

		} else {
			$refunds = $order->get_refunds();

			foreach ( $refunds as $refund ) {
				if ( $refund->get_id() == $order_refund_id ) {
					return $refund->get_date_created()->date_i18n( $format );
					break;
				}
			}
		}

		return false;
	}

	public static function get_order_item( $order, $order_item_id ) {
		if ( version_compare( WC_VERSION, '3', '<' ) ) {
			foreach ( $order->get_items( 'line_item' ) as $key => $item ) {
				if ( $order_item_id == $key ) {
					return $item;
				}
			}
			return false;
		} else {
			return $order->get_item( $order_item_id );
		}
	}

	/**
	 * Checks if HPOS is enabled.
	 *
	 * @since 1.26
	 *
	 * @return bool true if it is enabled, false if it is not or WC version does not support it yet.
	 */
	public static function is_hpos_enabled() {
		if ( version_compare( get_option( 'woocommerce_version' ), '7.1.0' ) < 0 ) {
			return false;
		}

		if ( Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			return true;
		}

		return false;
	}

	/**
	 * Retrieves WooCommerce order metadata, regardless of old post_meta or new HPOS table.
	 *
	 * @param int    $order_id order id.
	 * @param string $key meta key.
	 * @param bool   $single get single or all meta.
	 * @param object $order order object
	 * @return array
	 *
	 * @since 1.26
	 */
	public static function get_order_meta( $order_id, $key = '', $single = false, $order = null ) {
		$data = array();
		if ( self::is_hpos_enabled() ) {
			if ( ! $order ) {
				$order = wc_get_order( $order_id );
			}
			if ( ! $order ) {
				return $data;
			}
			if ( '' !== $key ) {
				return $order->get_meta( $key );
			}

			$meta_data = $order->get_meta_data();
			$data = array();

			foreach ( $meta_data  as $key => $meta_value ) {
				$meta_key            = $meta_value->key;
				$data[ $meta_key ][] = $meta_value->value;
			}

		} else {
			$data  = get_post_meta( $order_id, $key, $single );
		}

		return $data;
	}

	/**
	 * Updates WooCommerce order metadata, regardless of old post_meta or new HPOS table.
	 *
	 * @param $order_id
	 * @param $key
	 * @param $value
	 * @param $order
	 * @param $save
	 *
	 * @since 1.26
	 *
	 * @return array|bool|int
	 */
	public static function update_order_meta( $order_id, $key, $value = false, $order = null, $save = false ) {
		$data = array();
		if ( self::is_hpos_enabled() ) {
			if ( ! $order ) {
				$order = wc_get_order( $order_id );
			}
			if ( ! $order ) {
				return false;
			}

			$order->update_meta_data( $key, $value );

			if ( $save ) {
				$order->save();
			}

		} else {
			$data  = update_post_meta( $order_id, $key, $value );
		}

		return $data;
	}
}
