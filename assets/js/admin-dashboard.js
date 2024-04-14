require('../css/admin-dashboard.scss');

import dashboard from '../js/dashboard.js';

var ClipboardJS = require('clipboard');

var admin_dashboard = {
  // Add or edit
  on_workshop_manage : function(response) {
    if(1 === response.query_status) {
			// var workshop_class = dashboard.ws_active_event_class + ' workshop-status-' + response.workshop_status;
			var workshop_class = 'workshop-status-' + response.workshop_status;

			if(response.form_data.workshop_is_VSI_type === true)
				workshop_class += ' workshop-is-vsi';

			// console.log('on_workshop_manage()');
			// console.log('workshop_class: ' + workshop_class);

      if(true === response.is_new_entity) {
        // Push new workshop on calendar
        this.$calendar.fullCalendar('renderEvent', {
          id    : response.id_entity,
          className : workshop_class,
          url   : response.url_entity,
          title : response.form_data.workshop_theme_name,
          start : response.form_data.workshop_date_start,
          end   : response.form_data.workshop_date_end
        }, true);
      } else {
        // Reload popin
        dashboard.workshop_popin.fill(response);

        // Reload event in calendar
        var eventObj = this.$calendar.fullCalendar('clientEvents', response.id_entity);

        if(typeof eventObj != 'undefined' && eventObj.length > 0) {
          eventObj = $.extend(eventObj[0], {
            className : [ workshop_class ],
            url   : response.url_entity,
            title : response.form_data.workshop_theme_name,
            start : response.form_data.workshop_date_start,
            end   : response.form_data.workshop_date_end
          });

          this.$calendar.fullCalendar('updateEvent', eventObj);
        }
      }

      // Close modal
      this.$modal_manage_workshop.modal('hide');
    }
  },
  on_workshop_delete  : function(response) {
    // if OK = delete event
    if(response.query_status === 1) {
      this.$calendar.fullCalendar('removeEvents', response.id_entity);
      dashboard.$workshop_popin.simplePopin('close');

      var $row_workshop = dashboard.$row_subscribes.filter('[data-workshop-id="' + response.id_entity + '"]');
      var $row_parent = $row_workshop.parents('.user-workshops-day').first();

      // Delete workshop in row
      $row_workshop.remove();

      // If last > remove parents
      if($row_parent.find('.row-workshop').length < 1)
        $row_parent.remove();
    }
  },
  after_manage_user_vsi : function($form, response) {
    alert('[after_manage_user_vsi] yay !');
    return false;
    if(1 === response.query_status) {
      if(true === response.is_new_entity) {
        // Add
        document.location.reload();
      } else {
        // Edit
        var $row_entity = self.$workshop_theme_entities.filter('[data-id-entity="' + response.id_entity + '"]');
        $row_entity.find('.col-name > a').html(response.form_data.workshop_theme_name);
        $row_entity.find('.col-description-mini').find('.description').html(response.form_data.workshop_theme_description);

        // Update for theme main page
        dashboard.$core.find('.theme-entity-name').html(response.form_data.workshop_theme_name);
        dashboard.$core.find('.theme-entity-description').html(response.form_data.workshop_theme_description);

        // Hide modal
        self.$modal_manage_workshop_theme.modal('hide');
      }
    }
  },
  launch : function() {
    //
    // Variables
    //

    var self = this;

    // Nodes
    this.$calendar  = dashboard.$body.find('.workshop-calendar');
    // Modals
    this.$modal_manage_address        = dashboard.$body.find('#modal-manage-address');
    this.$row_entities                = dashboard.$body.find('.table-entities').find('.row-entity');
    this.$modal_manage_workshop_theme = dashboard.$body.find('#modal-manage-workshop-theme');
    this.$workshop_theme_entities     = dashboard.$body.find('.table-workshop-theme-entities').find('.workshop-theme-entity');
    this.$modal_manage_workshop       = dashboard.$body.find('#modal-manage-workshop');
    this.$modal_manage_user_vsi       = dashboard.$body.find('#modal-manage-user-vsi');


    //
    // Clipboard
    //

    var btn_clipboard = new ClipboardJS('.btn-clipboard');
    var timeoutHideTooltipClip = null;

    btn_clipboard.on('success', function(e) {
      var $btn_tooltip = $(e.trigger);
      $btn_tooltip = (typeof $btn_tooltip.data('clip-tooltip-target') != 'undefined') ? dashboard.$body.find($btn_tooltip.data('clip-tooltip-target')) : $btn_tooltip;

      // Show tooltip "Copied !" after copie
      var $tooltip_copied = $btn_tooltip.tooltip({ title: 'Copié !', trigger: 'manual' });
      $tooltip_copied.tooltip('show');

      // Then dispose tooltip after 1000ms
      clearTimeout(timeoutHideTooltipClip);
      timeoutHideTooltipClip = setTimeout(function() {
        $tooltip_copied.tooltip('dispose');
      }, 1000);
    });


    //
    // Events
    //

    // -------------------
    // Modal add users to workshop
    this.$modal_add_subber    = dashboard.$body.find('#modal-add-subber');
    this.$mas_search_results  = self.$modal_add_subber.find('.search-results');
    this.$mas_item_sample     = this.$mas_search_results.find('.item').clone();
    // remove search item sample
    this.$mas_search_results.find('.item').remove();

    // Display modal
    dashboard.$workshop_popin.on('click', '.btn-modal-add-subber', function() {
      var $button = $(this);
      self.$modal_add_subber.modal('show');
    });

    // Search (?!!)
    var time_before_searching = 400;
    var timeout_searching = null;
    var search_user_prev_value = null;
    this.$modal_add_subber
    .on('keydown', '.input-search-user', function() {
      search_user_prev_value = this.value;
      clearTimeout(timeout_searching);
    })
    .on('keyup', '.input-search-user', function(e) {
      var input = this;
      var unicode = e.keyCode ? e.keyCode : e.charCode;
      var locked_unicodes = {9 : true};
      var value_has_change = search_user_prev_value != input.value;
      // var is_alpha = unicode < 91 && unicode > 64; // Nice ! but useless

      // If unicode isn't locked > Go, search !
      if ((typeof locked_unicodes[unicode] == 'undefined') && value_has_change == true) {
        clearTimeout(timeout_searching);
        timeout_searching = setTimeout(function() {
          var $inputs_search  = self.$modal_add_subber.find('.input-search-user');
          var data = { };

          // Reset results
          self.$mas_search_results.removeClass('no-results').empty();

          var has_smthg_to_search = false;
          $inputs_search.each(function() {
            data[this.name] = this.value;
            has_smthg_to_search |= (this.value != '');
          }).attr('disabled', true);

          if (has_smthg_to_search == true) {
            // data.is_vsi = dashboard.workshop_popin.workshop_is_vsi();
            // Load subber to add
            $.fn.stealthRaven('fly', {
              url : '/dashboard/utilisateurs/rechercher',
              data : data,
              loading_class : 'raven-searching',
              done : function(response) {
                $inputs_search.removeAttr('disabled');
                $(input).focus();

                if (response.query_status == 1) {
                  if (response.users.length > 0) {
                    for (var i = 0; i < response.users.length; i++) {
                      var user  = response.users[i];
                      var $item = self.$mas_item_sample.clone();

                      // If users is already subbed to workshop > add CSS class
                      if (typeof dashboard.workshop_popin.subscribers[user.id] != 'undefined')
                        $item.addClass('in-workshop');

                      // Add CSS class if user is a VSI one
                      if (user.is_vsi == 1)
                        $item.addClass('is-vsi');

                      // Fill user info
                      $item.find('._names').html(user.lastname + ' ' + user.firstname);
                      $item.find('._email').html(user.email);

                      $item.data('user-id', user.id);

                      // Append new item
                      self.$mas_search_results.append($item);
                    }
                  } else {
                    self.$mas_search_results.addClass('no-results');
                  }
                }
              }
            });
          } else {
            // self.$mas_search_results.append('<div class="text-muted text-center py-3">Veuillez rechercher un nom ou un prénom.</div>');
            $inputs_search.removeAttr('disabled');
            $(input).focus();
          }
        }, time_before_searching);
      }

      search_user_prev_value = this.value;
    })
    .on('click', '.btn-add-new-subber', function() {
      var $item = $(this).parents('.item').first();

      $.fn.stealthRaven('fly', {
        url : '/dashboard/inscriptions/ajout-manuel',
        data : {
          workshop_id : dashboard.$workshop_popin.data('workshop-id'),
          user_is_vsi : $item.hasClass('is-vsi'),
          user_id     : $item.data('user-id'),
        },
        done : function(response) {
          if(response.query_status == 1) {
            // Update subscribers
            dashboard.workshop_popin.set_subscribers(response.subscribers, response.workshop.nb_seats, response.workshop.nb_seats_left);

            // Update seats
            // dashboard.workshop_popin.set_seats(response.workshop.nb_seats, response.workshop.nb_seats_left, response.workshop.nb_waiters);
            dashboard.workshop_popin.set_seats({
              nb_seats      : response.workshop.nb_seats,
              nb_seats_left : response.workshop.nb_seats_left,
              nb_waiters    : response.workshop.nb_waiters,
              workshop_status : response.workshop.status });

            // Set user in workshop
            $item.addClass('in-workshop');
          }
        }
      });
    })
    .on('hidden.bs.modal', function() {
      self.$mas_search_results.removeClass('no-results').empty();
      self.$modal_add_subber.find('.input-search-user').each(function() {
        this.value = '';
      });
    });



    // -------------------
    // Stealth Raven form submit
    this.$form_stealth_raven = dashboard.$core.find('.form-stealth-raven');

    // Workshop:Add+Edit
    this.$form_stealth_raven.filter('[name="workshop"]').stealthRaven({
      callback_done : function($form, response) {
        self.on_workshop_manage(response);
      }
    });

    // Workshop:Delete
    dashboard.$core.find('.btn-delete-workshop').stealthRaven({
      callback_done : function($button, response) {
        self.on_workshop_delete(response);
      }
    });


    // Workshop's theme Form
    this.$form_stealth_raven.filter('[name="workshop_theme"]').stealthRaven({
      callback_done : function($form, response) {
        if(1 === response.query_status) {
          if(true === response.is_new_entity) {
            // Add
            // document.location.reload();
          } else {
            // Edit
            var $row_entity = self.$workshop_theme_entities.filter('[data-id-entity="' + response.id_entity + '"]');
            $row_entity.find('.col-name > a').html(response.form_data.workshop_theme_name);
            $row_entity.find('.col-description-mini').find('.description').html(response.form_data.workshop_theme_description);

            // Update for theme main page
            dashboard.$core.find('.theme-entity-name').html(response.form_data.workshop_theme_name);
            dashboard.$core.find('.theme-entity-description').html(response.form_data.workshop_theme_description);

            // Hide modal
            self.$modal_manage_workshop_theme.modal('hide');
          }
        }
      }
    });

    // Address Form
    this.$form_stealth_raven.filter('[name="address"]').stealthRaven({
      callback_done : function($form, response) {
        if(1 === response.query_status) {
          if(true === response.is_new_entity) {
            // Add = reload // TODO dynamic reload
            document.location.reload();
          } else {
            // Edit
            var $row_entity = self.$row_entities.filter('[data-id-entity="' + response.id_entity + '"]');
            $row_entity.find('.col-name').html(response.form_data.address_name);
            $row_entity.find('.col-lat-lng').html(response.form_data.address_lat_position + ', ' + response.form_data.address_lng_position);

            // Hide modal
            self.$modal_manage_address.modal('hide');
          }
        }
      }
    });

    // User VSI Form
    this.$form_stealth_raven.filter('[name="user_vsi"]').stealthRaven({
      callback_done : function($form, response) {
        if (response.query_status == 1) {
          if(true === response.is_new_entity) {
            // Add display popin asking to reload page, not used ?
            dashboard.$body.find('.user-vsi--need-to-reload').addClass('show');
          } else {
            // Edit = update row cell values
            // var $row_entity = self.$row_entities.filter('[data-id-entity="' + response.id_entity + '"]');
            // $row_entity.find('.col-id-vsi').html(response.form_data.user_vsi_idVSI);
            // $row_entity.find('.col-names').html(response.form_data.user_vsi_lastname + ' / ' + response.form_data.user_vsi_firstname);
            // $row_entity.find('.col-email').html(response.form_data.user_vsi_email);
            // // Re-format date (not in PHP to let stealth raven input's loading value works)
            // var end_date_splitted = response.form_data.user_vsi_workshop_end_date.split('-');
            // if (end_date_splitted.length == 3)
            //   $row_entity.find('.col-send-date').html(end_date_splitted[2] + '/' + end_date_splitted[1] + '/' + end_date_splitted[0]);
            // // Hide modal
            // self.$modal_manage_user_vsi.modal('hide');

            // Easiest to just reload ...
            document.location.reload();
          }
        }
      }
    });

    // For ALL <select id=[user_vsi_referent_consultant | workshop_lecturer]> in raven forms
    //    > auto-select curent connected user
    this.$form_stealth_raven.find('#user_vsi_referent_consultant, #workshop_lecturer').each(function() {
      if (this.value.length == 0 && typeof dashboard.settings.user !== 'undefined') {
        var $select = $(this);
        var $options = $select.find('option');

        $options.each(function() {
          if (parseInt(this.value, 10) === dashboard.settings.user.id) {
            $select.val(dashboard.settings.user.id);
            return false;
          }
        });
      }
    });



    // -------------------
    // Stealth Raven click on button to execute ajax function
    this.$btn_stealth_raven = dashboard.$core.find('.btn-stealth-raven');

    this.$btn_stealth_raven.filter('[data-action="workshop_theme_delete"]').stealthRaven({
      callback_done : function($button, response) {
        // if OK = reload
        if(1 === response.query_status)
            document.location.reload();
          // self.$workshop_theme_entities.filter('[data-id-entity="' + response.id_entity + '"]')
          //   .remove();
      }
    });

    this.$btn_stealth_raven.filter('[data-action="row_delete"]').stealthRaven({
      callback_done : function($button, response) {
        // if OK = delete row
        if(1 === response.query_status)
          self.$row_entities.filter('[data-id-entity="' + response.id_entity + '"]')
            .remove();
      }
    });

    this.$btn_stealth_raven.filter('[data-action="simple_confirm"]').stealthRaven();



    // -------------------
    // Stealth Raven click on modal (with form) button

    // Basic click on button to display modal
    dashboard.$core.on('click', '.modal-stealth-raven-button', function() {
      var $modal = $($(this).data('target'));

      // Update title
      self.stealth_raven_modal_change_form_title(false, $modal);

      // Show Modal
      $modal.modal('show');
    });

    // Generic form loader
    dashboard.$core.find('.btn-stealth-raven-load-form').stealthRaven({
      callback_done : function($button, response) {
        var $modal = $($button.data('target'));

        // Update title
        self.stealth_raven_modal_change_form_title(true, $modal);

        // Show Modal
        $modal.modal('show');
      }
    });



    // -------------------
    // Stealth Raven modal on hide

    this.$modal_stealth_raven = dashboard.$body.find('.modal-stealth-raven');
    this.$modal_stealth_raven.on('hidden.bs.modal', function (e) {
      var $modal = $(this);
      // Reset title
      $modal.find('.modal-title').html('');

      // Reset Raven form
      $modal.find('.form-stealth-raven').stealthRaven('reset_form');
    });



    // -------------------
    // Click on the calendar (month|week(select))

    if(typeof dashboard.calendar != 'undefined') {
      // -------------------
      // Click on day = new event/workshop
      dashboard.calendar.on('dayClick', function(date, jsEvent, view) {
        var start_hour  = (view.name.match(/month/) != null) ? 8 : parseInt(date.format('H'));
        var end_hour    = start_hour + 2;
        var day         = date.format('YYYY-MM-DD');

        // Fill workshop form
        self.$form_stealth_raven.filter('[name="workshop"]').stealthRaven('fill_form', {
          data : {
            workshop_date_start  : day + 'T' + ($.fn.stealthRaven('ten', start_hour)) + ':00',
            workshop_date_end    : day + 'T' + ($.fn.stealthRaven('ten', end_hour)) + ':00'
          }
        });

        // Update title
        self.stealth_raven_modal_change_form_title(false, $modal);

        // Display modal with form
        self.$modal_manage_workshop.modal('show');
      });


      // -------------------
      // Select on day = new event/workshop

      dashboard.calendar.on('select', function(start, end, jsEvent, view) {
        if(view.name.match(/agenda/) != null) {
          // Fill workshop form
          self.$form_stealth_raven.filter('[name="workshop"]').stealthRaven('fill_form', {
            data : {
              workshop_date_start  : start.format('YYYY-MM-DD[T]HH[:]mm'),
              workshop_date_end    : end.format('YYYY-MM-DD[T]HH[:]mm')
            }
          });

          // Update title
          self.stealth_raven_modal_change_form_title();

          // Display modal with form
          self.$modal_manage_workshop.modal('show');
        }
      });
    }



    // -------------------
    // Manage subscribe
    if(dashboard.$workshop_popin.length > 0) {
      dashboard.workshop_popin.$ws_subs.on('change', '.subber-check-presence > .checkbox', function() {
        var id_subscribe = $(this).parents('.subscriber-item').data('id-subscribe');
        var $checkbox = $(this);

        $.fn.stealthRaven('fly', {
          url : '/dashboard/inscriptions/update-presence',
          data : {
            id : id_subscribe,
            presence : this.checked
          },
          done : function(response) {
            if(response.query_status === 1) {
              // Get user's presence icons on page to update them
              var $presence_icons = dashboard.$body.find('.presence-icon[data-id-subscribe="' + id_subscribe + '"]');
              $presence_icons.removeClass('icon-times icon-tick text-danger text-success');

              if(response.has_come == true) {
                $checkbox.addClass('validated');
                $presence_icons.addClass('icon-tick text-success');
              } else {
                $checkbox.removeClass('validated');
                $presence_icons.addClass('icon-times text-danger');
              }
            }
          }
        });
      });

      dashboard.workshop_popin.$ws_subs.on('click', '.btn-change-status', function() {
        var id_subscribe = $(this).parents('.subscriber-item').data('id-subscribe');

        $.fn.stealthRaven('fly', {
          url : '/dashboard/inscriptions/changer-statuts',
          data : {
            id     : id_subscribe,
            status : this.dataset.status
          },
          done : function(response) {
            if(response.query_status == 1) {
              // Update subscribers
              dashboard.workshop_popin.set_subscribers(response.subscribers, response.workshop.nb_seats, response.workshop.nb_seats_left);
              // Update seats
              // dashboard.workshop_popin.set_seats(response.workshop.nb_seats, response.workshop.nb_seats_left, response.workshop.nb_waiters);
              dashboard.workshop_popin.set_seats({
                nb_seats      : response.workshop.nb_seats,
                nb_seats_left : response.workshop.nb_seats_left,
                nb_waiters    : response.workshop.nb_waiters,
                workshop_status : response.workshop.status });

              // Update user status icons on page ?
              var status_icons = dashboard.$body.find('.status-icon[data-id-subscribe="' + id_subscribe + '"]');
              status_icons
                .attr('data-status-icon', response.subscribe_status)
                .attr('title', response.subscribe_status_text)
                .attr('data-original-title', response.subscribe_status_text)
                .tooltip('update');
            }
          }
        });
      });

      dashboard.workshop_popin.$ws_subs.on('click', '.btn-delete-subscribe', function(e) {
        if(window.confirm($(this).attr('data-stealth-raven-confirm'))) {
          $.fn.stealthRaven('fly', {
            url : this.href,
            done : function(response) {
              if(response.query_status === 1) {
                dashboard.workshop_popin.set_subscribers(response.subscribers, response.workshop.nb_seats, response.workshop.nb_seats_left);

                // dashboard.workshop_popin.set_seats(response.workshop.nb_seats, response.workshop.nb_seats_left, response.workshop.nb_waiters);
                dashboard.workshop_popin.set_seats({
                  nb_seats      : response.workshop.nb_seats,
                  nb_seats_left : response.workshop.nb_seats_left,
                  nb_waiters    : response.workshop.nb_waiters,
                  workshop_status : response.workshop.status });

                var $row_workshop = dashboard.$row_subscribes.filter('[data-workshop-id="' + response.id_workshop + '"]');
                var $row_parent = $row_workshop.parents('.user-workshops-day').first();

                // Delete workshop in row
                // $row_workshop.remove();
                //
                // // On unsub > close Simple Popin
                // dashboard.$workshop_popin.simplePopin('close');
                //
                // // If last > remove parents
                // if($row_parent.find('.row-workshop').length < 1)
                //   $row_parent.remove();
              }
            }
          });
        }

        // ULTRA-Stop propagation
        e.preventDefault();
        e.stopPropagation();
        return false;
      });
    }


    // btn-reset-survey-token
    dashboard.$body.on('click', '.btn-reset-survey-token', function(e) {
      var $btn = $(this);
      var $user_item = $btn.parents('.-item');
      var survey_token_id = typeof $btn != 'undefined' ? parseInt($btn.data('survey-token-id')) : 0;
      if (survey_token_id > 0) {
        $.fn.stealthRaven('fly', {
          url : '/dashboard/questionnaire/reset-token/' + survey_token_id,
          done : function(response) {
            if(response.query_status == 1) {
              // Delete tooltips
              $('.tooltip').tooltip('dispose');

              // Update token status HTML
              var $token_status = $btn.parents('.col-token-status');
              $token_status.html($('' +
                '<a href="' + $token_status.data('url-survey-token').replace('SURVEY_TOKEN', response.survey_token) + '" class="badge badge-type-service badge-warning"' +
                  'data-toggle="tooltip" data-placement="top" data-html="true"' +
                    'title="L\'accès expire le <b class=\'d-inline-block\'>' + response.expires_at_formatted + '</b>">' +
                  '<span class="text">en cours - ' + response.percent_completed + '%</span>' +
                '</a>'
              ).tooltip());

              // Survey results page: Delete user resetted & decrement nb participants
              // if (dashboard.$core.hasClass('app-core--survey-results')) {
              //   var $cell = dashboard.$body.find('.survey-results--table-keys-stats .-item[data-key-stat-slug="total_nb_participants"] .cell-value');
              //   var str_nb_participants = $cell.html();
              //   var reg_total_participants = /\/ ([0-9]+)/i;
              //   var nb_participants = str_nb_participants.match(reg_total_participants);
              //
              //   // Verify if nb participants has correctly matched
              //   if (nb_participants != null) {
              //     $cell.html(str_nb_participants.replace(reg_total_participants, '/ ' + (parseInt(nb_participants[1]) - 1)));
              //     $user_item.remove(); // Delete resetted user
              //   }
              // }
            }
          }
        });
      }

      // ULTRA-Stop propagation
      e.preventDefault();
      e.stopPropagation();
      return false;
    });


    // Change default survey
    dashboard.$body.on('change', '.control-default-survey', function(e) {
      var $radio = $(this);
      var survey_id = parseInt($radio.val());

      if (survey_id > 0) {
        $.fn.stealthRaven('fly', {
          url : '/dashboard/utilisateurs/VSI/questionnaires/change-default/' + survey_id,
          done : function(response) {
            // if(response.query_status == 1)
            //   console.log(response);
          }
        });
      }

      // ULTRA-Stop propagation
      e.preventDefault();
      e.stopPropagation();
      return false;
    });

    // Force step ID in survey question form in order to correctly add the new question
    //  (not useful when editing a question 'cause we already know the step ID)
    dashboard.$body.on('click', '.btn-add-survey-question', function() {
      var $btn = $(this);
      var $target = $($btn.data('target'));

      if (typeof $target != 'undefined')
        $target.find('[name="survey-step-id"]').attr('value', $btn.data('survey-step-id'));
    });
  },



  //
  // Dashboard methods ( = Stealth Raven ? )
  //

  stealth_raven_modal_change_form_title : function(is_edit, $modal) {
    $modal = (typeof $modal == 'undefined') ? this.$modal_stealth_raven : $modal;
    // Reload Modal Title
    var $modal_title = $modal.find('.modal-title');
    $modal_title.html($modal_title.data('stealth-raven-title-' + (typeof is_edit != 'undefined' && is_edit == true ? 'edit' : 'add')));
  },


};

//
// Doc ready
//

(function() {
  admin_dashboard.launch();
})();
