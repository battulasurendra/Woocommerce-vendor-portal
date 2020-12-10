<?php
if ( !defined('ABSPATH') ) {
	exit; // Exit if accessed directly
}
if ( !class_exists('WWP_WHOLESALE_FORM_BUILDER') ) {

	class WWP_WHOLESALE_FORM_BUILDER {

		public function __construct() {  
			$this->wwp_formbuilder();
		}
		public function wwp_formbuilder() {
			?>
			<div id="wwp_wholesale_form_builder_container">
				<div class="message"><span class="dashicons dashicons-yes-alt"></span><span class="message_text"> <?php echo esc_html__('Successfully Form Saved.', 'woocommerce-vendor-portal'); ?></span> <span class="dashicons dashicons-no-alt"></span></div>
				<div class="loader"></div>
				<div id="build-wrap"></div>
				<div class="render-wrap"></div>
			</div>
			<script>
			jQuery($ => {
			jQuery('#wwp_wholesale_form_builder_container .loader').show();	
			jQuery('#build-wrap').hide();  
			jQuery('.message').hide();  
			
				const fbTemplate = document.getElementById('build-wrap');
				jQuery(fbTemplate).formBuilder({
					disabledActionButtons: ['data'],
					replaceFields: [
						{
							type: "checkbox-group",
							label: "Checkbox",
							values: [{ label: "Option 1", value: "" }],
						}
					],
					controlOrder: [
						'text',
						'textarea',
						'select',
						'checkbox-group',
						'radio-group',
						'number',
						'date',
						'autocomplete',
						'header',
						'paragraph'
					],
					formData: '<?php echo wp_kses_post( get_option('wwp_save_form') ); ?>' ,
					scrollToFieldOnAdd: false,
					typeUserDisabledAttrs: {
						'radio-group': [ 'access', 'other' ],
						'checkbox-group': [ 'access', 'other', 'toggle' ],
						'date': [ 'access' ],
						'text': [ 'access' ],
						'header': [ 'access' ],
						'paragraph': [ 'access' ],
						'autocomplete': [ 'access' ],
						'file': [ 'access' ,'multiple' ],
						'textarea': [ 'access' ,'subtype' ],
						'number': [ 'access' ],
						'select': [ 'access' ]
					},
					disableFields: [ 'button','hidden','file' ],
					onSave: function (evt, formData) {
						jQuery.ajax({
							type: "POST",
							data: {
								action: 'wwp_save_form', 
								formData:  formData ,
							},
							url: "<?php echo esc_url(admin_url('admin-ajax.php')); ?>",
							success: function (response) {
								jQuery('.message').fadeIn("slow");
								timeout();
							}
						});
					}
				  });	
				function timeout(){
					setTimeout(function(){				  
						jQuery('.message').fadeOut("slow"); 
					}, 2000);
				}
				setTimeout(function(){				  
				  jQuery('#wwp_wholesale_form_builder_container .loader').hide();
				  jQuery('#build-wrap').show();
				}, 900);
				jQuery( "#wwp_wholesale_form_builder_container span.dashicons.dashicons-no-alt" ).click(function() {
					jQuery('.message').fadeOut("slow"); 
				});
			});
			</script>
			<?php 
		}
	}
	new WWP_WHOLESALE_FORM_BUILDER();
}
