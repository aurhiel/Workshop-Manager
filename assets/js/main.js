require('../css/main.scss');

/* // Useful tool for datetimeLocal format
Date.prototype.toDatetimeLocal =
  function toDatetimeLocal() {
    var
      date = this,
      ten = function (i) {
        return (i < 10 ? '0' : '') + i;
      },
      YYYY = date.getFullYear(),
      MM = ten(date.getMonth() + 1),
      DD = ten(date.getDate()),
      HH = ten(date.getHours()),
      II = ten(date.getMinutes()),
      SS = ten(date.getSeconds())
    ;
    return YYYY + '-' + MM + '-' + DD + 'T' + HH + ':' + II + ':' + SS;
  };
*/

(function() {
  // console.log("Hi ! I'm the main.js script");

  // Ugly fix for Symfony's HTML5 input value
  $('input[type="datetime-local"]').each(function() {
    var current_value = $(this).attr('value');
    if('undefined' !== typeof(current_value) && 'Z' === current_value[current_value.length - 1])
      $(this).val(current_value.substring(0, current_value.length - 1))
  });
})();
