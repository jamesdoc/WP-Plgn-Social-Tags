<?php
	
	/*
	Plugin Name: Social Meta Tags
	Description: Adds in social meta tags for sharing on Facebook, Twitter, Pinterest, etc
	Author: James Doc
	Author URI: http://jamesdoc.com
	Version: 0.1
	*/
	
	$load = new smt_Loader();
	
	class smt_Loader {
		
		// Name of the main var we are going to store in the options table
		protected $option_store = 'smt_settings';
		
		// Declare some default settings
		protected $default_settings = array(
			'enabled' => array(
					'og_tags' 	=> TRUE,
					'tw_tags' 	=> TRUE,
					'gp_tags' 	=> FALSE,
					'schema'	=> TRUE
				),
			'fb_page_id' 	=> '',
			'twitter_user'	=> '',
			'gp_id'			=> ''
		);
		
		
		public function __construct(){
			
			// Create or destroy settings in database on activation/deactivation
			register_activation_hook( __FILE__,  array( $this, 'smt_activate'));
			register_deactivation_hook( __FILE__,  array( $this, 'smt_deactivate'));
			
			// When admin inits tell WP about the plugin settings
			add_action( 'admin_init', array( &$this, 'wpga_admin_init' ) );
			
			// Create options menu under Settings
			add_action( 'admin_menu', array( &$this, 'smt_create_options_page' ) );
			
			// Hook into the page <head>
			add_action('wp_head', array($this, 'smt_hook_head') );
			
		}
		
		
		public function wpga_admin_init() {
			register_setting( 'smt_settings', $this->option_store, array($this, 'smt_validate') );
		}
		
		
		public function smt_create_options_page() {
			add_options_page(
				'Social Media Tags',
				'Social Media Tags',
				'manage_categories',
				'social-media-tags-settings',
				array( $this, 'smt_options_form' )
			);
		}
		
		
		public function smt_options_form() {
		
			$options = get_option($this->option_store);
			
			?>
			
			<div class="wrap">
				
				<h3>Social Tags Setup</h3>
				
				<form method="post" action="options.php">
					
					<?php settings_fields('smt_settings'); ?>
					
					<table class="form-table">
						<tr valign="top">
							<th scope="row">Enable Services:</th>
							<td>
								<p>
									<label>
										<input type="checkbox" name="<?php echo $this->option_store; ?>[enabled][og_tags]" value="1" <?php if($options['enabled']['og_tags'] == True) { echo 'checked'; } ?> />
										Open Graph Tags
									</label>
								</p>
								
								<p>
									<label>
										<input type="checkbox" name="<?php echo $this->option_store; ?>[enabled][tw_tags]" value="1" <?php if($options['enabled']['tw_tags'] == True) { echo 'checked'; } ?> />
										Twitter Cards
									</label>
								</p>
								
								<p>
									<label>
										<input type="checkbox" name="<?php echo $this->option_store; ?>[enabled][gp_tags]" value="1" <?php if($options['enabled']['gp_tags'] == True) { echo 'checked'; } ?> />
										Google Plus
									</label>
								</p>
								
								<? /*<p>
									<label>
										<input type="checkbox" name="<?php echo $this->option_store; ?>[enabled][schema]" value="1" <?php if($options['enabled']['schema'] == True) { echo 'checked'; } ?> />
										Schema.org
									</label>
								</p> */ //To do: add me ?>
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row">Facebook Page ID</th>
							<td>
								<input type="text" name="<?php echo $this->option_store?>[fb_page_id]" value="<?php echo $options['fb_page_id']; ?>" />
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row">Twitter User Name</th>
							<td>
								<input type="text" name="<?php echo $this->option_store?>[twitter_user]" value="<?php echo $options['twitter_user']; ?>" />
							</td>
						</tr>
						
						<tr valign="top">
							<th scope="row">Google Plus ID</th>
							<td>
								<input type="text" name="<?php echo $this->option_store?>[gp_id]" value="<?php echo $options['gp_id']; ?>" />
							</td>
						</tr>
					</table>
					
					<p class="submit">
		                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
		            </p>
		            
				</form>
				
			</div>
			
			<?php
			
		}
		
		
		public function smt_validate($input) {
			
			$output = array();
			
			$output['enabled']['og_tags'] = sanitize_text_field($input['enabled']['og_tags']);
			$output['enabled']['tw_tags'] = sanitize_text_field($input['enabled']['tw_tags']);
			$output['enabled']['gp_tags'] = sanitize_text_field($input['enabled']['gp_tags']);
			$output['enabled']['schema']  = sanitize_text_field($input['enabled']['schema']);
			
			$output['fb_page_id']  	= sanitize_text_field($input['fb_page_id']);
			$output['twitter_user'] = sanitize_text_field($input['twitter_user']);
			$output['gp_id']  		= sanitize_text_field($input['gp_id']);
			
			
			// Check that fb_page_id is an int
			if (strlen($output['fb_page_id']) != 0 && !is_numeric($output['fb_page_id'])) {
				
				// Throw user error
				add_settings_error(
	                'fb_page_id',                			// Setting title
	                'fb_page_id_error',          			// Error ID
	                'Facebook Page ID should be numeric.<br />You can find your Page ID at <a href="http://findmyfacebookid.com/" target="_null">http://findmyfacebookid.com/</a>.',   // Error message
	                'error'                         		// Type of message
		        );
				
				// Reset to default
				$output['fb_page_id'] = $this->default_settings['fb_page_id'];
				
			}
			
			
			// Check to see if user has added a twitter name, and make sure it is prefixed with an '@'
			if (strlen($output['twitter_user']) != 0 && substr($output['twitter_user'], 0, 1) != '@') {
		        $output['twitter_user'] = '@' . $output['twitter_user'];
		    }
			
			return $output;
			
		}
		
		
		public function smt_hook_head(){
			
			global $post;
			
			// If we are not in a post get out of here...
			if ((!isset($post) && !isset($post->ID)) || !is_single() ) {
				return;
			}
			
			// Enable post functions such as the_permalink()
			setup_postdata($post);
			
			$options = get_option($this->option_store);
			
			// Get featured image
			if(has_post_thumbnail($post->ID)){
				$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID, array(360,150) ), 'single-post-thumbnail' );
			}
			
			echo "<!-- Start Social Meta Tags -->";
					
			if ($options['enabled']['og_tags']):
			?>
				<meta property="og:description" content="<?php echo wp_trim_words(get_the_excerpt(), 75, ''); ?>" />
				<?php if($image): ?>
					<meta property="og:image" content="<?php echo $image[0]; ?>" />
				<?php else: ?>
					<meta property="og:image" content="<?php echo header_image(); ?>" />
				<?php endif; ?>
				<meta property="og:sitename" content="<?php echo get_bloginfo('name'); ?>" />
				<meta property="og:title" content="<?php echo the_title(); ?>" />
				<meta property="og:type" content="article" />
				<meta property="og:url" content="<?php echo the_permalink(); ?>" />
				<meta property="article:published_time" content="<?php echo get_the_date('c'); ?>" />
				<meta property="article:author" content="<?php echo get_the_author(); ?>" />
				<meta property="article:section" content="<?php $cat = get_the_category(); echo $cat[0]->cat_name; ?>" />
			<?php
			
			// End og_tags check
			endif;
			
			if ($options['fb_page_id']) :
			?>
				<meta property="fb:admins" content="<?php echo $options['fb_page_id'] ?>" />
			
			<?php
			// End facebook page id check
			endif;
			
			if ($options['enabled']['tw_tags']):
			?>
				<?php if($image): ?>
				<meta property="twitter:card" content="summary_large_image" />
				<meta property="twitter:image:src" content="<?php echo $image[0] ?>" />
				<? else: ?>
				<meta property="twitter:card" content="summary" />
				<?php endif; ?>
				
				<?php if ($options['twitter_user']): ?>
				<meta property="twitter:site" content="<?php echo $options['twitter_user'] ?>" />
				<?php endif; ?>
				
				<meta property="twitter:title" content="<?php echo the_title() ?>" />
				<meta property="twitter:description" content="<?php echo wp_trim_words(get_the_excerpt(), 75, ''); ?>" />
				<meta property="twitter:url" content="<?php echo the_permalink() ?>" />
			<?php
			
			// End og_tags check
			endif;
			
			if($options['enabled']['gp_tags'] && $options['gp_id']):
			?>
			
			<meta rel="author" content="http://plus.google.com/<?php echo $options['gp_id']; ?>/posts" />
			<meta rel="published" content="http://plus.google.com/<?php echo $options['gp_id']; ?>" />
			
			<?php
			
			// end gp check
			endif;

			
			echo "<!-- End Social Meta Tags -->";
			
		}
		
		
		public function smt_activate() {
			update_option($this->option_store, $this->default_settings);
		}
		
		
		public function wpga_deactivate() {
		    delete_option($this->option_store);
		}
		
	}