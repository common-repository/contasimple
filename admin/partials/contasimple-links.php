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

if (!empty($data['custom_attributes'])) { ?>
	<ul style='<?php echo esc_attr($data['css']) ?>'>
	<?php foreach ($data['custom_attributes'] as $key => $value)
	{ ?>
		<li>
			<a href='<?php echo esc_url($value) ?>' target='_blank'><?php echo esc_attr($key) ?></a>
		</li>
	<?php } ?>
	</ul> <?php
}
?>