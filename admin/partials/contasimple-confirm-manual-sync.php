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

<div class="bootstrap-cs">

	<!-- Modal to confirm manual sync with a different numbering series -->
	<div id="dialog-modal-confirm-manual-sync" class="modal fade cs" role="dialog" data-keyboard="false" data-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <span class="modal-title cs-header"><i class="icon-AdminContasimple cs-font-big-blue"></i> <?php _e('Confirm sync', 'contasimple'); ?></span>
                </div>
                <div class="modal-body">
                    <div class="form-wrapper">
                        <p><?php _e('At least one of the selected orders has an order completed date that is in a different fiscal period than the current one. If you add these orders to the sync queue, they will be counted in the current fiscal quarter.', 'contasimple'); ?></p>
                        <p><?php _e('Do you want to continue?', 'contasimple'); ?></p>
                        </br>
                    </div>
                    <div class="row">
                        <div class="col-xs-offset-2 col-xs-4 text-center">
                            <button type="submit" name="confirm-manual-sync" value="1" class="btn btn-default btn-block">
                                <span><i class=""></i> <?php _e('Accept', 'contasimple'); ?></span>
                            </button>
                        </div>
                        <div class="col-xs-4 text-center">
                            <button type="button" name="cancel-manual-sync" class="btn btn-default btn-block btn-cancel" data-dismiss="modal">
                                <span><?php _e('Cancel', 'contasimple'); ?></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
	</div>

</div>
