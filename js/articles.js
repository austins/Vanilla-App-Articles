jQuery(document).ready(function ($) {
    // Map plain text category to url code
    $("#Form_Name").keyup(function (event) {
        if ($('#Form_UrlCodeIsDefined').val() == '0') {
            $('#UrlCode').show();
            var val = $(this).val().replace(/[ \/\\&.?;,<>'"]+/g, '-');
            val = val.replace(/\-+/g, '-').toLowerCase();
            $("#Form_UrlCode").val(val);
            $("#UrlCode").find("span").text(val);
        }
    });
    // Make sure not to override any values set by the user.
    $('#UrlCode').find('span').text($('#UrlCode').find('input').val());
    $("#Form_UrlCode").focus(function () {
        $('#Form_UrlCodeIsDefined').val('1')
    });
    $('#UrlCode input, #UrlCode a.Save').hide();

    // Reveal input when "change" button is clicked
    $('#UrlCode a, #UrlCode span').click(function () {
        $('#UrlCode').find('input,span,a').toggle();
        $('#UrlCode').find('span').text($('#UrlCode').find('input').val());
        $('#UrlCode').find('input').focus();
        return false;
    });

    // /settings/articles/deletecategory/
    // Hide/reveal the delete options when the DeleteArticles checkbox is un/checked.
    $('[name$=DeleteArticles]').click(function () {
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

    // Enable multicomplete on selected inputs
    $('.MultiComplete').livequery(function () {
        $(this).autocomplete(
            gdn.url('/dashboard/user/autocomplete/'),
            {
                minChars: 1,
                multiple: false,
                scrollHeight: 220,
                selectFirst: true
            }
        );
    });

    // Auto size text boxes.
    if ($.autogrow)
        $('textarea.TextBox').livequery(function () {
            $(this).autogrow();
        });

    // Threaded comment replies.
    // Hide/reveal the comments when the comment link is clicked
    $('a.ReplyLink').click(function (e) {
        e.preventDefault();

        var commentBox = $('#CommentBox');
        commentBox.insertAfter($(this));

        // Add the ParentCommentID to the form as a hidden field.
        var parentCommentID = commentBox.closest('.ItemComment').attr('id').replace(/[^\d.]/g, '');
        $('#Form_Comment').append('<input id="Form_ParentCommentID" name="ParentCommentID" type="hidden" value="' + parentCommentID + '" />');

        $('#CommentBox').find('textarea').focus().blur(function() {
            // Hide the form on blur (de-focus) if empty.
            if (this.value == '') {
                commentBox.insertAfter($('#Comments'));
                $('#Form_Comment').find('#Form_ParentCommentID').remove();
            }
        });
    });
});
