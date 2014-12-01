jQuery(document).ready(function($) {
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

    // Upload an image.
    if ($('#Form_UploadImage_New').length) {
        $('#Form_UploadImage_New').ajaxfileupload({
            'action': gdn.url('/articles/compose/uploadimage?DeliveryMethod=JSON&DeliveryType=VIEW'),
            'params': {
                'ArticleID': currentArticleID
            },
            'onComplete': function(response) {
                // Reset the file upload field.
                $(this).wrap('<form>').closest('form').get(0).reset();
                $(this).unwrap();

                var imagePath = gdn.url('/uploads' + response.Path);

                // Show new image in form.
                $('#UploadedImages').append('<div id="ArticleMedia_' + response.ArticleMediaID + '" class="UploadedImageWrap">' +
                '<div class="UploadedImage"><img src="' + imagePath + '" alt="" /></div>' +
                '<div class="UploadedImageActions"><a class="UploadedImageInsert" href="' + imagePath + '">Insert into Post</a>' +
                '<br /><a class="UploadedImageDelete" href="' + gdn.url('/articles/compose/deleteimage/'
                + response.ArticleMediaID) + '?DeliveryMethod=JSON&DeliveryType=BOOL">Delete</a></div>');

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
    }

    // Upload a thumbnail.
    if ($('#Form_UploadThumbnail_New').length) {
        $('#Form_UploadThumbnail_New').ajaxfileupload({
            'action': gdn.url('/articles/compose/uploadimage?DeliveryMethod=JSON&DeliveryType=VIEW'),
            'params': {
                'ArticleID': currentArticleID,
                'IsThumbnail': true
            },
            'onComplete': function(response) {
                // Reset the file upload field.
                $(this).wrap('<form>').closest('form').get(0).reset();
                $(this).unwrap();

                $(this).hide();

                var imagePath = gdn.url('/uploads' + response.Path);

                // Show new image in form.
                $('#UploadedThumbnail').append('<div id="ArticleMedia_' + response.ArticleMediaID + '" class="UploadedImageWrap">' +
                '<div class="UploadedImage"><img src="' + imagePath + '" alt="" /></div>' +
                '<div class="UploadedImageActions"><a class="UploadedImageDelete" href="' + gdn.url('/articles/compose/deleteimage/'
                + response.ArticleMediaID) + '?DeliveryMethod=JSON&DeliveryType=BOOL">Delete</a></div>');

                // Add new image to hidden form field to be passed to the controller.
                var UploadedThumbnailID = CreateCustomElement('input', {
                    'type': 'hidden',
                    'name': 'UploadedThumbnailID',
                    'value': response.ArticleMediaID
                });
                $('#Form_ComposeArticle').append(UploadedThumbnailID);

                $('.TinyProgress').remove();
            },
            'onStart': function() {
                $(this).after('<span class="TinyProgress">&#160;</span>');
            },
            'onCancel': function() {
                //console.log('no file selected');
            }
        });
    }

    $('.UploadedImageInsert').livequery('click', function(e) {
        e.preventDefault();

        var linkUrl = $(this).attr('href');
        var imageUrl = window.location.protocol + '//' + location.host + linkUrl;
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

    $('a.UploadedImageDelete').popup({
        confirm: true,
        confirmHeading: gdn.definition('ConfirmDeleteImageHeading', 'Delete Image'),
        confirmText: gdn.definition('ConfirmDeleteImageText', 'Are you sure you want to delete this image?'),
        followConfirm: false,
        deliveryType: 'BOOL',
        afterConfirm: function(json, sender) {
            var linkUrl = jQuery(sender).attr('href').split('?')[0]; // Retrieve part of URL without query string.
            var ArticleMediaID = linkUrl.substring(linkUrl.lastIndexOf('/') + 1);
            $('#ArticleMedia_' + ArticleMediaID).remove();

            if ($(this).parent().attr('id') == '#UploadedThumbnail') {
                $('#UploadedThumbnailID').remove();
                $('#Form_UploadThumbnail_New').show();
            }
        }
    });

    // Hijack article compose form button clicks
    $('#Form_ComposeArticle :submit').live('click', function() {
        var btn = this;
        var frm = $(btn).parents('form').get(0);

        // Handler before submitting
        $(frm).triggerHandler('BeforeArticleSubmit', [frm, btn]);

        var inpArticleID = $(frm).find(':hidden[name$=ArticleID]');
        var preview = $(btn).attr('name') == $('#Form_Preview').attr('name') ? true : false;
        var postValues = $(frm).serialize();
        postValues += '&DeliveryType=VIEW&DeliveryMethod=JSON'; // DELIVERY_TYPE_VIEW
        postValues += '&' + btn.name + '=' + btn.value;
        gdn.disable(btn);

        $.ajax({
            type: "POST",
            url: $(frm).attr('action'),
            data: postValues,
            dataType: 'json',
            error: function(XMLHttpRequest, textStatus, errorThrown) {
                $('div.Popup').remove();
                $.popup({}, XMLHttpRequest.responseText);
            },
            success: function(json) {
                json = $.postParseJson(json);

                // Remove any old popups
                $('div.Popup').remove();

                // Assign the article id to the form if it was defined
                if (json.ArticleID != null)
                    $(inpArticleID).val(json.ArticleID);

                // Remove any old errors from the form
                $(frm).find('div.Errors').remove();

                if (json.FormSaved == false) {
                    $(frm).prepend(json.ErrorMessages);
                    json.ErrorMessages = null;
                } else if (preview) {
                    // Pop up the new preview.
                    $.popup({}, json.Data);
                } else if (!draft) {
                    if (json.RedirectUrl) {
                        $(frm).triggerHandler('complete');
                        // Redirect to the new article
                        document.location = json.RedirectUrl;
                    } else {
                        $('#Content').html(json.Data);
                    }
                }
                gdn.inform(json);
            },
            complete: function(XMLHttpRequest, textStatus) {
                gdn.enable(btn);
            }
        });
        $(frm).triggerHandler('submit');
        return false;
    });
});
