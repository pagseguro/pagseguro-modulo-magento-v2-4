require(['jquery', 'Magento_Ui/js/modal/alert', 'mage/translate'], function ($, alert, $t) {

    const urlParams = new URLSearchParams(window.location.search)

    $(document).ready(function() {
        $('#row_payment_us_pagseguro_payment_options_pagseguropayment_general_oauth_code').css('display', 'none');
        const code = urlParams.get('code')
        const codeVerifier = urlParams.get('code_verifier')
        if (code && codeVerifier) {
            $('#payment_us_pagseguro_payment_options_pagseguropayment_general_oauth_code').val(code + '|' + codeVerifier);
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

        window.open(redirectUrl, '_blank').focus();

    }

    window.paseguroOauthRemove = function () {
        $('#payment_us_pagseguro_payment_options_pagseguropayment_general_oauth_code').val('revoke');
        $('#pagseguro-oauth-button-span').text('Salve as configurações...');
    }

});
