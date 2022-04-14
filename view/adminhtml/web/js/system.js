require(['jquery', 'Magento_Ui/js/modal/alert', 'mage/translate'], function ($, alert, $t) {

    const urlParams = new URLSearchParams(window.location.search)

    const code = urlParams.get('code')

    if (code) {

        let sandbox = $('#payment_other_pagseguropayment_pagseguropayment_general_sandbox').val();

        let endpoint = $('#pagseguro-oauth-button').data('exchange-url');

        console.log(endpoint)

        /* Remove previous success message if present */
        if ($(".pagseguro-payment-credentials-success-message")) {

            $(".pagseguro-payment-success-message").remove();

        }

        $.post(endpoint,{
            code: code,
            sandbox: sandbox
        }).done(function (response) {
        }).fail(function () {
            alert({
                title: $t('PagSeguro - Validation Failed'),
                content: $t('Your PagSeguro token could not be validated. Please ensure you have selected the correct environment and entered a valid token.')
            });
        }).always(function () {
        });

    }

    window.paseguroOauthRedirect = function (url) {

        let redirectUrl = url;

        redirectUrl = redirectUrl + '&redirect_uri=' + window.location.href

        console.log(redirectUrl)

        window.open(redirectUrl, '_blank').focus();

    }

    window.paseguroOauthRemove = function (endpoint) {

        console.log(endpoint)

        let sandbox = $('#payment_other_pagseguropayment_pagseguropayment_general_sandbox').val();

        /* Remove previous success message if present */
        if ($(".pagseguro-payment-credentials-success-message")) {

            $(".pagseguro-payment-success-message").remove();

        }

        /* Basic field validation */
        var errors = [];

        if (errors.length > 0) {
            alert({
                title: $t('PagSeguro - Remove Failed'),
                content:  errors.join('<br />')
            });
            return false;
        }

        $(this).text($t("Removing your credentials...")).attr('disabled', true);

        var self = this;

        $.post(endpoint,{
            sandbox: sandbox
        }).done(function (response) {
            console.log(response)
            $('<div class="message message-success pagseguro-payment-success-message">' + $t("Your credentials were removed.") + '</div>').insertAfter(self);
        }).fail(function () {
            alert({
                title: $t('PagSeguro - Removing Failed'),
                content: $t('Your PagSeguro token could not be validated. Please ensure you have selected the correct environment and entered a valid token.')
            });
        }).always(function (response) {
            console.log(response)
            $(self).text($t("Reload this page")).attr('disabled', false);
        });

    }

});
