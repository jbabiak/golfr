
(function ($, Drupal) {
  Drupal.behaviors.courseTeeColorChange = {
    attach: function (context, settings) {
      var autocompleteFieldSelector = '#edit-field-grint-course-0-value';
      var textFieldSelector = '#edit-field-grint-tee-color-0-value'; // Change to your text field ID

      $(autocompleteFieldSelector, context).each(function () {
        var $autocompleteField = $(this);
        if (!$autocompleteField.hasClass('course-tee-color-processed')) {
          $autocompleteField.addClass('course-tee-color-processed');
          $autocompleteField.on('autocompleteclose', function () {
            var selectedValue = $(this).val();
            console.log('Selected Course:', selectedValue);
            $(textFieldSelector).val('');
            $('#edit-field-grint-course-mr-0-value').val('');
            $('#edit-field-grint-course-ms-0-value').val('');
            $('#edit-field-grint-course-lr-0-value').val('');
            $('#edit-field-grint-course-ls-0-value').val('');

            $.ajax({
              url: '/hacks_forms/tee_color/' + encodeURIComponent(selectedValue),
              success: function (data) {
                // Replace the text field with a select field
                var $textField = $(textFieldSelector);
                var attributes = $textField.prop("attributes");

                // Create a select field and copy attributes from the text field
                var $selectField = $('<select>');
                $.each(attributes, function() {
                  $selectField.attr(this.name, this.value);
                });

                // Populate the select field with options from AJAX response
                $.each(data, function (key, value) {
                  $selectField.append($('<option></option>').attr('value', key).text(value));
                });

                // Replace the text field with the select field
                $textField.replaceWith($selectField);


                // Add event listener to change back to text field when option is selected
                $selectField.on('change', function () {
                  var selectedTeeColor = $(this).find('option:selected').text();
                  var matchMR = selectedTeeColor.match(/MR:\s*([\d.]+)/);
                  var matchMS = selectedTeeColor.match(/MS:\s*([\d.]+)/);
                  var matchLR = selectedTeeColor.match(/LR:\s*([\d.]+)/);
                  var matchLS = selectedTeeColor.match(/LS:\s*([\d.]+)/);

                  var mrValue = matchMR ? matchMR[1] : '';
                  var msValue = matchMS ? matchMS[1] : '';
                  var lrValue = matchLR ? matchLR[1] : '';
                  var lsValue = matchLS ? matchLS[1] : '';

                  // Disable the fields to prevent editing
                  $('#edit-field-grint-course-mr-0-value').val(parseFloat(mrValue));
                  $('#edit-field-grint-course-ms-0-value').val(parseFloat(msValue));
                  $('#edit-field-grint-course-lr-0-value').val(parseFloat(lrValue));
                  $('#edit-field-grint-course-ls-0-value').val(parseFloat(lsValue));

                  var attributes = $selectField.prop("attributes");

                  // Create a text field and copy attributes from the select field
                  var $newTextField = $('<input type="text">');
                  $.each(attributes, function() {
                    $newTextField.attr(this.name, this.value);
                  });
                  $newTextField.val($(this).val());

                  // Replace the select field with the new text field
                  $selectField.replaceWith($newTextField);
                });
              }
            });
          });
        }
      });
    }
  };
})(jQuery, Drupal);



