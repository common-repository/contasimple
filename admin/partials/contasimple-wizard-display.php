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

if ( empty( $data['custom_attributes']['paymentModulesList'] ) ) {
	$data['custom_attributes']['paymentModulesList'] = array();
}

?>

<!-- Weird, WP seems to be removing the first encountered form element, but not the rest. So we add a useless form so that the following ones can work... -->
<form id="handle-wp-parse-error" action="" method="post" class="defaultForm form-horizontal">
</form>

<div class="bootstrap-cs">
	<div class="panel cs">
		<h3 class="wc-settings-sub-title "><?php esc_html_e( 'Configuration Steps', 'contasimple' ); ?></h3>
		<p>
			<?php esc_html_e( 'You do not have any account linked to the plugin yet.', 'contasimple' ); ?>
			</br>
			<?php esc_html_e( 'If you have a Contasimple account, please follow the linking steps so that you can start working.', 'contasimple' ); ?>
		</p>
		</br>
		<div class="text-center">
			<a class="button-primary woocommerce-save-button show-login-dialog" data-toggle="modal" data-target="#dialog-modal-login">
				<?php esc_html_e( 'Link to my Contasimple account', 'contasimple' ); ?>
			</a>
		</div>
		</br></br>
		<p>
			<?php esc_html_e( 'On the other hand, if you do not have an account yet, you can create it in just a few seconds.', 'contasimple' ); ?>
		</p>
		</br>
		<div class="text-center">
			<a class="button-primary woocommerce-save-button ajax_do_linking_button" href="<?php echo esc_url( URL_CS_REGISTER ); ?>" target="_blank" role="button">
				<?php esc_html_e( 'Create a Contasimple account', 'contasimple' ); ?>
			</a>
		</div>
		<br>
	</div>

	<!-- Modal Login -->
	<div id="dialog-modal-login" class="modal fade cs" role="dialog" data-keyboard="false" data-backdrop="static">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<span class="modal-title cs-header"><i class="icon-AdminContasimple cs-font-big-blue"></i> <?php esc_html_e( 'Contasimple Login', 'contasimple' ); ?></span>
				</div>
				<div class="modal-body">
					<form id="form-cs-login" action="" method="post" class="defaultForm form-horizontal">
						<div class="form-wrapper">
							<p><?php esc_html_e( 'Connect to your Contasimple account', 'contasimple' ); ?></p>
							</br>
							<div class="form-group">
								<label class="control-label col-lg-2 col-md-2">
									<?php esc_html_e( 'ApiKey', 'contasimple' ); ?>
								</label>
								<div class="col-lg-9 col-md-9">
									<div class="input-group">
									<span class="input-group-addon">
										<span class="dashicons dashicons-admin-network dashicons-modal-cs"></span>
									</span>
										<input type="text" name="CONTASIMPLE_API_KEY" id="CONTASIMPLE_API_KEY" value="" class="">
									</div>
									</br>
									<label class="small">
										<?php esc_html_e( 'You need a personal access key to start syncing with Contasimple. It can be found on your control panel in Contasimple under the \'Configuration > External Applications\' section.', 'contasimple' ); ?>
									</label>
								</div>
							</div>
							<button type="button" id="login-button" class="btn btn-default btn-block btn-login center-block fixed-width-md">
								<span class="spinner"></span><span> <?php esc_html_e( 'Enter', 'contasimple' ); ?></span>
							</button>
							<br>
							<div class="text-center">
								<p id="login-status" ></p>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal Select Order Status needed to trigger Invoice Creation -->
	<div id="dialog-modal-sync-order-status" class="modal fade cs" role="dialog" data-keyboard="false" data-backdrop="static">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<span class="modal-title cs-header"><i class="icon-AdminContasimple cs-font-big-blue"></i> <?php esc_html_e( 'Contasimple Configuration', 'contasimple' ); ?></span>
				</div>
				<div class="modal-body">
					<form id="form-cs-sync-order-status" action="" method="post" class="defaultForm form-horizontal">
						<div class="form-wrapper">
							<p><?php esc_html_e( 'Select which status triggers first the order invoice generation. The default is to sync invoices when orders reach the \'Completed\' status. You can choose to sync invoices when the order status is set to \'Processing\' or \'On-hold\' instead.', 'contasimple' ); ?></p>
							</br>
							<div class="row center-block">
								<select name="CONTASIMPLE_SYNC_ORDER_STATUS" class="center-block fixed-width-md" id="CONTASIMPLE_SYNC_ORDER_STATUS">
									<option value="completed"><?php esc_html_e( 'Completed', 'contasimple' ); ?></option>
									<option value="processing"><?php esc_html_e( 'Processing', 'contasimple' ); ?></option>
									<option value="on-hold"><?php esc_html_e( 'On-Hold', 'contasimple' ); ?></option>
								</select>
							</div>
							</br>
							</br>
							<button type="submit" id="select-sync-order-status-button" class="btn btn-default btn-block btn-select-sync-order-status fixed-width-xl center-block">
								<span class="spinner"></span><span><?php esc_html_e( 'Save selection', 'contasimple' ); ?></span>
							</button>
						</div>
						<br>
						<div class="text-center">
							<p id="sync-order-status-status" ></p>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal Select Company -->
	<div id="dialog-modal-company" class="modal fade cs" role="dialog" data-keyboard="false" data-backdrop="static">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<span class="modal-title cs-header"><i class="icon-AdminContasimple cs-font-big-blue"></i> <?php esc_html_e( 'Contasimple Configuration', 'contasimple' ); ?></span>
				</div>
				<div class="modal-body">
					<form id="form-cs-company" action="" method="post" class="defaultForm form-horizontal">
						<div class="form-wrapper">
							<p><?php esc_html_e( 'Please, select the company you want to work with:', 'contasimple' ); ?></p>
							</br>
							<div class="row center-block">
								<select name="CONTASIMPLE_ACCOUNT_COMPANY" class="center-block fixed-width-xl" id="CONTASIMPLE_ACCOUNT_COMPANY">
								</select>
							</div>
							</br>
							<div class="text-center">
								<!-- <p><b><?php esc_html_e( 'Currency:', 'contasimple' ); ?> </b><span id="company-currency">-</span></p> -->
								<p><b><?php esc_html_e( 'Country:', 'contasimple' ); ?> </b><span id="company-country">-</span></p>
								<p><b><?php esc_html_e( 'Fiscal Region:', 'contasimple' ); ?> </b><span id="company-fiscal-region">-</span></p>
							</div>
							</br>
							<p><span class="text-danger"><?php esc_html_e( 'Important:', 'contasimple' ); ?> </span><?php esc_html_e( 'Please, make sure that the same currency is selected in both WooCommerce and Contasimple in order to avoid any conversion issues during synchronization.', 'contasimple' ); ?></p>
							</br>
							<button type="button" id="select-company-button" class="btn btn-default btn-block btn-select-company fixed-width-xl center-block">
								<span class="spinner"></span><span><?php esc_html_e( 'Select company', 'contasimple' ); ?></span>
							</button>
						</div>
						<br>
						<div class="text-center">
							<p id="company-status" ></p>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- TODO this does not seem to apply in this plugin, probably need to remove this before the final release -->
	<!-- Modal Update Company -->
	<div id="dialog-modal-update" class="modal fade cs" role="dialog" data-keyboard="false" data-backdrop="static">
		<div class="modal-dialog">
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<span class="modal-title cs-header"><i class="icon-AdminContasimple cs-font-big-blue"></i> <?php esc_html_e( 'Contasimple Configuration', 'contasimple' ); ?></span>
				</div>
				<div class="modal-body">
					<form id="form-cs-update-company" action="" method="post" class="defaultForm form-horizontal">
						<div class="form-wrapper">
							<p><?php esc_html_e( 'Your WooCommerce and Contasimple selected company identification numbers do not match.', 'contasimple' ); ?></p>
							<p><?php esc_html_e( 'In order to start working with the Contasimple module, both numbers must match.', 'contasimple' ); ?></p>
							</br>
							<p><?php esc_html_e( 'We can update your Contasimple settings with the current WooCommerce information.', 'contasimple' ); ?></p>
							<p><?php esc_html_e( 'Would you like to proceed?', 'contasimple' ); ?></p>
							</br>
							<div class="form-group">
								<div class="row">
									<div class="col-xs-offset-2 col-xs-4 text-center">
										<button type="submit" class="btn btn-default btn-update-company" style="white-space: normal;">
											<span class="spinner"></span><span><?php esc_html_e( 'Yes, Update company in Contasimple', 'contasimple' ); ?></span>
										</button>
									</div>
									<div class="col-xs-4 text-center">
										<button type="button" class="btn btn-default btn-cancel" style="white-space: normal;" data-dismiss="modal">
											<span><?php esc_html_e( 'No, I want to check both settings manually', 'contasimple' ); ?></span>
										</button>
									</div>
								</div>
							</div>
							<br>
							<div class="text-center">
								<p id="company-change" ></p>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal Select Payment Method -->
	<div id="dialog-modal-payment-method" class="modal fade cs" role="dialog" data-keyboard="false" data-backdrop="static">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<span class="modal-title cs-header"><i class="icon-AdminContasimple cs-font-big-blue"></i> <?php esc_html_e( 'Contasimple Configuration', 'contasimple' ); ?></span>
				</div>
				<div class="modal-body">
					<form id="form-cs-payment-method" action="" method="post" class="defaultForm form-horizontal">
						<div class="form-wrapper">
							<p><?php esc_html_e( 'Similar to Woocommerce, Contasimple allows the invoices to be marked as paid, and at that point, the user can also specify the payment method used. In this screen you need to specify, for each Woocommerce payment method, its equivalent one in Contasimple, so that when syncing the Woocommerce orders, those get marked as paid with the right payment method.', 'contasimple' ); ?></p>
							</br>
							<div class="payment-methods-container">
							<?php foreach ( $data['custom_attributes']['paymentModulesList'] as $payment_gateway ) : ?>
								<div class="row form-group center-block" style="display:flex; align-items:center">
									<div class="col-sm-5 col-xs-4 text-right">
										<p><?php echo esc_attr( $payment_gateway['displayName'] ); ?> </p>
									</div>
									<div class="text-left">
										<select name="<?php echo esc_attr( $payment_gateway['name'] ); ?>" class="cs-payment-method fixed-width-xl" id="<?php echo esc_attr( $payment_gateway['displayName'] ); ?>"></select>
									</div>
								</div>
							<?php endforeach ?>
							</div>
							</br>
							<p><span class="text-danger"><?php esc_html_e( 'Important:', 'contasimple' ); ?> </span><?php esc_html_e( 'You can create additional payment methods in Contasimple\'s website. If you install new WooCommerce payment modules after this configuration and want to use them, you will need to unlink your account and then run this configuration wizard again.', 'contasimple' ); ?></p>
							</br>
							<button type="submit" id="select-payment-method-button" class="btn btn-default btn-block btn-select-payment-method fixed-width-xl center-block">
								<span class="spinner"></span><span><?php esc_html_e( 'Save selection', 'contasimple' ); ?></span>
							</button>
						</div>
						<br>
						<div class="text-center">
							<p id="payment-method-status" ></p>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal Select Numbering series for each document type  -->
	<div id="dialog-modal-numbering-series" class="modal fade cs" role="dialog" data-keyboard="false" data-backdrop="static">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<span class="modal-title cs-header"><i class="icon-AdminContasimple cs-font-big-blue"></i> <?php esc_html_e( 'Contasimple Configuration', 'contasimple' ); ?></span>
				</div>
				<div class="modal-body">
					<form id="form-cs-numbering-series" action="" method="post" class="defaultForm form-horizontal">
						<div class="form-wrapper">
							<p><?php esc_html_e( 'Please select which numbering series from Contasimple you want to use for every type of invoice:', 'contasimple' ); ?></p>
							</br>
							<div class="numbering-series-container">
								<div class="row form-group center-block" style="display:flex; align-items:center">
									<div class="col-sm-5 col-xs-4 text-right">
										<p><?php esc_html_e( 'Invoices', 'contasimple' ); ?> </p>
									</div>
									<div class="text-left">
										<select name="select-invoices-series" class="cs-numbering-series fixed-width-xl" id="select_invoices_series"></select>
									</div>
								</div>
								<div class="row form-group center-block" style="display:flex; align-items:center">
									<div class="col-sm-5 col-xs-4 text-right">
										<p><?php esc_html_e( 'Credit notes', 'contasimple' ); ?> </p>
									</div>
									<div class="text-left">
										<select name="select-refunds-series" class="cs-numbering-series fixed-width-xl" id="select_refunds_series"></select>
									</div>
								</div>
								<div class="row form-group center-block" style="display:flex; align-items:center">
									<div class="col-sm-5 col-xs-4 text-right">
										<p><?php esc_html_e( 'Receipts', 'contasimple' ); ?> </p>
									</div>
									<div class="text-left">
										<select name="select-receipts-series" class="cs-numbering-series fixed-width-xl" id="select_receipts_series"></select>
									</div>
								</div>
							</div>
							<button id="open-create-new-series-button" class="btn btn-link fixed-width-xl center-block">
								<?php esc_html_e( 'Create a new series', 'contasimple' ); ?>
							</button>
							</br>
							<button type="submit" id="select-numbering-series-button" class="btn btn-default btn-block btn-select-numbering-series fixed-width-xl center-block">
								<span class="spinner"></span><span><?php esc_html_e( 'Save selection', 'contasimple' ); ?></span>
							</button>
						</div>
						<br>
						<div class="text-center">
							<p id="numbering-series-status" ></p>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>

	<!-- Modal for creating a new series -->
	<div id="dialog-modal-create-new-series" class="modal fade cs" role="dialog" data-keyboard="false" data-backdrop="static">
		<div class="modal-dialog">
			<!-- Modal content-->
			<div class="modal-content">
				<div class="modal-header">
					<button type="button" class="close" data-dismiss="modal">&times;</button>
					<span class="modal-title cs-header"><i class="icon-AdminContasimple cs-font-big-blue"></i> <?php esc_html_e( 'Contasimple Configuration', 'contasimple' ); ?></span>
				</div>
				<div class="modal-body">
					<form id="form-cs-numbering-series" action="" method="post" class="defaultForm form-horizontal">
						<div class="form-wrapper">
							<p><?php echo __( 'If you need a different series from the listed above, you can also create a new series right from here and then select it in the dropdown controls above.', 'contasimple' ) ?></p>
							<p style="margin-top: 10px"><?php echo __( 'Note: The mask can contain special characters that will be replaced for their corresponding value during the invoice creation:', 'contasimple' ) ?></p>
							<ul style="list-style: disc; margin-left: 30px">
								<li><?php echo __('<b>AA</b> and <b>AAAA</b> will be replaced by the current year in 2-digit or 4-digit format, respectively.', 'contasimple') ?></li>
								<li><?php echo __('The <b>#</b> symbol will be replaced by an incremental number. The number will have as many digits as the number of # symbols used.', 'contasimple') ?></li>
								<li><?php echo __('<b>Any other character</b> will be preserved.', 'contasimple') ?></li>
							</ul>
							</br>
							<div class="create-series-container">
								<div class="row form-group center-block" style="display:flex; align-items:center">
									<div class="col-sm-5 col-xs-4 text-right">
										<p><?php esc_html_e( 'Series name', 'contasimple' ); ?> </p>
									</div>
									<div class="text-left">
										<input type="text" name="ajax-contasimple_new_series_name" id="ajax-contasimple_new_series_name" value="" class="" placeholder="<?php echo __('Ex: WooCommerce Invoices', 'contasimple') ?>">
									</div>
								</div>
								<div class="row form-group center-block" style="display:flex; align-items:center">
									<div class="col-sm-5 col-xs-4 text-right">
										<p><?php esc_html_e( 'Numbering mask', 'contasimple' ); ?> </p>
									</div>
									<div class="text-left">
										<input type="text" name="ajax-contasimple_new_series_mask" id="ajax-contasimple_new_series_mask" value="" class="" placeholder="<?php echo __('Ex: WC-INV-AAAA-#####', 'contasimple') ?>">
									</div>
								</div>
								<div class="row form-group center-block" style="display:flex; align-items:center">
									<div class="col-sm-5 col-xs-4 text-right">
										<p><?php esc_html_e( 'Invoice type', 'contasimple' ); ?> </p>
									</div>
									<div class="text-left">
										<select name="ajax-contasimple_new_series_type" class="fixed-width-xl" id="ajax-contasimple_new_series_type">
											<option value="Normal"><?php echo __('Normal', 'contasimple') ?></option>
											<option value="Rectifying"><?php echo __('Rectifying', 'contasimple') ?></option>
										</select>
									</div>
								</div>
								<div class="row form-group center-block" style="display:flex; align-items:center">
									<div class="col-sm-5 col-xs-4 text-right">
										<label style="font-weight: normal"><?php echo __('Preview', 'contasimple') ?>:</label>
									</div>
									<div class="text-left">
										<label style="font-weight: normal; color: darkslateblue" id="ajax-contasimple_new_series_mask_output"></label>
									</div>
								</div>
								<button id="seriesWizardCreateButton" class="btn btn-default btn-block fixed-width-xl center-block">
									<span class="spinner"></span><span><?php esc_html_e( 'Create a new series', 'contasimple' ); ?></span>
								</button>
							</div>
						</div>
						<br>
						<div class="text-center">
							<p id="create-numbering-series-status" ></p>
						</div>
						<button id="close-create-new-series-button" class="btn btn-link fixed-width-xl center-block">
							<?php esc_html_e( 'Back to previous step', 'contasimple' ); ?>
						</button>
					</form>
				</div>
			</div>
		</div>
	</div>

</div>
