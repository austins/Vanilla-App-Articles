jQuery(document).ready(function($) {
    // Map plain text category to url code
    $("#Form_Name").keyup(function(event) {
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
    $("#Form_UrlCode").focus(function() {
        $('#Form_UrlCodeIsDefined').val('1')
    });
    $('#UrlCode input, #UrlCode a.Save').hide();

    // Reveal input when "change" button is clicked
    $('#UrlCode a, #UrlCode span').click(function() {
        $('#UrlCode').find('input,span,a').toggle();
        $('#UrlCode').find('span').text($('#UrlCode').find('input').val());
        $('#UrlCode').find('input').focus();
        return false;
    });

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

    // Enable multicomplete on selected inputs
    $('.MultiComplete').livequery(function() {
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
        $('textarea.TextBox').livequery(function() {
            $(this).autogrow();
        });

    // Threaded comment replies.
    // Hide/reveal the comments when the comment link is clicked
    $('a.ReplyLink').click(function(e) {
        e.preventDefault();

        var commentBox = $('#CommentBox');
        commentBox.insertAfter($(this));

        // Add the ParentArticleCommentID to the form as a hidden field.
        var parentArticleCommentID = commentBox.closest('.ItemComment').attr('id').replace(/[^\d.]/g, '');
        $('#Form_Comment').append('<input id="Form_ParentArticleCommentID" name="ParentArticleCommentID" type="hidden" value="' + parentArticleCommentID + '" />');
    });

    function resetCommentBoxPlacement() {
        $('#CommentBox').insertAfter($('#Comments'));
        $('#Form_Comment').find('#Form_ParentArticleCommentID').remove();
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

    function resetCommentForm(sender) {
        var parent = $(sender).parents('.CommentForm, .EditCommentForm');
        $(parent).find('.Preview').remove();
        $(parent).find('.TextBoxWrapper').show();
        $('.TinyProgress').remove();
    }

    // Utility function to clear out the comment form
    function clearCommentForm(sender) {
        var container = $(sender).parents('li.Editing');
        $(container).removeClass('Editing');
        $('div.Popup,.Overlay').remove();
        var frm = $(sender).parents('div.CommentForm, .EditCommentForm');
        frm.find('textarea').val('');
        frm.find('input:hidden[name$=ArticleCommentID]').val('');
        frm.find('div.Errors').remove();
        $('div.Information').fadeOut('fast', function() {
            $(this).remove();
        });
        $(sender).closest('form').trigger('clearCommentForm');
    }

    // Set up paging
    if ($.morepager)
        $('.MorePager').morepager({
            pageContainerSelector: 'ul.Comments',
            afterPageLoaded: function() {
                $(document).trigger('CommentPagingComplete');
            }
        });

    // Hijack comment form button clicks.
    $('.CommentButton, a.PreviewButton').livequery('click', function() {
        var btn = this;
        var parent = $(btn).parents('div.CommentForm, div.EditCommentForm');
        var frm = $(parent).find('form');
        var textbox = $(frm).find('textarea');
        var inpArticleCommentID = $(frm).find('input:hidden[name$=ArticleCommentID]');
        var type = 'Post';
        var preview = $(btn).hasClass('PreviewButton');
        if (preview) {
            type = 'Preview';
            // If there is already a preview showing, kill processing.
            if ($('div.Preview').length > 0 || jQuery.trim($(textbox).val()) == '')
                return false;
        }

        // Post the form, and append the results to #Discussion, and erase the textbox
        var postValues = $(frm).serialize();
        postValues += '&DeliveryType=VIEW&DeliveryMethod=JSON'; // DELIVERY_TYPE_VIEW
        postValues += '&Type=' + type;
        var articleID = $(frm).find('[name$=ArticleID]');
        articleID = articleID.length > 0 ? articleID.val() : 0;
        var tKey = $(frm).find('[name$=TransientKey]');
        var prefix = tKey.attr('name').replace('TransientKey', '');
        // Get the last comment id on the page
        var comments = $('ul.Comments li.ItemComment');
        var lastComment = $(comments).get(comments.length - 1);
        var lastArticleCommentID = $(lastComment).attr('id');
        if (lastArticleCommentID)
            lastArticleCommentID = lastArticleCommentID.indexOf('Article_') == 0 ? 0 : lastArticleCommentID.replace('Comment_', '');
        else
            lastArticleCommentID = 0;

        postValues += '&' + prefix + 'LastArticleCommentID=' + lastArticleCommentID;
        var action = $(frm).attr('action');
        if (action.indexOf('?') < 0)
            action += '?';
        else
            action += '&';

        if (articleID > 0) {
            action += 'articleid=' + articleID;
        }

        $(frm).find(':submit').attr('disabled', 'disabled');
        $(parent).find('a.Back').after('<span class="TinyProgress">&#160;</span>');

        $(frm).triggerHandler('BeforeSubmit', [frm, btn]);
        $(':submit', frm).addClass('InProgress');
        $.ajax({
            type: "POST",
            url: action,
            data: postValues,
            dataType: 'json',
            error: function(xhr) {
                gdn.informError(xhr);
            },
            success: function(json) {
                json = $.postParseJson(json);

                var processedTargets = false;
                // If there are targets, process them
                if (json.Targets && json.Targets.length > 0)
                    gdn.processTargets(json.Targets);

                // If there is a redirect url, go to it
                if (json.RedirectUrl != null && jQuery.trim(json.RedirectUrl) != '') {
                    resetCommentForm(btn);
                    clearCommentForm(btn);
                    window.location.replace(json.RedirectUrl);
                    return false;
                }

                // Remove any old popups
                if (json.FormSaved == true)
                    $('div.Popup,.Overlay').remove();

                var commentID = json.ArticleCommentID;

                // Assign the comment id to the form if it was defined
                if (commentID != null && commentID != '') {
                    $(inpArticleCommentID).val(commentID);
                }

                // Remove any old errors from the form
                $(frm).find('div.Errors').remove();
                if (json.FormSaved == false) {
                    $(frm).prepend(json.ErrorMessages);
                    json.ErrorMessages = null;
                } else if (preview) {
                    // Reveal the "Edit" button and hide this one
                    $(btn).hide();
                    $(parent).find('.WriteButton').show();

                    $(frm).trigger('PreviewLoaded', [frm]);
                    $(frm).find('.TextBoxWrapper').hide().after(json.Data);

                } else {
                    // Clean up the form
                    if (processedTargets)
                        btn = $('div.CommentForm :submit, div.EditCommentForm :submit');

                    resetCommentForm(btn);
                    clearCommentForm(btn);

                    // If editing an existing comment, replace the appropriate row
                    var existingCommentRow = $('#Comment_' + commentID);
                    if (processedTargets) {
                        // Don't do anything with the data b/c it's already been handled by processTargets
                    } else if (existingCommentRow.length > 0) {
                        existingCommentRow.after(json.Data).remove();
                        $('#Comment_' + commentID).effect("highlight", {}, "slow");
                    } else {
                        gdn.definition('LastArticleCommentID', commentID, true);
                        // If adding a new comment, show all new comments since the page last loaded, including the new one.
                        if (gdn.definition('PrependNewComments') == '1') {
                            $(json.Data).prependTo('ul.Comments');
                            $('ul.Comments li:first').effect("highlight", {}, "slow");
                        } else {
                            $(json.Data).appendTo('ul.Comments');
                            $('ul.Comments li:last').effect("highlight", {}, "slow");
                        }
                    }
                    // Remove any "More" pager links (because it is typically replaced with the latest comment by this function)
                    if (gdn.definition('PrependNewComments') != '1') // If prepending the latest comment, don't remove the pager.
                        $('#PagerMore').remove();

                    // Set the discussionid on the form in case the discussion was created by adding the last comment
                    var articleID = $(frm).find('[name$=ArticleID]');
                    if (articleID.length == 0 && json.ArticleID) {
                        $(frm).append('<input type="hidden" name="' + prefix + 'ArticleID" value="' + json.ArticleID + '">');
                    }

                    // Let listeners know that the comment was added.
                    $(document).trigger('CommentAdded');
                    $(frm).triggerHandler('complete');
                }
                gdn.inform(json);
                return false;
            },
            complete: function(XMLHttpRequest, textStatus) {
                // Remove any spinners, and re-enable buttons.
                $(':submit', frm).removeClass('InProgress');
                $(frm).find(':submit').removeAttr("disabled");
            }
        });

        frm.triggerHandler('submit');

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
                $(row).slideUp('fast', function() {
                    $(this).remove();
                });
                gdn.processTargets(json.Targets);
            }
        }
    });

    // Article media: image upload events.
    function CreateCustomElement(ElementType, SetOptions) {
        var Element = document.createElement(ElementType);

        for (var prop in SetOptions) {
            var propval = SetOptions[prop];
            Element.setAttribute(prop, propval);
        }

        return Element;
    }

    var currentArticleID = gdn.definition('ArticleID', null);
    $('#Form_UploadImage_New').ajaxfileupload({
        'action': gdn.url('/articles/compose/uploadimage?DeliveryMethod=JSON&DeliveryType=VIEW'),
        'params': {
            'ArticleID': currentArticleID
        },
        'onComplete': function(response) {
            $(this).replaceWith($(this).clone(true)); // Reset the file upload field.

            var imagePath = gdn.url('/uploads' + response.Path);

            // Show new image in form.
            $('#UploadedImages').append('<div id="ArticleMedia_' + response.ArticleMediaID + '" class="UploadedImageWrap">' +
                '<div class="UploadedImage"><img src="' + imagePath + '" alt="" /></div>' +
                '<div class="UploadedImageActions"><a class="UploadedImageInsert" href="' + imagePath + '">Insert into Post</a>' +
                '<br /><a class="UploadedImageDelete" href="' + gdn.url('/articles/compose/deleteimage/'
                + response.ArticleMediaID) + '">Delete</a></div>');

            // Add new image to hidden form field to be passed to the controller.
            var UploadedImageIDs = CreateCustomElement('input', {
                'type': 'hidden',
                'name': 'UploadedImageIDs[]',
                'value': response.ArticleMediaID
            });
            $('#Form_ComposeArticle').append(UploadedImageIDs);

            $('.TinyProgress').remove();
        },
        'onStart': function() {
            $(this).after('<span class="TinyProgress">&#160;</span>');
        },
        'onCancel': function() {
            //console.log('no file selected');
        }
    });

    $('.UploadedImageInsert').livequery('click', function(e) {
        e.preventDefault();

        var linkUrl = $(this).attr('href');
        var imageUrl = window.location.protocol + '//' + location.host + '/' + linkUrl;
        var bodyFormat = $('#Form_Body').attr('Format');

        var imageCode = '';
        switch (bodyFormat.toLowerCase()) {
            case 'markdown':
                imageCode = '![](' + imageUrl + ')';
                break;
            case 'bbcode':
                imageCode = '[img]' + imageUrl + '[/img]';
                break;
            default:
                imageCode = '<img src="' + imageUrl + '" alt="" />';
                break;
        }

        var FormBodyVal = $('#Form_Body').val();

        $('#Form_Body').val(FormBodyVal + imageCode);

        if ($('#Form_Body').data('wysihtml5'))
            $('#Form_Body').data('wysihtml5').editor.setValue(FormBodyVal + imageCode); // Wysihtml5 support.

        return false;
    });

    $('.UploadedImageDelete').livequery('click', function(e) {
        e.preventDefault();

        var linkUrl = $(this).attr('href');

        $.ajax({
            url: linkUrl,
            success: function(json) {
                var ArticleMediaID = linkUrl.substring(linkUrl.lastIndexOf('/') + 1);
                $('#ArticleMedia_' + ArticleMediaID).remove();
            }
        });

        return false;
    });
});
