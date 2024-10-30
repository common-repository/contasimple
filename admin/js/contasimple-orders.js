(function( $ ) {
	'use strict';

    $(document).ready(function(){
        $(document).tooltip({
            selector: '[rel="tooltip"]'
        })
    });

    $(document).on('click', '.cs_view', function () {
        $(this).attr('target', '_blank');
    });

    // If any of the selected orders has an order completed date different than the current fiscal period,
    // we should warn.
    $(document).on('click', '#doaction[name="cs"]', function (event) {

        event.preventDefault();

        let warning = false;
        let checkboxes = $('input[name="order_ids[]"]:checked');
        let action = $('#bulk-action-selector-top').find(":selected").val();

        if (checkboxes.length === 0)
          return true;

        if (action !== 'create')
          return true;

        $(checkboxes).each(function() {
          let date = $(this).parent().parent().find('time').first().attr('datetime');

          if (orderIsNotFromThisFiscalPeriod(date)) {
            warning = true;
            return false; // break the bucle.
          }
        });

        if (warning === true) {
          $('#dialog-modal-confirm-manual-sync').one('show.bs.modal', function(e) {
            $('button[name="confirm-manual-sync"]').one('click', function(event) {
              $('#dialog-modal-confirm-manual-sync').modal('hide');
              document.getElementById('posts-filter').submit();
            });
          });

          $('#dialog-modal-confirm-manual-sync').one('hidden.bs.modal', function(e) {
            $('button[name="confirm-manual-sync"]').off('click');
          });

          $('#dialog-modal-confirm-manual-sync').modal('show');
        } else {
          document.getElementById('posts-filter').submit();
        }
    });

    /**
     * User clicks on a custom action button.
     * Perform common stuff like handling javascript window state change (icons, etc).
     */
    $(document).on('click', '.custom-action.cs_sync, ' + // sync
                            '.custom-action.cs_stop,'  + // stop
                            '.custom-action.cs_email:not(.disabled), ' + // send email
                            '.custom-action.cs_email_sent:not(.disabled), ' +
                            '.custom-action.cs_email_failed:not(.disabled)',
                            function(event) {

        event.preventDefault();

        var btn = $(this),
            url = btn.attr('href'),
            action = getURLParameter(url, 'action'),
            gtag_event = "Click invoice action",
            gtag_event_category = "Invoices Panel",
            gtag_label = "User triggered a single, manual invoice action via one of the possible action buttons (depending on invoice state).",
            gtag_event_action = action;

        switch (action) {
            // User wants to sync the invoice.
          case 'cs_sync':
                // Confirm dialog removed due to new requirements.
                performCustomActionAjax(action, btn, url);
                break;

            // User wants to remove the invoice from the queue.
            // Just call the cs_stop ajax action.
            case 'cs_stop':
                performCustomActionAjax(action, btn, url);
                break;

            // User wants to send the invoice to customer as a PDF via email.
            case 'cs_email':
                gtag_event = "Click invoice action send email";
                gtag_label = "User triggered the send email button on invoices sync panel.";
                performCustomActionAjax(action, btn, url);
                break;

            // User wants to download the invoice as a PDF from CS.
            case 'cs_pdf':
                gtag_event = "Click invoice action download PDF";
                gtag_label = "User triggered the download PDF button on invoices sync panel.";
                // This is easier so we won't call performCustomActionAjax(), the backend will do it via native WP ajax system.
                break;
        }

        gtagCS(
            'event', gtag_event, {
                'event_category': gtag_event_category,
                'event_label': gtag_label,
                'event_action': gtag_event_action
            }
        );

        return false;
    });

    /**
     * Common bootstrapping for sync and stop ajax calls.
     *
     * @param action Either 'cs_sync' or 'cs_stop'. Could be expanded on the future.
     * @param btn A reference to the clicked button. Will aid on getting sibling and parent DOM elements.
     * @param url The href of the button. Allows us to fetch the id of the invoice and the id of the order.
     */
    function performCustomActionAjax(action, btn, url) {

        var btnContainer = btn.closest('.order_actions'),
            order_id = getURLParameter(url, 'order_id'),
            cs_invoice_id = getURLParameter(url, 'cs_invoice_id'),
            nonce = getURLParameter(url, '_wpnonce'),
            state = btn.closest('tr').find('.state'),
            message = btn.closest('tr').find('.message'),
            sync_date = btn.closest('tr').find('.date-sync'),
            total_amount = btn.closest('tr').find('.cs_total'),
            invoice_number = btn.closest('tr').find('.cs_number'),
            tablerow = btn.closest('tr'),
            summary = $('.notice-cs'),
            icon;

        if ('cs_email' === action) {
            icon = btn.closest('tr').find('.view.cs_email, .view.cs_email_sent, .view.cs_email_failed');
        } else {
            icon = btn.closest('tr').find('.view.' + action);
        }

        // We keep a copy of elements since we could revert to previous message if request failed
        var buttonPrevious = btn.clone(),
            iconPrevious = icon.clone(),
            messagePrevious = message.clone();

        $.ajax({
            type: 'POST',
            url: ajaxurl,
            data: {
                action: action,
                nonce: nonce,
                order_id: order_id,
                cs_invoice_id: cs_invoice_id,
            },
            dataType: 'json',
            beforeSend: function() {
                btn.siblings().andSelf().each(function() {
                    $(this).prop('disabled', true).css('pointer-events', 'none');
                });

                if ('cs_email' === action) {
                    icon.removeClass('cs_email cs_email_failed cs_email_sent').addClass('cs_processing');
                } else {
                    icon.removeClass(action).addClass('cs_processing');
                }

                if ('cs_stop' === action) {
                    message.text(js_translations.msg_stopping).attr('style', '');
                }

                if ('cs_sync' === action) {
                    message.text(js_translations.msg_syncing).attr('style', '');
                }
            },
            success: function(response) {
                if (response.redirect) {
                    window.location.replace(response.redirect);
                } else {
                    // Just remove all the whole row.
                    if ('cs_stop' === action) {
                        tablerow.remove();
                    }

                    // Update buttons to match the according new state possible custom actions.
                    if ('cs_sync' === action || 'cs_email' === action) {

                        btnContainer.html(response.buttons);

                        if (response.message) { message.replaceWith(response.message); }
                        if (response.icon) { state.replaceWith(response.icon); }
                        if (response.date_sync) { sync_date.html(response.date_sync); }
                        if (response.invoice_number) { invoice_number.html(response.invoice_number); }
                        if (response.total_amount) { total_amount.html(response.total_amount); }

                        btn.siblings().andSelf().each(function() {
                            $(this).prop('disabled', false).css('pointer-events', 'auto');
                        });
                    }

                    if (response.summary) {
                        summary.replaceWith(response.summary);
                    }
                }
            },
            error: function (xhr, ajaxOptions, thrownError){

                btn.replaceWith(buttonPrevious);
                icon.replaceWith(iconPrevious);
                message.replaceWith(messagePrevious);

                summary.replaceWith(thrownError);

                $("html, body").animate({ scrollTop: 0 }, "slow");
            }
        });
    }

    function getURLParameter(url, name) {
        return (RegExp(name + '=' + '(.+?)(&|$)').exec(url)||[,null])[1];
    }

    function orderIsNotFromThisFiscalPeriod(dateAsString) {

      let currentPeriod = getPeriodFromDate(new Date().toISOString());
      let orderPeriod   = getPeriodFromDate(dateAsString);

      return currentPeriod !== orderPeriod;
    }

    function getPeriodFromDate(dateAsString) {

      let dt        = new Date( dateAsString );
      let month     = dt.getMonth()+1;
      let year      = dt.getFullYear();
      let trimester = Math.ceil( month / 3 );

      return year + '-' + trimester + 'T';
    }

})( jQuery );
