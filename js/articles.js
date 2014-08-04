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
    });

    function resetCommentBoxPlacement() {
        $('#CommentBox').insertAfter($('#Comments'));
        $('#Form_Comment').find('#Form_ParentCommentID').remove();
    }

    // If the #CommentBox form is not in the initial position due to threaded commenting
    // and all required fields are empty, then move the form block back to the initial position.
    $(document).on('click', function(e) {
        if (!$('#Comments').next('div').is($('#CommentBox'))) {
            var commentBox = $('#CommentBox');

            if (!$(e.target).is('a.ReplyLink') && $(e.target).closest(commentBox).length === 0) {
                // Hide the form on blur (de-focus) if empty.
                if ($('#Form_GuestName').length && ($('#Form_GuestName').val() == '')
                    && ($('#Form_GuestEmail').val() == '') && ($('#Form_Body').val() == ''))
                    resetCommentBoxPlacement();
                else if ($('#Form_Body').val() == '')
                    resetCommentBoxPlacement();
            }
        }
    });

    /* Comment Options */
    // Edit comment
    $('a.EditComment').livequery('click', function() {
        var btn = this;
        var container = $(btn).parents('li.ItemComment');
        $(container).addClass('Editing');
        var parent = $(container).find('div.Comment');
        var msg = $(parent).find('div.Message');
        $(parent).find('div.Meta span:last').after('<span class="TinyProgress">&#160;</span>');
        if ($(msg).is(':visible')) {
            $.ajax({
                type: "GET",
                url: $(btn).attr('href'),
                data: 'DeliveryType=VIEW&DeliveryMethod=JSON',
                dataType: 'json',
                error: function(xhr) {
                    gdn.informError(xhr);
                },
                success: function(json) {
                    json = $.postParseJson(json);

                    $(msg).after(json.Data);
                    $(msg).hide();
                    $(document).trigger('EditCommentFormLoaded', [container]);
                },
                complete: function() {
                    $(parent).find('span.TinyProgress').remove();
                    $(btn).closest('.Flyout').hide().closest('.ToggleFlyout').removeClass('Open');
                }
            });
        } else {
            $(parent).find('div.EditCommentForm').remove();
            $(parent).find('span.TinyProgress').remove();
            $(msg).show();
        }

        $(document).trigger('CommentEditingComplete', [msg]);
        return false;
    });
    // Reveal the original message when cancelling an in-place edit.
    $('.Comment .Cancel a').livequery('click', function() {
        var btn = this;
        $(btn).parents('.Comment').find('div.Message').show();
        $(btn).parents('.CommentForm, .EditCommentForm').remove();
        return false;
    });

    // Delete comment
    $('a.DeleteComment').popup({
        confirm: true,
        confirmHeading: gdn.definition('ConfirmDeleteCommentHeading', 'Delete Comment'),
        confirmText: gdn.definition('ConfirmDeleteCommentText', 'Are you sure you want to delete this comment?'),
        followConfirm: false,
        deliveryType: 'BOOL', // DELIVERY_TYPE_BOOL
        afterConfirm: function(json, sender) {
            var row = $(sender).parents('li.ItemComment');
            if (json.ErrorMessage) {
                $.popup({}, json.ErrorMessage);
            } else {
                // Remove the affected row
                $(row).slideUp('fast', function() {$(this).remove();});
                gdn.processTargets(json.Targets);
            }
        }
    });
});
