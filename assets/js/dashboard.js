require('../css/dashboard.scss');

//
// Workshop Calendar script
//

require('fullcalendar');
require('fullcalendar/dist/locale/fr.js');


//
// Simple Popin
//

(function($) {
  $.fn.simplePopin = function(options, callback) {
    var simple_popins = this;

    if(typeof simple_popins.settings == 'undefined') {
      // console.log('{Simpin} Hi !');

      this.settings = $.extend({
        on_open   : null, /*function($popin) { }*/
        on_close  : null /*function($popin) { }*/
      }, options);

      simple_popins.$html_body = $('html, body');

      simple_popins.open = function(callback_open) {
        // console.log('{Simpin} simplePopin().open()');
        var $popin = $(this);

        $popin.removeClass('simpin-hide');

        // Scroll to Simple Popin
        simple_popins.$html_body.animate({ scrollTop: $popin.offset().top }, 600);

        if(typeof simple_popins.settings.on_open == 'function')
          simple_popins.settings.on_open($popin);

        if(typeof callback_open == 'function')
          callback_open($popin);

        return this;
      };

      simple_popins.close = function(callback_done) {
        // console.log('{Simpin} simplePopin().close()');
        var $popin = $(this);

        $popin.slideUp(function() {
          $popin.addClass('simpin-hide').removeAttr('style');

          if(typeof simple_popins.settings.on_close == 'function')
            simple_popins.settings.on_close($popin);

          if(typeof callback_done == 'function')
            callback_done($popin);
        });

        return this;
      };

      $(simple_popins).on('click', '.simpin-close', function() {
        simple_popins.close();
      });
    }

    return this.each(function() {
      if(typeof options == 'string' && typeof simple_popins[options] != 'undefined')
        simple_popins[options](callback);
    });
  };
})(jQuery);


//
// Dashboard (main functions)
//

