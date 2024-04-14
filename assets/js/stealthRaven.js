//
// Stealth Raven
//


(function($) {
  // Tool box
  var raven = {
    //
    // Tools:Digits
    ten : function(i) {
      return (i < 10 ? '0' : '') + i;
    },
    //
    // Tools:Date
    toDatetimeLocal : function(date) {
      var
        YYYY = date.getFullYear(),
        MM = this.ten(date.getMonth() + 1),
        DD = this.ten(date.getDate()),
        HH = this.ten(date.getHours()),
        II = this.ten(date.getMinutes()),
        SS = this.ten(date.getSeconds())
      ;
      return YYYY + '-' + MM + '-' + DD + 'T' + HH + ':' + II + ':' + SS;
    },
    //
    // Tools:Ajax
    xhr           : null,
    loading_class : 'raven-is-loading',
    abort : function() {
      // Abort() if ajax request already sent
      if(this.xhr != null && this.xhr.readyState != 4)
        this.xhr.abort();

      // Remove loading CSS class
      this.$body.removeClass(this.loading_class);
    },
    fly : function(params) {
      var raven = this;

      // Pre-abort
      this.abort();

      // var params = arguments[0];
      // params & url : required
      if('undefined' == typeof params || 'undefined' == typeof params.url) {
        this.error('You must define an url before trying to fly !');
        return;
      }

      // params
      var url   = params.url;
      var data  = (typeof params.data == 'undefined') ? {} : params.data;
      var done  = (typeof params.done == 'undefined') ? null : params.done;
      var key   = url + '' + (typeof data == 'object' ? JSON.stringify(data) : data);

      // loading class
      this.loading_class = 'raven-is-loading';
      if(typeof params.loading_class != 'undefined')
        this.loading_class = params.loading_class;

      // Add loading CSS class
      this.$body.addClass(this.loading_class);

      // TODO Cache managment : can't work when calling the same page after
      // a subs <> unsubs (call the same page so don't subs the user to workshop
      // after unsub to the same one)
      /*if(typeof raven_responses[key] == 'undefined') {*/
      // Execute jquery ajax function and get XHR
      this.xhr = $.ajax({
        method  : 'POST',
        url     : url,
        data    : data,
        error   : function(jqXHR, status, error) {
          // Abort raven (remove body class, ...)
          raven.abort();
          // Print problem if not an abort()
          if(error != 'abort')
            raven.error(error);
        },
        success : function(response) {
          raven.abort();

          // Do some magic with query_status
          if(typeof response == 'object' && typeof response.query_status != 'undefined') {
            // #99 : simply reload page
            if(response.query_status == 99) {
              location.reload();
              return;
            }
          }

          if(done !== null)
            done(response);

          if(typeof response == 'object' && typeof response.message_status != 'undefined') {
            alert(response.message_status);
            // TODO Add stylized toaster ?
          }

          // Reset XHR
          raven.xhr = null;
          // raven_responses[key] = response; // see TODO above
        }
      });
      // see TODO above
      /*} else {
        var response = raven_responses[key];

        raven.$body.removeClass(raven.loading_class);

        if(done !== null)
          done(response);

        if(typeof response.message_status != 'undefined')
          alert(response.message_status);
      }*/
    },
    //
    // Tools:Forms
    fill_form   : function(params) {
      // raven.log('raven.fill_form()');
      // params & data : required
      if(typeof params == 'undefined' || typeof params.data == 'undefined') {
        this.error('You must define some data and an id entity to fill the form !');
        return;
      }

      var raven = this;

      this.$nodes.filter('form').each(function() {
        var $form = $(this);
        var $input = $form.find('input, textarea, select, checkbox');

        if(typeof params.data != 'undefined') {
          $input.each(function() {
            // If input ID is an item from "params.data"
            if(params.data.hasOwnProperty(this.id)) {
              var value = params.data[this.id];

							if(typeof(this.attributes.type) != 'undefined') {
								// If it's a checkbox <input> then put "checked" to true|false and change value to 1|0
								if(this.attributes.type.value == 'checkbox') {
									this.checked = value;
									value = (value == true) ? '1' : '0';
								}

								// If it's a datetime-local <input> then format value to valid datetime value
								// if(this.type.match(/datetime/g) != null) {     // NOTE: This test isn't working on Firefox, datetime-local type doesn't exist, so property too, but exist in Attributes object
								if(this.attributes.type.value.match(/datetime-local/g) != null) {
									value = raven.toDatetimeLocal(new Date(value));
								}
							}

              // Put "params.data" value into <input value>
              $(this).val(value);

							// DEBUG
							// raven.log(this.id + ' : ' + value);
            }
          });
        }

        // Fill id input hidden (= edit) or removing it
        if((typeof params.id_entity != 'undefined') && parseInt(params.id_entity) > 0)
          $form.append($('<input class="stealth-raven-id-entity" type="hidden" name="id" value="' + params.id_entity + '" />'));
        else
          $form.find('.stealth-raven-id-entity').remove();
      });

    },
    reset_form  : function(params) {
      // raven.log('raven.reset_form()');
      var $forms = null;

      if (this.$nodes !== null)
        $forms = this.$nodes.filter('form');

      // Use params
      if (typeof params !== 'undefined') {
        // Override $forms to use
        if (typeof params.$forced_forms !== 'undefined')
          $forms = params.$forced_forms;
      }

      if ($forms == null)
        return false;

      // Reset each forms <input|textarea> / <select> (TODO Add radio / checkbox support)
      $forms.each(function() {
        var $form     = $(this);
        var $texts    = $form.find('input, textarea');
        var $selects  = $form.find('select');

        // Reset classic <input|textarea>
        $texts.not('[type="hidden"], [data-stealth-raven-dont-wash="true"]').val('');
        // Reset <input|textarea> with a front wanted value
        $texts.filter('[data-stealth-raven-wash-value]').each(function() {
          $(this).val($(this).attr('data-stealth-raven-wash-value'));
        });

        // Reset <select>
        $selects.not('[data-stealth-raven-dont-wash="true"]').each(function() {
          var $select = $(this);
          var $options = $select.find('option');
          var select_val = $options.first().val();

          if ($options.filter('[selected]').length > 0)
            select_val = $options.filter('[selected]').val();

          $select.val(select_val);
        });
        // Reset <select> with a front wanted value
        $selects.filter('[data-stealth-raven-wash-value]').each(function() {
          var $select = $(this);
          var $options = $select.find('option');

          if (typeof $select.attr('data-stealth-raven-wash-value') !== 'undefined') {
            $options.each(function() {
              if (this.value == $select.attr('data-stealth-raven-wash-value')) {
                $select.val(this.value);
                return false;
              }
            })
          }
        });

        // Reset stealth raven edit id
        $form.find('.stealth-raven-id-entity').remove();
      });
    },
    //
    // Tools:Console
    log_prefixer : function(type) { return '%c{StealthRaven:'+type.toUpperCase()+'}'; },
    log   : function(str) {
      if(typeof console != 'undefined')
          console.log(raven.log_prefixer('log'), "color: #6f42c1", str);
    },
    error : function(str) {
      if(typeof console != 'undefined')
        console.error(raven.log_prefixer('error'), 'color: #dc3545', str);
    },
    warn  : function(str) {
      if(typeof console != 'undefined')
        console.warn(raven.log_prefixer('warning'), "color: #ffc107", str);
    },
    table : function(list) {
      if(typeof console != 'undefined') {
        console.log(raven.log_prefixer('table'), 'color: #343a40');
        console.table(list);
      }
    },
    //
    // Useful nodes
    $body   : null,
    $nodes  : null,
    //
    // Init
    init : function() {
      this.$body = $('body');
    },
  };

  // Init raven
  raven.init();

  // jQuery "bridge"
  $.fn.stealthRaven = function(options) {
    var is_init_nodes = (typeof options == 'object' || typeof options == 'undefined');
    var $nodes = $(this);

    if(is_init_nodes === false) {
      // Execute a raven's tool ?
      if(typeof options == 'string') {
        // raven.log('Execute tool: '+options);

        // Retrieve function to execute from Arguments JS object
        var tool_to_exec = [].shift.apply(arguments);

        // Execute function !
        if(typeof raven[tool_to_exec] != 'undefined') {
          // Set nodes
          raven.$nodes = $nodes;
          return raven[tool_to_exec].apply(raven, arguments);
        } else {
          raven.error('The tool "' + tool_to_exec + '" doesn\'t exist, try with an existing tool () !');
        }

      } else {
        raven.error('The option given to Stealth Raven is not supported');
      }

      return;
    } else {
      // raven.log('Init !');
      var settings = $.extend({
        stop_propagations : true,
        form_autowash     : true, // clear form after submit
        raven             : raven,
        /*
        loading_class     : 'custom-loading-class',
        message_query_ok  : 'Query OK !',
        message_query_nok : 'Something goes wrong with query',
        callback_done : function($node_origin (form|button), response) {}
        */
      }, options);

      // Init events
      $nodes.filter('form').on('submit', function(e) {
        // raven.log("I'm a <form> !");
        var $form = $(this);

        // Add loading class to form
        $form.addClass('raven-form-is-loading');

        // Querying
        raven.fly({
          url:  $form.attr('action'),
          data: $form.serialize(),
          done: function(response) {
            if(typeof response.exception !== 'undefined')
              raven.error(response.exception);

            // Remove loading class to form
            $form.removeClass('raven-form-is-loading');

            if(settings.callback_done)
              settings.callback_done($form, response);

            if(true === settings.form_autowash && response.query_status == 1)
              raven.reset_form({ $forced_forms : $form });
          }
        });

        // Stop propagation
        e.preventDefault();
        e.stopPropagation();
        return false;
      });

      $nodes.filter('button, a').on('click', function(e) {
        // raven.log("I'm a <button> !");
        var $button         = $(this);
        var url             = $button.attr('href');
        var confirm         = $button.attr('data-stealth-raven-confirm');
        var fill_form_name  = $button.attr('data-stealth-raven-fill-form');
        var run_action      = true;
        var no_ajax         = false;
        var stop_propag     = true;

        if(typeof $button.attr('data-stealth-raven-loader') != 'undefined')
          url = $button.attr('data-stealth-raven-loader');

        // url : required
        if(typeof url == 'undefined') {
          raven.error('You must define a href or a data-stealth-raven-loader attribute !');
          return;
        }

        if(typeof $button.data('stealth-raven-no-ajax') == 'boolean')
          no_ajax = $button.data('stealth-raven-no-ajax');

        if(typeof confirm != 'undefined')
          run_action = window.confirm(confirm);

        if(settings.callback_before)
          run_action = settings.callback_before($button);

        // TODO ~ Remove no_ajax ? stupid ?
        if(false === no_ajax) {
          if(true === run_action) {
            if(typeof url != 'undefined') {
              raven.fly({
                url: url,
                done: function(response) {
                  if(typeof fill_form_name != 'undefined' && typeof response.form_data != 'undefined') {
                    raven.$nodes = raven.$body.find('form[name="' + fill_form_name + '"]');
                    raven.fill_form({ data : response.form_data, id_entity : response.id_entity });
                  }

                  if(settings.callback_done)
                    settings.callback_done($button, response);
                }
              });
            } else {
              raven.error('You must define an url');
            }
          }
        } else {
          // no_ajax == true = follow link to submit action
          if(run_action === true)
            stop_propag = false;
        }

        // To stop propagation or not to stop propagation
        if(stop_propag === true) {
          // Stop propagation
          e.preventDefault();
          e.stopPropagation();
          return false;
        }
      });
    }

    return this;
  };
})(jQuery);
