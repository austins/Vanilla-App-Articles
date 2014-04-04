jQuery(document).ready(function($) {
    // Map plain text category to url code
    $("#Form_Name").keyup(function(event) {
        if ($('#Form_UrlCodeIsDefined').val() == '0') {
            $('#UrlCode').show();
            var val = $(this).val().replace(/[ \/\\&.?;,<>'"]+/g, '-')
            val = val.replace(/\-+/g, '-').toLowerCase();
            $("#Form_UrlCode").val(val);
            $("#UrlCode span").text(val);
        }
    });
    // Make sure not to override any values set by the user.
    $('#UrlCode span').text($('#UrlCode input').val());
    $("#Form_UrlCode").focus(function() {
        $('#Form_UrlCodeIsDefined').val('1')
    });
    $('#UrlCode input, #UrlCode a.Save').hide();

    // Reveal input when "change" button is clicked
    $('#UrlCode a, #UrlCode span').click(function() {
        $('#UrlCode').find('input,span,a').toggle();
        $('#UrlCode span').text($('#UrlCode input').val());
        $('#UrlCode input').focus();
        return false;
    });
});