var dashboard = {
  ws_status : {
    list : [
      'workshop-subscribes-opened',
      'workshop-waiting-validation',
      'workshop-subscribes-closed',
    ],
    exist : function(status) {
      return this.list.indexOf('workshop-' + status) != -1;
    },
    prefixe : function(status) {
      if(this.exist(status))
        return 'workshop-' + status;
      return false;
    }
  },

  ws_user_status : {
    list : [
      'user-status-sub-not-confirmed',
      'user-status-waiting-seats',
      'user-status-pre-subscribe',
      'user-status-subscribed',
      'user-status-waiting-stuck',
    ],
    exist : function(status) {
      return this.list.indexOf('user-status-' + status) != -1;
    },
    prefixe : function(status) {
      if(this.exist(status))
        return 'user-status-' + status;
      return false;
    }
  },

	ws_active_event_class: 'workshop-is-active',

  workshop_popin : {
    $popin  : null,
    $map    : null,
    build   : function($popin) {
      var self = this;
      // Popin Node
      self.$popin = $popin;
      // Workshop Nodes
      self.$ws_list_specs = self.$popin.find('.list-group-specs');
      self.$ws_date       = self.$popin.find('.workshop-date');
      self.$ws_title      = self.$popin.find('.workshop-title');
      self.$ws_desc       = self.$popin.find('.workshop-desc');
      self.$ws_infos      = self.$popin.find('.workshop-infos');
      self.ws_infos_html  = self.$ws_infos.prop('outerHTML');
      self.$ws_lecturer   = self.$popin.find('.workshop-lecturer');
      self.$ws_duration   = self.$popin.find('.workshop-duration');
      self.$ws_seats_left = self.$popin.find('.workshop-seats-left');
      self.$ws_actions    = self.$popin.find('.workshop-actions');
      self.$ws_address    = self.$popin.find('.workshop-address');
      self.$ws_user_status    = self.$popin.find('.workshop-user-status');
      self.$ws_user_has_come  = self.$popin.find('.workshop-user-has-come');
      self.$ws_subs           = self.$popin.find('.workshop-subscribers');

      // Create suber sample and delete the HTML original one
      if(self.$ws_subs.length > 0) {
        self.$suber_sample = self.$ws_subs.find('.subscriber-sample').clone();
        self.$suber_sample.removeClass('subscriber-sample').removeAttr('style');
        // Delete sample from list
        self.$ws_subs.find('.subscriber-sample').remove();

        // Subscribers list
        self.$ws_subbers_list = self.$ws_subs.find('.subscribers-list');

        // Copy subbers
        self.$ws_subbers_copy_text = self.$ws_subs.find('#subscribers-copy-format-text');
        self.$ws_subbers_copy_mail = self.$ws_subs.find('#subscribers-copy-format-mail');
      }

      // SIMPLE POPIN : init
      self.$popin.simplePopin({
        on_close : function() {
					/*var eventsObj = dashboard.$calendar.fullCalendar('clientEvents');

					// Need to loop on ALL events in order to manage their CSS class
					for (var i = 0; i < eventsObj.length; i++) {
						var eventObj = eventsObj[i];
						var positionCSSClassActive = $.inArray(dashboard.ws_active_event_class, eventObj.className);

						// Reset CSS class active from events on calendar
						if ( ~positionCSSClassActive )
							eventObj.className.splice(positionCSSClassActive, 1);

						// Update event on calendar
						dashboard.$calendar.fullCalendar('updateEvent', eventObj);
					}*/

          dashboard.$row_subscribes.removeClass('current');
        }
      });

      // Google Maps Node
      self.$map = self.$popin.find('.google-maps > .map');
      if(self.$map.length > 0 && typeof(google) != 'undefined') {
        self.google_maps = new google.maps.Map(self.$map.get(0), {
          zoom: 16,
          center: { lat: 43.5359525, lng: 5.3177359 }, // = Aix-en-Provence
          // User controls on map
          mapTypeControl: false,
          streetViewControl: false,
          // Map styles
          styles: [
            {
              "featureType": "all",
              "stylers": [
                { "saturation": 0 },
                { "hue": "#e7ecf0" }
              ]
            },
            {
              "featureType": "road",
              "stylers": [
                { "saturation": -70 }
              ]
            },
            {
              "featureType": "transit",
              "stylers": [
                { "visibility": "off" }
              ]
            },
            {
              "featureType": "poi",
              "stylers": [
                { "visibility": "off" }
              ]
            },
            {
              "featureType": "water",
              "stylers": [
                { "visibility": "simplified" },
                { "saturation": -60 }
              ]
            }
          ]
        });
      }

      self.$popin.find('.btn-subscribe-to-workshop, .btn-unsubscribe-to-workshop, .btn-valid-subscribe').stealthRaven({
        callback_done : function($button, response) {
          if(response.query_status == 1) {
            // Update status
            self.set_status({
              status_workshop   : response.workshop_status,
              status_user       : response.user_subscribe_status,
              status_user_text  : response.user_subscribe_status_text
            });

            // Update seats
            self.set_seats({
              nb_seats      : response.workshop_nb_seats,
              nb_seats_left : response.workshop_nb_seats_left,
              nb_waiters    : response.workshop_nb_waiters,
              workshop_status : response.workshop_status });

            // Update actions
            self.set_actions(response);

            // Update workshop (on calendar or row)
            self.update_workshop(response.workshop_id, response);
          }
        }
      });
    },
    fill    : function(response) {
      // Set id workshop into popin data
      this.$popin.data('workshop-id', response.id_entity);

      var data = response.form_data;
      // Toggle VSI on popin
      this.$popin.toggleClass('workshop-is-type-vsi', (data.workshop_is_VSI_type === true));

      // Convert date start and end strings to .moment object in UTC
      var date_start  = $.fullCalendar.moment(data.workshop_date_start);
      var date_end    = $.fullCalendar.moment(data.workshop_date_end);

      // Fill date
      this.$ws_date.find('._day-label').html($.fullCalendar.formatDate(date_start, 'dddd'));
      this.$ws_date.find('._day-number').html($.fullCalendar.formatDate(date_start, 'D'));
      this.$ws_date.find('._month').html($.fullCalendar.formatDate(date_start, 'MMM'));

      // Fill time
      var $time = this.$ws_date.find('._time');
      $time.find('.from .value').html($.fullCalendar.formatDate(date_start, 'kk:mm'));
      $time.find('.to .value').html($.fullCalendar.formatDate(date_end, 'kk:mm'));

      // Fill title
      this.$ws_title.html(data.workshop_theme_name);

      // Fill description
      this.$ws_desc.html(((data.workshop_theme_description != null) ? data.workshop_theme_description.replace(/(?:\r\n|\r|\n)/g, '<br>') : ''));

      // Fill details if exists
      this.$ws_infos.remove();
      if (data.workshop_description != null) {
        this.$ws_infos = $(this.ws_infos_html);
        this.$ws_infos.find('.value').html(data.workshop_description.replace(/(?:\r\n|\r|\n)/g, '<br>'));
        this.$ws_list_specs.prepend(this.$ws_infos);
      }

      // Fill lecturer
      this.$ws_lecturer.find('.name').html(data.workshop_lecturer_name);
      this.$ws_lecturer.find('.contact').html(data.workshop_lecturer_email);

      // Fill duration
      var duration  = parseFloat(date_end.diff(date_start, 'hours', true));
      var hours     = parseInt(duration);
      var minutes   = Math.round((duration - hours) * 60);
      this.$ws_duration.find('.value').html(hours + 'h' + ((minutes < 10)?'0':'') + minutes);

      // Fill Address name
      this.$ws_address.find('.text').html(data.workshop_address_name);

      // Fill seats
      this.set_seats({
        nb_seats      : data.workshop_nb_seats,
        nb_seats_left : data.workshop_nb_seats_left,
        nb_waiters    : data.workshop_nb_waiters,
        workshop_status : response.workshop_status
      });

      // Set actions
      this.set_actions(response);

      // Set Marker on GMaps
      this.set_marker(data.workshop_latitude, data.workshop_longitude);

      // Change status to hide or display some things
      this.set_status({
        status_workshop   : response.workshop_status,
        status_user       : response.user_subscribe_status,
        status_user_text  : response.user_subscribe_status_text
      });

      // Set presence
      this.set_has_come({
        user_status         : response.user_subscribe_status,
        user_has_come       : response.user_subscribe_has_come,
        user_has_come_text  : response.user_subscribe_has_come_text,
        workshop_status     : response.workshop_status
      });

      // Set subscribers (admin)
      this.set_subscribers(response.subscribers, data.workshop_nb_seats, data.workshop_nb_seats_left);
    },
    load    : function(workshop_url) {
      var self = this;

      // Use Stealth Raven to get more event informations
      $.fn.stealthRaven('fly', {
        url: workshop_url,
        done: function(response) {
          // Query : OK
          if(response.query_status == 1) {
            self.fill(response);

            // Display SimplePopin
            self.$popin.simplePopin('open');
          }
        }
      });
    },
    update_workshop : function(workshop_id, workshop_response) {
      // CALENDAR UPDATE WORKSHOP
      if(dashboard.$calendar != null && dashboard.$calendar.length > 0) {
        var eventObj = dashboard.$calendar.fullCalendar('clientEvents', workshop_id);

        if(typeof eventObj != 'undefined' && eventObj.length > 0) {
          var event_data = {
            className : [
              'workshop-status-' + workshop_response.workshop_status
							// dashboard.ws_active_event_class
            ]
          };

          if(workshop_response.user_subscribe_status)
            event_data.className.push('user-status-'+workshop_response.user_subscribe_status);

          eventObj = $.extend(eventObj[0], event_data);
          dashboard.$calendar.fullCalendar('updateEvent', eventObj);
        }
      // ROW UPDATE WORKSHOP
      } else if(dashboard.$row_subscribes.length > 0) {
        var $row_changed = dashboard.$row_subscribes.filter('[data-workshop-id="' + workshop_id + '"]');

        if(workshop_response.user_subscribe_status != null) {
          $row_changed.find('.status-icon')
            .attr('data-status-icon', workshop_response.user_subscribe_status)
            .attr('data-original-title', workshop_response.user_subscribe_status_text)
            .attr('title', workshop_response.user_subscribe_status_text);
        } else {
          var $row_parent = $row_changed.parents('.user-workshops-day').first();
          $row_changed.remove();

          // On unsub > close Simple Popin
          this.$popin.simplePopin('close');

          // If last row of user sub > remove parents
          if($row_parent.find('.row-workshop').length < 1)
            $row_parent.remove();
        }
      }
    },
    workshop_is_vsi : function() {
      return this.$popin.hasClass('workshop-is-type-vsi');
    },
    set_actions     : function(response) {
      var $actions = this.$ws_actions;

      // SUBSCRIBERS PART
      // Button:Subscribe
      var $button_subscribe = $actions.find('.btn-subscribe-to-workshop');
      // Subscribe:Update button url (action)
      if(typeof response.url_subscribe != 'undefined')
        $button_subscribe.attr('href', response.url_subscribe);

      // Subscribe:Update button text
      if(typeof response.workshop_nb_seats_left != 'undefined' || typeof response.form_data != 'undefined') {
        var nb_seats_left = null;
        // NOTE ~ trash but that's working
        if(typeof response.workshop_nb_seats_left != 'undefined')
          nb_seats_left = response.workshop_nb_seats_left;
        else
          nb_seats_left = response.form_data.workshop_nb_seats_left;

        if(nb_seats_left != null) {
          // No more seats = in waiting line
          var btn_subscribe_text  = (nb_seats_left < 1) ? "M'inscrire en file d'attente" : "M'inscrire à l'atelier";

          // Update
          $button_subscribe.find('.text').html(btn_subscribe_text);
        }
      }

      // Button:Unsubscribe
      var $button_unsubscribe = $actions.find('.btn-unsubscribe-to-workshop');
      // Unsubscribe:Update button url (action)
      if(typeof response.url_unsubscribe != 'undefined')
        $button_unsubscribe.attr('href', response.url_unsubscribe);

      var btn_unsubscribe_text  = "Me désinscrire de l'atelier";
      // Change button text for users in waiting line
      if(response.user_subscribe_status == 'waiting-seats')
        btn_unsubscribe_text = "Me désinscrire de la file d'attente";

      // Unsubscribe:Update button text
      $button_unsubscribe.find('.text').html(btn_unsubscribe_text);

      // Button:Validate subscription
      if(typeof response.url_validate_subscribe != 'undefined')
        $actions.find('.btn-valid-subscribe').attr('href', response.url_validate_subscribe);


      // ADMIN PART
      // Edit
      var $edit_action = $actions.find('.btn-edit-workshop');
      if($edit_action.length > 0 && typeof response.url_entity != 'undefined')
        $edit_action.attr('data-stealth-raven-loader', response.url_entity);

      // Delete
      var $delete_action = $actions.find('.btn-delete-workshop');
      if($delete_action.length > 0 && typeof response.url_delete != 'undefined')
        $delete_action.attr('href', response.url_delete);
    },
    set_marker      : function(latitude, longitude) {
      if(typeof this.google_maps != 'undefined') {
        if(this.marker != null)
        this.marker.setMap(null);

        this.marker = new google.maps.Marker({
          position:   { lat: latitude, lng: longitude },
          animation:  google.maps.Animation.DROP,
          map:        this.google_maps
        });

        this.google_maps.setCenter({ lat: latitude, lng: longitude });
      }
    },
    set_seats       : function(params) {
      // this.$ws_seats_left.show();

      // if(params.workshop_status == 'subscribes-closed' && dashboard.is_admin == false) {
      //   this.$ws_seats_left.hide();
      //   return;
      // }

      // Set seats lefts & colors
      var percent_left  = params.nb_seats_left * 100 / params.nb_seats;
      var seats_class   = (percent_left > 50) ? 'success' : ((percent_left > 0) ? 'warning' : 'danger');
      this.$ws_seats_left.find('.text')
        .removeClass('badge-danger badge-warning badge-success')
        .addClass('badge-' + seats_class)
        .html(Math.max(0, params.nb_seats_left));

      // Assign seats left in popin attribute and toggle "no-more-seats" CSS class
      this.$popin.attr('data-workshop-seats-left', Math.max(0, params.nb_seats_left))
        .toggleClass('workshop-no-more-seats', (params.nb_seats_left < 1));

      // Update nb people in waiting line
      var waiters_text = '';
      if(params.nb_waiters > 0)
        waiters_text = params.nb_waiters + ' personne'+ (params.nb_waiters>1?'s':'') +' en file d\'attente.';

      this.$ws_seats_left.find('.nb-waiting').html(waiters_text);
    },
    set_status      : function(params) {
      // Reset & assign workshop status (popin CSS class)
      this.$popin.removeClass(dashboard.ws_status.list.join(' '));
      if(typeof params.status_workshop != 'undefined' && dashboard.ws_status.exist(params.status_workshop))
        this.$popin.addClass(dashboard.ws_status.prefixe(params.status_workshop));


      // Reset & assign user status (icon attribute & popin CSS class)
      this.$popin.removeClass(dashboard.ws_user_status.list.join(' '));
      if(typeof params.status_user != 'undefined') {
        // Update user status in popin CSS class
        if(dashboard.ws_user_status.exist(params.status_user))
          this.$popin.addClass(dashboard.ws_user_status.prefixe(params.status_user));

        // Update user status icon = less CSS
        this.$ws_user_status.find('.status-icon').attr('data-status-icon', params.status_user);
      }

      // Assign user status text in workshop summary
      if(typeof params.status_user_text != 'undefined')
        this.$ws_user_status.find('.status-text').html(params.status_user_text);
    },
    set_has_come    : function(params) {
      this.$popin.removeClass('user-status-has-come');
      // if(params.workshop_status == 'subscribes-closed' && params.user_status == 'subscribed') {
        // Update has come text
        this.$ws_user_has_come.find('.has-come-text').html(params.user_has_come_text);
        // Add has-come CSS class
        if(params.user_has_come == true)
          this.$popin.addClass('user-status-has-come');
      // }
    },
    set_subscribers : function(subscribers, nb_seats, nb_seats_left) {
      if(typeof subscribers != 'undefined' && this.$ws_subs.length > 0) {
        // Reset table
        this.$ws_subbers_list.find('.subscriber-item').remove();

        // Emptying copy
        this.$ws_subbers_copy_text.html('');
        this.$ws_subbers_copy_mail.html('');

        // Add "hiddener" CSS class
        this.$ws_subs.addClass('no-subs');

        // Create subscribers object into Popin object
        this.subscribers = { };

        if(subscribers.length > 0) {
          // Remove "hiddener" CSS class
          this.$ws_subs.removeClass('no-subs');

          // Append subscribers to table
          for (var i = 0; i < subscribers.length; i++) {
            var suber = subscribers[i];
            var $suber = this.$suber_sample.clone();

            // Set subscriber into popin object
            this.subscribers[suber.id] = suber;

            // Set id subscribe in node data AND add user status CSS class
            $suber.attr('data-id-subscribe', suber.subscribe_id)
              .addClass(dashboard.ws_user_status.prefixe(suber.subscribe_status));

            if(suber.is_active == false)
              $suber.addClass('disabled');

            if(suber.is_vsi == true)
              $suber.addClass('is-vsi');

            var $checker_label  = $suber.find('.subber-check-presence');
            var $checker_box    = $checker_label.find('.checkbox');

            $checker_label.attr('for', 'checkbox-' + suber.id);
            $checker_box.attr('id', 'checkbox-' + suber.id);

            if(suber.subscriber_has_come == true)
              $checker_box.addClass('validated').attr('checked', true);

            // Set subscriber data
            $suber.find('._names').html(suber.lastname + ' ' + suber.firstname);
            $suber.find('._email').html(suber.email);

            // Reset & set status icon
            $suber.find('.subscriber-status').attr('data-status-icon', suber.subscribe_status)
              .attr('title', suber.subscribe_status_text);

            // Links:User profile
            if(typeof suber.user_url != 'undefined')
              $suber.find('.subscriber-link').attr('href', suber.user_url);

            // Links:Delete subscribe
            $suber.find('.btn-delete-subscribe').attr('href', suber.subscribe_delete_url);

            // Append subscriber to list
            this.$ws_subbers_list.append($suber);

            // Push copies text
            this.$ws_subbers_copy_text
              .append($('<div>' + suber.lastname + ' ' + suber.firstname + ' - ' + suber.email+'</div>'));

            this.$ws_subbers_copy_mail
              .append($('<span>' + ((i>0)?', ':'') + suber.lastname + ' ' + suber.firstname + ' &lt;' + suber.email+'&gt;</span>'));
          }

          // Generate status tooltip
          this.$ws_subs.find('.subscriber-status').tooltip();
        }
      }
    },
  },

  // Viewport very simple detector
  viewport : {
    current: function() {
      var $viewport_detected = dashboard.$core.find('.viewport-sizes > *:visible');
      return $viewport_detected.attr('data-viewport-size-slug');
    },
    is : function(size_slug) {
      return size_slug == this.current();
    }
  },

  // DASHBOARD.VARIABLES
  $calendar : null,
  is_admin  : false,

  // DASHBOARD.CONSTRUCT
  build : function(options) {
    // console.log("{dashboard.js} Let's go ! ~ ");
    var self = this;

    // Store options as settings (see DASH_SETTINGS)
    this.settings = options;

    // Nodes
    this.$html_body = $('html, body');
    this.$window    = $(window);
    this.$core = $('.app-core');
    this.$body = this.$core.find('.app-body');

    this.$core.on('click', '[data-toggle="print"]', function() {
      window.print();
    });

    // INITIALIZE : Tooltips
    this.$core.find('[data-toggle="tooltip"]').tooltip();

    this.$core.on('click', '[data-toggle="class-toggler"]', function() {
      if(typeof this.dataset.target != 'undefined' && typeof this.dataset.classCss != 'undefined')
        $(this.dataset.target).toggleClass(this.dataset.classCss);
    });

    // GENERATE : Workshop popin (more info about a workshop)
    this.$workshop_popin = this.$core.find('#workshop-popin-details');
    if(this.$workshop_popin.length > 0)
      this.workshop_popin.build(this.$workshop_popin);

    // Clic on workshop row
    this.$core.on('click', '.row-workshop', function() {
      var $clicked_row = $(this);
      var workshop_url = $clicked_row.data('load-workshop-popin');

      if(typeof workshop_url != 'undefined') {
        self.workshop_popin.load($clicked_row.data('load-workshop-popin'));
        self.$row_subscribes.removeClass('current');

        $clicked_row
        .addClass('current') // Set current to clicked row
        .parents('.user-workshops-day').first().after(self.$workshop_popin); // Change popin position
      }
    });

    this.$row_subscribes = this.$core.find('.row-workshop');

    if(typeof options.is_admin != 'undefined')
      this.is_admin = options.is_admin;

    if(typeof options.show_help_modal != 'undefined' && options.show_help_modal == true) {
      this.$core.find('#modal-welcome').modal('show').on('hidden.bs.modal', function() {
        $.fn.stealthRaven('fly', {
          url: '/dashboard/disable-help-modal'
        });
      });
    }

    // IF events > GENERATE : Calendar
    if(typeof options.events != 'undefined') {
      this.calendar_config = {
        // Classic params
        locale:       'fr',
        // timezone:     false,
        // weekends:     false,
        themeSystem:  'bootstrap4',
        defaultView:  'agendaWeek',
        nowIndicator: true,
        height:       'auto',
        header: {
          left:   'prev,next today',
          right:  'month,agendaWeek'
        },
        bootstrapFontAwesome: {
          close: 'nope icon-cancel',
          prev: 'nope icon-chevron-left',
          next: 'nope icon-chevron-right'
        },
        businessHours: {
          start: '08:00', end: '18:00',
        },
        views: {
          agenda : {
            minTime:    '07:00:00',
            maxTime:    '19:00:00',
            allDaySlot: false,
            selectable: options.is_admin
          },
					month: {
						timeFormat: 'H:mm',
					}
        },

        // Events
        events      : options.events,

        // Range
        validRange  : {
          start : options.calendar_start,
          end   : options.calendar_end
        },

        // EVENTS : Click event
        eventClick  : function(event, jsEvent, view) {
					/*var eventsObj = dashboard.$calendar.fullCalendar('clientEvents');

					// Need to loop on ALL events in order to manage their CSS class
					for (var i = 0; i < eventsObj.length; i++) {
						var eventObj = eventsObj[i];
						var positionCSSClassActive = $.inArray(dashboard.ws_active_event_class, eventObj.className);

						if ( ~positionCSSClassActive ) {
							// Reset CSS class active from events on calendar
							eventObj.className.splice(positionCSSClassActive, 1);
						} else if ( eventObj.id == event.id ) {
							// Add CSS class active on workshop event clicked
							eventObj.className.push(dashboard.ws_active_event_class);
						}

						// Update event on calendar
						dashboard.$calendar.fullCalendar('updateEvent', eventObj);
					}*/

					// Open and load workshop popin
          self.workshop_popin.load(event.url);

          // prevent url open, cause url = event data
          return false;
        },

        //
        eventRender : function(event, element) {
					// console.log(event);
					var event_footer = '';
          if('undefined' != typeof event.subbers_amount && event.subbers_amount > 0)
            event_footer = ('<span class="amount">' + event.subbers_amount + '</span> <span class="text">inscription' + ((event.subbers_amount > 1) ? 's' : '') + '</span>')
					else if ('undefined' != typeof event.nb_seats_left && event.nb_seats_left > 0)
            event_footer = ('<span class="amount">' + event.nb_seats_left + '</span> <span class="text">place' + ((event.nb_seats_left > 1) ? 's' : '') + ' restante' + ((event.nb_seats_left > 1) ? 's' : '') + '</span>')

					if(event_footer != '')
						element.find('.fc-content').after('<div class="fc-footer">' + event_footer + '</div>')
        },

        // RENDER
        viewRender : function(view, element) {
          // Add period into dashboard title
          self.$core.find('.planning-period').html(view.title);

          // Add type format into print button
          var view_format_text = '';
          switch(view.type) {
            case 'agendaWeek':
              view_format_text = ' (semaine)';
              break;
            case 'month':
              view_format_text = ' (mois)';
              break;
            case 'agendaDay':
              view_format_text = ' (jour)';
              break;
          }
          self.$core.find('.planning-format').html(view_format_text);
        }
      };

      // Change calendar view according to current viewport
      dashboard.change_view();
      this.$window.resize(function() { dashboard.change_view(); });

      // GENERATE : Render .fullCalendar() and stock it
      this.$calendar = this.$body.find('.workshop-calendar').fullCalendar(this.calendar_config);

      this.calendar = this.$calendar.fullCalendar('getCalendar');
    }
  },
  previous_viewport : null,
  change_view : function() {
    if(this.previous_viewport != this.viewport.current()) {
      // For device with XS viewport > change agendaWeek to agendaDay
      if(this.viewport.is('xs')) {
        this.calendar_config.header = {
          left:  'prev,next',
          right: 'today'
        };
        this.calendar_config.defaultView = 'agendaDay';
      } else {
        this.calendar_config.header = {
          left:  'prev,next today',
          right: 'month,agendaWeek'
        };
        this.calendar_config.defaultView = 'agendaWeek';
      }

      // If fullCalendar is existing > Update it
      if (this.$calendar != null) {
        // Update options
        this.$calendar.fullCalendar('option', this.calendar_config);

        // Change view to defaultView if the current view is not available to display
        if (null === this.calendar_config.header.right.match(this.$calendar.fullCalendar('getView').type))
          this.$calendar.fullCalendar('changeView', this.calendar_config.defaultView);
      }

      // Assign current view to previous
      this.previous_viewport = this.viewport.current();
    }
  }
};


//
// Doc ready
//

(function() {
  DASH_SETTINGS = (typeof DASH_SETTINGS != 'undefined') ? DASH_SETTINGS : {};
  dashboard.build(DASH_SETTINGS);
})();


// dashboard.js
module.exports = dashboard;
