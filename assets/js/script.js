jQuery(document).ready(
    function () {
        jQuery('.variations_form').on(
            'found_variation', function ( event, variation ) {
                console.log(variation['price_html']);
            }
        );
        jQuery('#wwp_wholesaler_copy_billing_address').change(
            function () {
                if (!this.checked) {
                    //  ^
                    jQuery('#wholesaler_shipping_address').fadeIn('slow');
                } else {
                    jQuery('#wholesaler_shipping_address').fadeOut('slow');
                }
            }
        );
    }
);