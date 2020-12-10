jQuery(document).ready(
    function () {
        jQuery("#wholesale_user_roleschecklist-pop input, #wholesale_user_roleschecklist input, .wholesale_user_roles-checklist input").each(
            function () {
                this.type="radio"}
        );
       
        jQuery('#wholesale_user_roles-add-submit').on(
            'click',function () {
                setTimeout(
                    function () {
                        jQuery("#wholesale_user_roleschecklist-pop input, #wholesale_user_roleschecklist input, .wholesale_user_roles-checklist input").each(
                            function () {
                                this.type="radio"}
                        );
                    },1000
                );
            }
        );
        jQuery(document).on(
            'change', '#woocommerce-product-data #product-type', function () {

                var ptype = jQuery(this).val();
                var product_id = jQuery(document).find('input[name="product_id"]').val();
                var data = {
                    action : 'retrieve_wholesale_multiuser_pricing',
                    product_id: product_id,
                    ptype : ptype,
					security : wwpscript.ajax_nonce
                };
                jQuery(document).find('#wholesale-multiuser-pricing .wholesale_loader').show();
                jQuery.ajax(
                    {
                        type: 'POST',
                        url: wwpscript.ajaxurl,
                        dataType: 'html',
                        cache: false,
                        data: data,
                        success: function (response) {
                            jQuery(document).find('#wholesale-multiuser-pricing .wholesale_loader').hide();
                            jQuery(document).find('#wholesale-multiuser-pricing .wholesale_container').html(response);
                        }
                    }
                );
            }
        );
		jQuery('#woocommerce-product-data #product-type').trigger('change');
        jQuery(document).on(
            'click','#wholesale-pricing-pro-multiuser-move',function (e) {
                e.preventDefault();
                jQuery('html').delay(100).animate({scrollTop: $('#wholesale-pricing-pro-multiuser').offset().top }, 1000);
            }
        );
        jQuery(document).on(
            'click','#wholesale_pricing_bulk_update', function (e) {
                e.preventDefault();
                var me = jQuery(this);
                var product_id = me.data('id');
                var data = {
                    action: 'save_single_wholesale_product',
                    product_id: product_id,
					security : wwpscript.ajax_nonce,
                    data : me.closest('#pannel-'+product_id).find(':input').serialize()
                };
                me.closest('#pannel-'+product_id).find('.wwp-loader').show();
                jQuery.ajax(
                    {
                        type: 'POST',
                        url: wwpscript.ajaxurl,
                        dataType: 'html',
                        cache: false,
                        data: data,
                        success: function (response) {
							
                            me.closest('#pannel-'+product_id).find('.wwp-loader').hide();
                            console.log(response);
                        }
                    }
                );
        
            }
        );
        jQuery(".flip").click(
            function () {
                var me =jQuery(this);
                var pannel = me.attr('id');
                jQuery("#pannel-"+pannel).slideToggle("slow");
                me.toggleClass('flipped');
            }
        );

        jQuery('#wwp_all_products').click(
            function () {
                if(jQuery(this).is(':checked')) {
                    jQuery('.wwp_selected_item').attr('checked',true);
                } else {
                    jQuery('.wwp_selected_item').attr('checked',false);
                }
            }
        );

        jQuery('.wwp_selected_item').click(
            function (e) {
                e.stopImmediatePropagation(); // STOP ACCORDION
            }
        );

        jQuery('.wwp_prod_cat').change(
            function () {
                //jQuery('#wwp_bulk_form').submit();
                var cat =jQuery('.wwp_prod_cat').val();
                var location = wwpscript.admin_url + 'admin.php?page=wwp-bulk-wholesale-pricing&category='+cat;
                window.open(location,'_self');
            }
        );

        jQuery('.view_only_wholesale').click(
            function () {
                if(jQuery(".view_only_wholesale").is(':checked')) {
                    var location = window.location.href + '&view_wholesale_items=1'
                    window.open(location,'_self');
                } else {
                    var location = window.location.href + '&view_wholesale_items=0'
                    window.open(location,'_self');
                }
            }
        );

		function check_multirole_enable(){
			if(jQuery('#multiple_wholesaler_role').is(':checked')) 
			{ 
				jQuery('#multiroledropdown').show();
			}else{
				jQuery('#multiroledropdown').hide();
			}
		}
		
		jQuery("#multiple_wholesaler_role,#single_wholesaler_role").click(function(){
		check_multirole_enable();
		});		

		check_multirole_enable();
		
		jQuery('#rejected_note').hide();
		function rejected_note() {
			  console.log('test');
			if ( jQuery('#rejected').is(':checked') ) {
		 
				jQuery('#rejected_note').show();
			} else {
			
				jQuery('#rejected_note').hide();
			}
			
		}
		
		jQuery("#rejected,#active").click(function(){
		rejected_note();
		});		

		rejected_note();
		
		//jQuery('#multiroledropdown').show(); rejected_note

    }
);
function copytoclipboard()
{
    //var me =jQuery(this);
    var shortcode = jQuery('.map_shortcode_callback').find('p input');
    shortcode[0].select();
    document.execCommand("Copy");
    // console.log(shortcode);
}