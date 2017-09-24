var $ = window.jQuery;
Drupal.behaviors.pageLayout = {
  attach: function (context) {
    'use strict';
    var pathInput = $(context).find('input[name="path"]');

    // If a path input exists, add an onchange listener.
    if (pathInput.length) {
      pathInput.on('change', function () {
        // Disable the submit button.
        $(document).find('.ui-dialog button').attr('disabled', true);
      });
    }
  }
};
