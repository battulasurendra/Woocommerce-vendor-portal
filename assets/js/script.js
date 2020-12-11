jQuery(document).ready(
    function () {
        jQuery('.variations_form').on(
            'found_variation', function ( event, variation ) {
                console.log(variation['price_html']);
            }
        );
        jQuery('#wvp_contractor_copy_billing_address').change(
            function () {
                if (!this.checked) {
                    //  ^
                    jQuery('#contractor_shipping_address').fadeIn('slow');
                } else {
                    jQuery('#contractor_shipping_address').fadeOut('slow');
                }
            }
        );
    }
);