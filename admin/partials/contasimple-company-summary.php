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

?>

<!-- Weird, WP seems to be removing the first encountered form element, but not the rest. So we add a useless form so that the following ones can work... -->
<form id="handle-wp-parse-error" action="" method="post" class="bootstrap-cs defaultForm form-horizontal">
</form>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div class="bootstrap-cs panel">
	</br>
	<h3><i class="icon-AdminContasimple"></i> <span><?php _e('Account Information', 'contasimple'); ?></span></h3>
	</br>
	<p>
		<?php _e('You are working with the following credentials:', 'contasimple'); ?>
	</p>
	</br>
	<form id="form-cs-summary" method="post" class="defaultForm form-horizontal" enctype="multipart/form-data">
        <!-- WC needs some stuff when POSTs to its own generated settings form, if we want to mimick it, we will need to inject a wpnonce security key and a referrer -->
        <input type="hidden" id="_wpnonce" name="_wpnonce" value="<?php echo wp_create_nonce('woocommerce-settings'); ?>">
        <input type="hidden" name="_wp_http_referer" value="<?php echo admin_url( 'admin.php?page=wc-settings&tab=integration&section=integration-contasimple'); ?>">
		<div class="form-wrapper">
			<div class="form-group">
				<div class="row">
					<label class="control-label col-lg-4 col-md-4">
						<?php _e('Account:', 'contasimple'); ?>
					</label>
					<div class="col-lg-8 col-md-8">
						<div class="input-group fixed-width-lg">
							<label class="btn btn-link col-lg-4 col-md-4">
								<?php if(!empty($data['email'])) echo esc_attr($data['email']); ?>
							</label>
						</div>
					</div>
				</div>
			</div>
			<div class="form-group">
				<div class="row">
					<label class="control-label col-lg-4 col-md-4">
						<?php _e('Company:', 'contasimple'); ?>
					</label>
					<div class="col-lg-8 col-md-8">
						<div class="input-group fixed-width-lg">
							<label class="control-label col-lg-8 col-md-8">
	                            <?php if(!empty($data['company'])) echo esc_attr($data['company']); ?>
							</label>
							<label class="control-label col-lg-8 col-md-8">
	                            <?php if(!empty($data['address'])) echo esc_attr($data['address']); ?>
							</label>
							<label class="control-label col-lg-8 col-md-8">
	                            <?php if(!empty($data['nif'])) echo esc_attr($data['nif']); ?>
							</label>
						</div>
					</div>
				</div>
			</div>
		</div>
		</br>
        <div>
            <span class="text-danger"><?php _e('Important:', 'contasimple'); ?> </span> <?php _e('Please, make sure that the same currency is selected in both WooCommerce and Contasimple in order to avoid any conversion issues during synchronization.', 'contasimple'); ?>
        </div>
        <br>
		<div class="text-center">
			<button type="button" value="1" name="unlink" class="button-primary woocommerce-save-button">
				<span><?php _e('Unlink account', 'contasimple'); ?></span>
			</button>
			&nbsp;
			<button type="button" value="1" name="reset" class="button-primary woocommerce-save-button">
				<span><?php _e('Unlink account and delete data', 'contasimple'); ?></span>
			</button>
			&nbsp;
			<button type="submit" value="1" name="refresh" class="button-primary woocommerce-save-button">
				<span><?php _e('Update account settings', 'contasimple'); ?></span>
			</button>
		</div>
        <br>
		<!-- Confirm Unlink Data -->
		<div id="dialog-modal-unlink" class="modal fade" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal">&times;</button>
						<span class="modal-title cs-header"><i class="icon-AdminContasimple cs-font-big-blue"></i> <?php _e('Contasimple Configuration', 'contasimple'); ?></span>
					</div>
					<div class="modal-body">
						<div class="form-wrapper">
							<p><?php _e('Are you sure you want to unlink your account?', 'contasimple'); ?></p>
							<p><?php _e('Only your access credentials will be deleted, your invoicing information will be preserved.', 'contasimple'); ?></p>
							</br>
						</div>
						<div class="row">
							<div class="col-xs-offset-2 col-xs-4 text-center">
								<button type="submit" name="confirm-unlink" value="1" class="btn btn-default btn-block">
									<span><i class=""></i> <?php _e('Yes', 'contasimple'); ?></span>
								</button>
							</div>
							<div class="col-xs-4 text-center">
								<button type="button" class="btn btn-default btn-block btn-cancel" data-dismiss="modal">
									<span><?php _e('No', 'contasimple'); ?></span>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<!-- Confirm Unlink & Delete Data -->
		<div id="dialog-modal-reset" class="modal fade" role="dialog">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal">&times;</button>
						<span class="modal-title cs-header"><i class="icon-AdminContasimple cs-font-big-blue"></i> <?php _e('Contasimple Configuration', 'contasimple'); ?></span>
					</div>
					<div class="modal-body">
						<div class="form-wrapper">
							<p><?php _e('Are you sure you want to unlink your account and delete your data?', 'contasimple'); ?></p>
							<p><?php _e('All your WooCommerce invoice synchronization information will be erased. Your data in Contasimple will never be erased.', 'contasimple'); ?></p>
							</br>
						</div>
						<div class="row">
							<div class="col-xs-offset-2 col-xs-4 text-center">
								<button type="submit" name="confirm-reset" value="1" class="btn btn-default btn-block">
									<span><i class=""></i> <?php _e('Yes', 'contasimple'); ?></span>
								</button>
							</div>
							<div class="col-xs-4 text-center">
								<button type="button" class="btn btn-default btn-block btn-cancel" data-dismiss="modal">
									<span><?php _e('No', 'contasimple'); ?></span>
								</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

	</form>
</div>