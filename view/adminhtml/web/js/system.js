require(['jquery', 'Magento_Ui/js/modal/alert', 'mage/translate'], function ($, alert, $t) {

    const urlParams = new URLSearchParams(window.location.search)

    let paymentCountry = document.getElementById('payment_other_account_merchant_country')

    if (paymentCountry === undefined || paymentCountry === null || paymentCountry.value === '') {
        paymentCountry = 'us'
    } else {
        paymentCountry = 'other'
    }

    $(document).ready(function() {
        $('#row_payment_' + paymentCountry + '_pagseguro_payment_options_pagseguropayment_general_oauth_code').css('display', 'none');
        const code = urlParams.get('code')
        const codeVerifier = urlParams.get('code_verifier')
        if (code && codeVerifier) {
            $('#payment_' + paymentCountry + '_pagseguro_payment_options_pagseguropayment_general_oauth_code').val(code + '|' + codeVerifier);
            $('#pagseguro-oauth-button-span').text('Salve as configurações...');
            alert({
                title: $t('PagSeguro - Salve suas Configurações'),
                content: $t('Você precisa salvar as configurações para completar o processo de autenticação.')
            });
        }
    })

    window.paseguroOauthRedirect = function (url) {

        let redirectUrl = url;

        const codeVerifier = $('#pagseguro-oauth-button').attr('data-code-verifier')

        redirectUrl = redirectUrl + '&redirect_uri=' + window.location.href + '?code_verifier=' + codeVerifier

        console.log(redirectUrl)

        window.open(redirectUrl, '_blank').focus();

    }

    window.paseguroOauthRemove = function () {
        $('#payment_' + paymentCountry + '_pagseguro_payment_options_pagseguropayment_general_oauth_code').val('revoke');
        $('#pagseguro-oauth-button-span').text('Salve as configurações...');
    }

    window.pagseguroPaymentValidator = function (endpoint) {
        let sandbox = $('#payment_other_pagseguropayment_pagseguropayment_general_sandbox').val();
        let token = $('#payment_other_pagseguropayment_pagseguropayment_general_token').val();

        /* Remove previous success message if present */
        if ($(".pagseguro-payment-credentials-success-message")) {
            $(".pagseguro-payment-success-message").remove();
        }

        /* Basic field validation */
        var errors = [];

        if (!token) {
            errors.push($t('Please enter a token'));
        }

        if (errors.length > 0) {
            alert({
                title: $t('PagSeguro - Validation Failed'),
                content:  errors.join('<br />')
            });
            return false;
        }

        $(this).text($t("We're validating your credentials...")).attr('disabled', true);

        var self = this;
        $.post(endpoint,{
            token: token,
            sandbox: sandbox
        }).done(function () {
            $('<div class="message message-success pagseguro-payment-success-message">' + $t("Your credentials are valid.") + '</div>').insertAfter(self);
        }).fail(function (response) {
            if (response.status == 200) {
                $('<div class="message message-success pagseguro-payment-success-message">' + $t("Your credentials are valid.") + '</div>').insertAfter(self);
            } else {
                alert({
                    title: $t('PagSeguro - Validation Failed'),
                    content: $t('Your PagSeguro token could not be validated. Please ensure you have selected the correct environment and entered a valid token.')
                });
            }
        }).always(function () {
            $(self).text($t("Validate Credentials")).attr('disabled', false);
        });
    }

});
