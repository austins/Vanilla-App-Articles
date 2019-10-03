(function(window, $) {
    $(document)
    // Categories->Delete().
    // Hide/reveal the delete options when the DeleteDiscussions checkbox is un/checked.
        .on('change', '[name=ContentAction]', function () {
            if ($(this).val() === 'move') {
                $('[name=ReplacementCategoryID]').trigger('change');
                $('#ReplacementCategory').slideDown('fast');
                $('#DeleteCategory').slideUp('fast');
            } else {
                $('[name=ConfirmDelete]').trigger('change');
                $('#ReplacementCategory').slideUp('fast');
                $('#DeleteCategory').slideDown('fast');
            }
        })
        .on('change', '[name=ReplacementCategoryID]', function () {
            $('[name=Proceed]').prop('disabled', !$(this).val());
        })
        .on('change', '[name=ConfirmDelete]', function () {
            $('[name=Proceed]').prop('disabled', !$(this).prop('checked'));
        })
        // Categories->Delete()
        // Hide onload if unchecked.
        .on('contentLoad', function (e) {
            $('#ReplacementCategory, #DeleteCategory', e.target).hide();

            if ($('[name$=MoveContent]', e.target).is('checked')) {
                if ($('[name$=MoveContent]', e.target).val() === 'move') {
                    $('#ReplacementCategory').slideDown('fast');
                } else {
                    $('#DeleteCategory').slideDown('fast');
                }
            }

            $('#Form_Proceed').prop('disabled', true);
        })
    ;
})(window, jQuery);

jQuery(document).ready(function($) {
    // Set custom category permissions display
    var displayCategoryPermissions = function() {
        var checked = $('#Form_CustomPermissions').prop('checked');
        if (checked) {
            $('.CategoryPermissions').show();
        } else {
            $('.CategoryPermissions').hide();
        }
    };
    $('#Form_CustomPermissions').click(displayCategoryPermissions);
    displayCategoryPermissions();
});
