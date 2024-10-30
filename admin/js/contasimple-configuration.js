(function( $ ) {
	'use strict';

	// Expand jQuery to encode the whitespaces correctly, we need it to send the form values correctly for payment methods
	$.fn.serializeAndEncode = function() {
		return $.map(this.serializeArray(), function(val) {
			return [val.name, encodeURIComponent(val.value)].join('=');
		}).join('&');
	};

    $(document).ready(function(){
        // Datepicker for selecting the log file of the desired day, set default date the current day
        $('.datepicker').datepicker({
            maxDate: "+0d",
            dateFormat: "dd/mm/yy"
        }).datepicker("setDate", new Date());

        if ($('.show-login-dialog').is(':visible')) {
            $('input[name=save]').toggle();
        }

    });

    function getCsNounce() {
        return $('.submit #_wpnonce').val();
    }

    // On download button click, check if the file exists in the server and if it is the case, download it, inform the user otherwise
    $(document).on('click', '#logDownloadButton', function(event) {

        event.preventDefault();
        event.stopPropagation();

        var log_date = $('.datepicker').val();

        $.ajax({
            url: 'admin-ajax.php',
            dataType: 'json',
            data: {
                action: 'check_for_log',
                _wpnonce: getCsNounce(),
                date: log_date
            },
            error: function() {
                alert('Error trying to check for the logfile. Please contact Contasimple.');
            },
            success: function(response) {
                if (response == true) {
                    gtagCS('event', 'Download LOG file - Success', {
                        'event_category': 'Configuration',
                        'event_label': 'User clicked on download log and there were logged actions.'
                    });
                    var form_cs_download_log = $('#form-cs-download-log');
                    form_cs_download_log.append('<div><input type="text" name="log-date" value="' +  log_date  + '"/>');
                    form_cs_download_log.trigger('submit');
                } else {
                    gtagCS('event', 'Download LOG file - Empty', {
                        'event_category': 'Configuration',
                        'event_label': 'User clicked on download log, but there were logged actions.'
                    });
                    alert(js_translations.msg_log_not_found);
                }
            }
        });

    });


  // On create new series button click, invoke the Contasimple API and refresh the dropdown controls with the new options (settings page).
  $(document).on('click', '#seriesCreateButton', function(event) {

    event.preventDefault();
    event.stopPropagation();

    let name = $('#ajax-contasimple_new_series_name').val();
    let mask = $('#ajax-contasimple_new_series_mask').val();
    let type = $('#ajax-contasimple_new_series_type').find(":selected").val();

    $.ajax({
      url: 'admin-ajax.php',
      dataType: 'json',
      data: {
        action: 'create_new_series',
        _wpnonce: getCsNounce(),
        name: name,
        mask: mask,
        type: type
      },
      beforeSend: function () {
        disableButton('#seriesCreateButton');
        startLoadingButton('.new-series-spinner');
      },
      success: function(response) {
        if (response['success'] === true) {
          gtagCS('event', 'Create new numbering series - Success', {
            'event_category': 'Configuration',
            'event_label': 'User clicked on create new series button'
          });

          if (type === 'Normal') {
            $('#woocommerce_integration-contasimple_invoices_series').append(response['element'])
            $('#woocommerce_integration-contasimple_receipts_series').append(response['element'])
          } else {
            $('#woocommerce_integration-contasimple_refunds_series').append(response['element'])
          }

          $('#ajax-contasimple_new_series_name').val('');
          $('#ajax-contasimple_new_series_mask').val('');

          alert(js_translations.msg_new_series_added_successfully);

        } else {
          if (response['error'] !== undefined) {
            alert(response['error']);
          } else {
            alert(js_translations.msg_new_series_add_error);
          }
        }
        enableButton('#seriesCreateButton');
        stopLoadingButton('.new-series-spinner');
      },
      error: function() {
        alert(js_translations.msg_new_series_add_error);
        enableButton('#seriesCreateButton');
        stopLoadingButton('.new-series-spinner');
      },
    });

  });

  // Very similar to the previous one but for the Wizard.
  $(document).on('click', '#seriesWizardCreateButton', function(event) {

    event.preventDefault();
    event.stopPropagation();

    let name = $('#ajax-contasimple_new_series_name').val();
    let mask = $('#ajax-contasimple_new_series_mask').val();
    let type = $('#ajax-contasimple_new_series_type').find(":selected").val();

    $.ajax({
      url: 'admin-ajax.php',
      dataType: 'json',
      data: {
        action: 'create_new_series',
        _wpnonce: getCsNounce(),
        name: name,
        mask: mask,
        type: type
      },
      beforeSend: function () {
        disableButton('#seriesWizardCreateButton');
        startLoadingButton('#seriesWizardCreateButton');
      },
      success: function(response) {
        if (response['success'] === true) {
          gtagCS('event', 'Create new numbering series - Success', {
            'event_category': 'Configuration',
            'event_label': 'User clicked on create new series button'
          });

          if (type === 'Normal') {
            $('#select_invoices_series').append(response['element'])
            $('#select_receipts_series').append(response['element'])
          } else {
            $('#select_refunds_series').append(response['element'])
          }

          $('#ajax-contasimple_new_series_name').val('');
          $('#ajax-contasimple_new_series_mask').val('');

          showSuccessMessage('#create-numbering-series-status', js_translations.msg_new_series_added_successfully, function() {
            stopLoadingButton('#seriesWizardCreateButton');
            }, function(){
            enableButton('#seriesWizardCreateButton');
          });

        } else {
          let msgError = js_translations.msg_new_series_add_error;
          if (response['error'] !== undefined) {
            msgError = response['error'];
          }

          stopLoadingButton('#seriesWizardCreateButton');
          enableButton('#seriesWizardCreateButton');
          showErrorMessage('#create-numbering-series-status', msgError);
        }
      },
      error: function() {
        stopLoadingButton('#seriesWizardCreateButton');
        enableButton('#seriesWizardCreateButton');
        showErrorMessage('#create-numbering-series-status', js_translations.msg_new_series_add_error);
      },
    });

  });


  $( document ).on('keyup', '#ajax-contasimple_new_series_mask', function(event) {

    let result = '';
    let input = $(this).val();

    if (input.length > 0) {
      result = previewNumberingMaskResult(input);
    }

    $('#ajax-contasimple_new_series_mask_output').text(result);

  });

  /**
   * Given a mask format as a string, returns how the first invoice number would look like.
   * @param number
   * @returns {string}
   */
  function previewNumberingMaskResult(number) {

    function zeroPad(num, places) {
      var zero = places - num.toString().length + 1;
      return Array(+(zero > 0 && zero)).join("0") + num;
    }

    let wildcard = number.match(/([#]+)/g);
    let count = 0;

    if (wildcard !== null) {
      count = wildcard[0].length;
    }

    let result = number.replace('AAAA', new Date().getFullYear());
        result = result.replace('AA', new Date().getFullYear() % 100);

    if (count > 0) {
      result = result.replace(wildcard, zeroPad(1, count));
    } else {
      result = result+'1';
    }

    return result;
  }

	$( document ).on('click', '#login-button', function(event) {

		event.preventDefault();
		event.stopPropagation();

		$.ajax({
			url: 'admin-ajax.php',
			dataType: 'json',
			data: {
				action: 'cs_login',
                _wpnonce: getCsNounce(),
				apikey: $('#CONTASIMPLE_API_KEY').val()
			},
			beforeSend: function() {
                gtagCS('event', 'Connect via APIKEY', {
                    'event_category': 'Configuration',
                    'event_label': 'User clicked connect with key.' + $('#CONTASIMPLE_API_KEY').val()
                });
                disableButton('.btn-login');
                startLoadingButton('.btn-login');
			},
			success: function(response) {
                if (response.error) {
                    stopLoadingButton('.btn-login');
                    enableButton('.btn-login');
                    showErrorMessage('#login-status', response.message);
				} else {
                    $('#CONTASIMPLE_ACCOUNT_COMPANY').empty();
                    $.each(response.companies, function(i, company){
                        $('#CONTASIMPLE_ACCOUNT_COMPANY').append($('<option>', {
                            value: company.id_option,
                            text: company.name,
                            data: {
                                currency: company.currency,
                                country: company.country,
                                fiscalRegion: company.fiscalRegion
                            }
                        }));
                    });

                    if (response.companies) {
                        var contasimple_account_company_option = $('#CONTASIMPLE_ACCOUNT_COMPANY option');
                        $('#company-currency').text(contasimple_account_company_option.first().data('currency'));
                        $('#company-country').text(contasimple_account_company_option.first().data('country'));
                        $('#company-fiscal-region').text(contasimple_account_company_option.first().data('fiscalRegion'));

                        if (response.selectedCompany) {
                            var contasimple_account_company_option_selected = $('#CONTASIMPLE_ACCOUNT_COMPANY option[value=' + response.selectedCompany + ']');
                            contasimple_account_company_option_selected.attr("selected", "selected");
                            contasimple_account_company_option_selected.data('currency');
                            contasimple_account_company_option_selected.data('country');
                            contasimple_account_company_option_selected.data('fiscalRegion');
                        }

                        showSuccessMessage('#login-status', response.message, function () {
                            stopLoadingButton('.btn-login');
                        }, function () {
                            enableButton('.btn-login');
                            openSelectSyncOrderStatus();
                        });
                    } else {
                        stopLoadingButton('.btn-login');
                        enableButton('.btn-login');
                        showErrorMessage('#login-status', js_translations.msg_no_active_companies_found);
                    }
				}
			},
            error: function() {
                gtagCS('event', 'Connect via APIKEY - Error', {
                    'event_category': 'Configuration',
                    'event_label': 'User could not connect with APIKEY .' + $('#CONTASIMPLE_API_KEY').val()
                });
                stopLoadingButton('.btn-login');
                enableButton('.btn-login');
            }
		});
	});

  $( document ).on('click', '#select-sync-order-status-button', function(event) {

    event.preventDefault();
    event.stopPropagation();

    $.ajax({
      url: 'admin-ajax.php',
      dataType: 'json',
      data: {
        action: 'cs_select_sync_order_status',
        _wpnonce: getCsNounce(),
        syncOrderStatus: $('#CONTASIMPLE_SYNC_ORDER_STATUS').val()
      },
      beforeSend: function() {
        disableButton('.btn-select-sync-order-status');
        startLoadingButton('.btn-select-sync-order-status');
      },
      success: function(response) {
        if (response.error) {
          stopLoadingButton('.btn-select-sync-order-status');
          enableButton('.btn-select-sync-order-status');
          showErrorMessage('#sync-order-status-status', response.message);
        }
        else {
          showSuccessMessage('#sync-order-status-status', response.message, function() {
            stopLoadingButton('.btn-select-sync-order-status');
          }, function(){
            enableButton('.btn-select-sync-order-status');
            openSelectCompany();
          });
        }
      },
      error: function() {
        stopLoadingButton('.btn-select-sync-order-status');
        enableButton('.btn-select-sync-order-status');
      }
    });
  });

	$( document ).on('click', '#select-company-button', function(event) {

		event.preventDefault();
		event.stopPropagation();

		$.ajax({
			url: 'admin-ajax.php',
			dataType: 'json',
			data: {
				action: 'cs_select_company',
                _wpnonce: getCsNounce(),
				company: $('#CONTASIMPLE_ACCOUNT_COMPANY').val()
			},
			beforeSend: function() {
                disableButton('.btn-select-company');
                startLoadingButton('.btn-select-company');
			},
			success: function(response) {
                if (response.error) {
                    if (response.updateCompanyInfoStep) {
                        stopLoadingButton('.btn-select-company');
                        showErrorMessage('#company-status', response.message, function(){
                            enableButton('.btn-select-company');
                            openUpdateCompanyInfo();
                        })
                    } else {
                        stopLoadingButton('.btn-select-company');
                        enableButton('.btn-select-company');
                        showErrorMessage('#company-status', response.message);
                    }
                }
                else {
                    $('.cs-payment-method').empty();
                    $.each(response.paymentMethods, function(i, paymentMethod){
                        $('.cs-payment-method').append($('<option>', {
                            value: paymentMethod.id_option,
                            text: paymentMethod.name,
                            data: {
                            }
                        }));
                    });
                    showSuccessMessage('#company-status', response.message, function() {
                        stopLoadingButton('.btn-select-company');
                    }, function(){
                        enableButton('.btn-select-company');
                        openSelectPaymentMethod();
                    });
                }
			},
            error: function() {
                stopLoadingButton('.btn-select-company');
                enableButton('.btn-select-company');
            }
		});
	});

	$( document ).on('click', '#select-payment-method-button', function(event) {

		event.preventDefault();
		event.stopPropagation();

        $.ajax({
            url: 'admin-ajax.php',
            dataType: 'json',
            data: {
                action: 'cs_select_payment_methods',
                _wpnonce: getCsNounce(),
                data: $('#form-cs-payment-method').serializeAndEncode(),
            },
            beforeSend: function() {
                disableButton('.btn-select-payment-method');
                startLoadingButton('.btn-select-payment-method');
            },
            success: function(response) {
              $('.cs-numbering-series').empty();
              $.each(response.numberingSeries, function(i, numberingSeries){
                let option = {
                  value: numberingSeries.id_option,
                  text: numberingSeries.name,
                  data: {
                  }
                };

                if (numberingSeries.type === 'Normal') {
                  $('#select_invoices_series').append($('<option>', option));
                  $('#select_receipts_series').append($('<option>', option));
                } else { // Rectifying
                  $('#select_refunds_series').append($('<option>', option));
                }
              });
              showSuccessMessage('#payment-method-status', response.message, function() {
                stopLoadingButton('.btn-select-payment-method');
              }, function(){
                enableButton('.btn-select-numbering-series');
                openSelectNumberingSeries();
              });
            },
            error: function() {
                stopLoadingButton('.btn-select-payment-method');
                enableButton('.btn-select-payment-method');
            }
        });
    });

  $( document ).on('click', '#select-numbering-series-button', function(event) {

    event.preventDefault();
    event.stopPropagation();

    $.ajax({
      url: 'admin-ajax.php',
      dataType: 'json',
      data: {
        action: 'cs_select_numbering_series',
        _wpnonce: getCsNounce(),
        data: $('#form-cs-numbering-series').serializeAndEncode(),
      },
      beforeSend: function() {
        disableButton('.btn-select-numbering-series');
        startLoadingButton('.btn-select-numbering-series');
      },
      success: function(response) {
        if (response.error) {
          stopLoadingButton('.btn-select-numbering-series');
          enableButton('.btn-select-numbering-series');
          showErrorMessage('#numbering-series-status', response.message);
        } else {
          showSuccessMessage('#numbering-series-status', response.message, function() {
            stopLoadingButton('.btn-select-numbering-series');
          }, function() {
            window.location = window.location.href;
          });
        }
      },
      error: function() {
        stopLoadingButton('.btn-select-numbering-series');
        enableButton('.btn-select-numbering-series');
      }
    });
  });

  $( document ).on('click', '#open-create-new-series-button', function(event) {

    event.preventDefault();
    event.stopPropagation();

    openCreateNewSeries();

  });

  $( document ).on('click', '#close-create-new-series-button', function(event) {

    event.preventDefault();
    event.stopPropagation();

    closeCreateNewSeries();

  });


  $(document).on('click', 'button[name="unlink"]', function() {
		$('#dialog-modal-unlink').modal('show').one('click', 'button[name="confirm-unlink"]', function(){
            gtagCS('event', 'Unlink account', {
                'event_category': 'Configuration',
                'event_label': 'User wants to unlink account.'
            });
			$('#form-cs-summary').trigger('submit');
		});
	});

	$(document).on('click', 'button[name="reset"]', function() {
		$('#dialog-modal-reset').modal('show').one('click', 'button[name="confirm-reset"]', function(){
            gtagCS('event', 'Unlink account and delete data', {
                'event_category': 'Configuration',
                'event_label': 'User wants to unlink account and reset data.'
            });
			$('#form-cs-summary').trigger('submit');
		});
	});

    $(document).on("hidden.bs.modal", "#dialog-modal-login", function () {
        stopLoadingButton('.btn-login');
        enableButton('.btn-login');
    });

    $(document).on('change', '#CONTASIMPLE_ACCOUNT_COMPANY', function() {
        var contasimple_account_company_option_selected = $('#CONTASIMPLE_ACCOUNT_COMPANY option:selected');
        $('#company-currency').text(contasimple_account_company_option_selected.data('currency'));
        $('#company-country').text(contasimple_account_company_option_selected.data('country'));
        $('#company-fiscal-region').text(contasimple_account_company_option_selected.data('fiscalRegion'));
    });

    function disableButton(buttonName) {
        $(buttonName).prop('disabled', true);
    }

    function startLoadingButton(buttonName) {
        $(buttonName).find("span").addClass("is-active");
    }

    function enableButton(buttonName) {
        $(buttonName).prop('disabled', false);
    }

    function stopLoadingButton(buttonName) {
        $(buttonName).find("span").removeClass("is-active");
    }

    function showErrorMessage(label, message, callback) {
        $(label).stop().addClass('text-danger').removeClass('text-success').fadeIn(200).text(message).delay(6000).fadeOut(200, callback);
    }

    function showSuccessMessage(label, message, callbackBefore, callbackAfter) {
        $(label).stop().addClass('text-success').removeClass('text-danger').fadeIn(200, callbackBefore).text(message).delay(2000).fadeOut(200, callbackAfter);
    }

    function openSelectSyncOrderStatus() {
        $('#dialog-modal-login').modal('hide');
        $('#dialog-modal-sync-order-status').modal('show');
    }

    function openSelectCompany() {
        $('#dialog-modal-sync-order-status').modal('hide');
        $('#dialog-modal-company').modal('show');
    }

    function openUpdateCompanyInfo() {
        $('#dialog-modal-company').modal('hide');
        $('#dialog-modal-update').modal('show');
    }

    function openSelectPaymentMethod() {
        $('#dialog-modal-company').modal('hide');
        $('#dialog-modal-update').modal('hide');
        $('#dialog-modal-payment-method').modal('show');
    }

  function openSelectNumberingSeries() {
    $('#dialog-modal-payment-method').modal('hide');
    $('#dialog-modal-numbering-series').modal('show');
  }

  function openCreateNewSeries() {
    $('#dialog-modal-create-new-series').modal('show');
  }

  function closeCreateNewSeries() {
    $('#dialog-modal-create-new-series').modal('hide');
  }

})( jQuery );
