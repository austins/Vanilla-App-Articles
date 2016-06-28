jQuery(document).ready(function($) {
    // /settings/articles/deletecategory/
    // Hide/reveal the delete options when the DeleteArticles checkbox is un/checked.
    $('[name$=DeleteArticles]').click(function() {
        if ($(this).attr('checked')) {
            $('#ReplacementCategory,#ReplacementWarning').slideDown('fast');
            $('#DeleteArticles').slideUp('fast');
        } else {
            $('#ReplacementCategory,#ReplacementWarning').slideUp('fast');
            $('#DeleteArticles').slideDown('fast');
        }
    });

    // /settings/articles/deletecategory/
    // Hide onload if unchecked
    if (!$('[name$=DeleteArticles]').attr('checked')) {
        $('#ReplacementCategory,#ReplacementWarning').hide();
        $('#DeleteArticles').show();
    } else {
        $('#ReplacementCategory,#ReplacementWarning').show();
        $('#DeleteArticles').hide();
    }

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
