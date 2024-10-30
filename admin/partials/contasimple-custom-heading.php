<?php

/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       https://wordpress.org/plugins/contasimple/
 * @since      1.0.0
 *
 * @package    contasimple
 * @subpackage contasimple/admin/partials
 * @author     Contasimple S.L. <soporte@contasimple.com>
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( empty( $data['icon'] ) ) {
	$data['icon'] = 'dashicons-admin-generic';
}

if ( empty( $data['title'] ) ) {
	$data['title'] = '';
}

?>

<h3><i class="dashicons <?php echo esc_attr($data['icon']); ?>"></i> <?php echo esc_attr($data['title']); ?></h3>
