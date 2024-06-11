(function ($, Drupal) {
  Drupal.behaviors.myCustomBehavior = {
    attach: function (context, settings) {
      $('.round-container', context).click(function () {
        $('.round-container').removeClass('clicked');

        // Get the associated radio button ID from the data attribute
        var radioId = $(this).data('radio-id');
        $(this).addClass('clicked');
        // Check the radio button with the corresponding ID
        $('#' + radioId).prop('checked', true);
      });
    }
  };
})(jQuery, Drupal);
