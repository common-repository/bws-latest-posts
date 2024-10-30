<?php
/*
Plugin Name: Latest Posts by BestWebSoft
Plugin URI: https://bestwebsoft.com/products/wordpress/plugins/relevant/
Description: Add latest posts or latest posts for selected categories widgets to WordPress website.
Author: BestWebSoft
Text Domain: bws-latest-posts
Domain Path: /languages
Version: 0.4
Author URI: https://bestwebsoft.com/
License: GPLv3 or later
*/

/*  @ Copyright 2017  BestWebSoft  ( https://support.bestwebsoft.com )

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/* Add option page in admin menu */
if ( ! function_exists( 'ltstpsts_admin_menu' ) ) {
	function ltstpsts_admin_menu() {
		bws_general_menu();
		$settings = add_submenu_page( 'bws_panel', __( 'Latest Posts Settings', 'bws-latest-posts' ), 'Latest Posts', 'manage_options', 'latest-posts.php', 'ltstpsts_settings_page' );
		add_action( 'load-' . $settings, 'ltstpsts_add_tabs' );
	}
}

/**
 * Internationalization
 */
if ( ! function_exists( 'ltstpsts_plugins_loaded' ) ) {
	function ltstpsts_plugins_loaded() {
		load_plugin_textdomain( 'bws-latest-posts', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
	}
}

/* Plugin initialization - add internationalization and size for image*/
if ( ! function_exists ( 'ltstpsts_init' ) ) {
	function ltstpsts_init() {
		global $ltstpsts_plugin_info;

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );

		if ( empty( $ltstpsts_plugin_info ) ) {
			if ( ! function_exists( 'get_plugin_data' ) )
				require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
			$ltstpsts_plugin_info = get_plugin_data( __FILE__ );
		}

		/* Function check if plugin is compatible with current WP version  */
		bws_wp_min_version_check( plugin_basename( __FILE__ ), $ltstpsts_plugin_info, '3.9' );
	}
}

/* Plugin initialization for admin page */
if ( ! function_exists ( 'ltstpsts_admin_init' ) ) {
	function ltstpsts_admin_init() {
		global $bws_plugin_info, $ltstpsts_plugin_info, $pagenow;

		if ( empty( $bws_plugin_info ) )
			$bws_plugin_info = array( 'id' => '615', 'version' => $ltstpsts_plugin_info["Version"] );

		/* Call register settings function */
		$admin_pages = array( 'widgets.php', 'plugins.php' );
		if ( in_array( $pagenow, $admin_pages ) || ( isset( $_GET['page'] ) && "latest-posts.php" == $_GET['page'] ) )
			ltstpsts_set_options();
	}
}

/* Setting options */
if ( ! function_exists( 'ltstpsts_set_options' ) ) {
	function ltstpsts_set_options() {
		global $ltstpsts_options, $ltstpsts_plugin_info, $ltstpsts_options_defaults;

		$ltstpsts_options_defaults	=	array(
			'plugin_option_version'		=>	$ltstpsts_plugin_info["Version"],
			'display_settings_notice'	=>	1,
			'suggest_feature_banner'	=>	1,
			'display_not_supported_notice' => 1,
			'title'						=>	__( 'Latest Posts', 'bws-latest-posts' ),
			'count'						=>	3,
			'excerpt_length'			=>	10,
			'excerpt_more'				=>	'...',
			'no_preview_img'			=>	plugins_url( 'images/no_preview.jpg', __FILE__ ),
			'show_date'					=>	1,			
			'show_author'				=>	1,
			'show_reading_time'			=>	1,
			'show_comments'				=>	1,
			'show_image'				=>	1,
			'show_excerpt'				=>	1,
		);

		if ( ! get_option( 'ltstpsts_options' ) )
			add_option( 'ltstpsts_options', $ltstpsts_options_defaults );

		$ltstpsts_options = get_option( 'ltstpsts_options' );

		/* Array merge incase this version has added new options */
		if ( ! isset( $ltstpsts_options['plugin_option_version'] ) || $ltstpsts_options['plugin_option_version'] != $ltstpsts_plugin_info["Version"] ) {
			$ltstpsts_options = array_merge( $ltstpsts_options_defaults, $ltstpsts_options );
			$ltstpsts_options['plugin_option_version'] = $ltstpsts_plugin_info["Version"];
			$ltstpsts_options['display_not_supported_notice'] = 1;
			update_option( 'ltstpsts_options', $ltstpsts_options );
		}
	}
}

/* Function for display latest_posts settings page in the admin area */
if ( ! function_exists( 'ltstpsts_settings_page' ) ) {
	function ltstpsts_settings_page() {
		global $ltstpsts_options, $ltstpsts_plugin_info, $ltstpsts_options_defaults;
		$error = $message = "";

		/* Save data for settings page */
		if ( isset( $_REQUEST['ltstpsts_form_submit'] ) && check_admin_referer( plugin_basename(__FILE__), 'ltstpsts_nonce_name' ) ) {

			$ltstpsts_options['title']			= ( ! empty( $_POST['ltstpsts_title'] ) ) ? stripslashes( esc_html( $_POST['ltstpsts_title'] ) ) : null;
			$ltstpsts_options['count']			= ( ! empty( $_POST['ltstpsts_count'] ) ) ? intval( $_POST['ltstpsts_count'] ) : 3;
			$ltstpsts_options['excerpt_length'] = ( ! empty( $_POST['ltstpsts_excerpt_length'] ) ) ? intval( $_POST['ltstpsts_excerpt_length'] ) : 10;
			$ltstpsts_options['excerpt_more']   = ( ! empty( $_POST['ltstpsts_excerpt_more'] ) ) ? stripslashes( esc_html( $_POST['ltstpsts_excerpt_more'] ) ) : '...';
			$show_options = array( 'comments', 'date', 'author', 'reading_time', 'image', 'excerpt' );
			foreach ( $show_options as $item )
				$ltstpsts_options["show_{$item}"] = isset( $_POST["ltstpsts_show_{$item}"] ) ? 1 : 0;
			if ( ! empty( $_POST['ltstpsts_no_preview_img'] ) && ltstpsts_is_200( $_POST['ltstpsts_no_preview_img'] ) && getimagesize( $_POST['ltstpsts_no_preview_img'] ) )
				$ltstpsts_options['no_preview_img'] = $_POST['ltstpsts_no_preview_img'];
			else
				$ltstpsts_options['no_preview_img'] = plugins_url( 'images/no_preview.jpg', __FILE__ );
			
			if ( "" == $error ) {
				/* Update options in the database */
				update_option( 'ltstpsts_options', $ltstpsts_options );
				$message = __( "Settings saved.", 'bws-latest-posts' );
			}
		}

		/* Add restore function */
		if ( isset( $_REQUEST['bws_restore_confirm'] ) && check_admin_referer( plugin_basename(__FILE__), 'bws_settings_nonce_name' ) ) {
			$ltstpsts_options = $ltstpsts_options_defaults;
			update_option( 'ltstpsts_options', $ltstpsts_options );
			$message = __( 'All plugin settings were restored.', 'bws-latest-posts' );
		}

		/* Display form on the setting page */ ?>
		<div class="wrap">
			<h1><?php _e( 'Latest Posts Settings', 'bws-latest-posts' ); ?></h1>
			<h2 class="nav-tab-wrapper">
				<a class="nav-tab<?php if ( ! isset( $_GET['action'] ) ) echo ' nav-tab-active'; ?>" href="admin.php?page=latest-posts.php"><?php _e( 'Settings', 'bws-latest-posts' ); ?></a>
				<a class="nav-tab <?php if ( isset( $_GET['action'] ) && 'custom_code' == $_GET['action'] ) echo ' nav-tab-active'; ?>" href="admin.php?page=latest-posts.php&amp;action=custom_code"><?php _e( 'Custom code', 'bws-latest-posts' ); ?></a>
			</h2>
			<?php bws_show_settings_notice(); ?>
			<div class="updated fade below-h2" <?php if ( $message == "" || "" != $error ) echo "style=\"display:none\""; ?>><p><strong><?php echo $message; ?></strong></p></div>
			<div class="error below-h2" <?php if ( "" == $error ) echo "style=\"display:none\""; ?>><p><?php echo $error; ?></p></div>
			<?php if ( ! isset( $_GET['action'] ) ) {
				if ( isset( $_REQUEST['bws_restore_default'] ) && check_admin_referer( plugin_basename(__FILE__), 'bws_settings_nonce_name' ) ) {
					bws_form_restore_default_confirm( plugin_basename(__file__) );
				} else { ?>
					<form class="bws_form" method="post" action="">
						<p><?php _e( 'If you would like to display latest posts with a widget, you need to add the widget "Latest Posts Widget" in the Widgets tab.', 'bws-latest-posts' ); ?></p>
						<table class="form-table">
							<tr valign="top">
								<th scope="row"><?php _e( 'Default image (full URL) if no featured image is available', 'bws-latest-posts' ); ?></th>
								<td>
									<input name="ltstpsts_no_preview_img" type="text" maxlength="250" value="<?php echo $ltstpsts_options['no_preview_img']; ?>"/>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Excerpt length', 'bws-latest-posts' ); ?></th>
								<td>
									<input name="ltstpsts_excerpt_length" type="number" min="1" max="1000" value="<?php echo $ltstpsts_options['excerpt_length']; ?>"/>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( '"Read more" text', 'bws-latest-posts' ); ?></th>
								<td>
									<input name="ltstpsts_excerpt_more" type="text" maxlength="250" value="<?php echo $ltstpsts_options['excerpt_more']; ?>"/>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Title', 'bws-latest-posts' ); ?></th>
								<td>
									<input name="ltstpsts_title" type="text" maxlength="250" value="<?php echo $ltstpsts_options['title']; ?>"/>
								</td>
							</tr>
							<tr valign="top">
								<th scope="row"><?php _e( 'Number of posts', 'bws-latest-posts' ); ?></th>
								<td>
									<input name="ltstpsts_count" type="number" min="1" max="1000" value="<?php echo $ltstpsts_options['count']; ?>"/>
								</td>
							</tr>														
							<tr valign="top">
								<th scope="row"><?php _e( 'Show', 'bws-latest-posts' ); ?></th>
								<td>
									<fieldset>
										<?php $show_options = array(
											'date'   		=> __( 'post date', 'bws-latest-posts' ),
											'author' 		=> __( 'author', 'bws-latest-posts' ),
											'reading_time'	=> __( 'reading time', 'bws-latest-posts' ),
											'comments' 		=> __( 'comments number', 'bws-latest-posts' ),
											'image'  		=> __( 'featured image', 'bws-latest-posts' ),
											'excerpt'		=> __( 'excerpt', 'bws-latest-posts' ),						
										);
										foreach ( $show_options as $item => $label ) {
											$checked = 1 == $ltstpsts_options["show_{$item}"] ? ' checked="checked"' : '';
											$attr    = "ltstpsts_show_{$item}"; ?>
											<label for="<?php echo $attr; ?>">
												<input id="<?php echo $attr; ?>" name="<?php echo $attr; ?>" type="checkbox" value="1"<?php echo $checked; ?> /><?php echo $label; ?>
											</label><br />
										<?php } ?>
									</fieldset>
								</td>
							</tr>							
						</table>
						<p class="submit">
							<input id="bws-submit-button" type="submit" class="button-primary" value="<?php _e( 'Save Changes', 'bws-latest-posts' ); ?>" />
							<input type="hidden" name="ltstpsts_form_submit" value="submit" />
							<?php wp_nonce_field( plugin_basename(__FILE__), 'ltstpsts_nonce_name' ); ?>
						</p>
					</form>
					<?php bws_form_restore_default_settings( plugin_basename(__file__) );
				}
			} elseif ( 'custom_code' == $_GET['action'] ) {
				bws_custom_code_tab();
			} ?>
		</div>
	<?php }
}

/* Create widget for plugin */
if ( ! class_exists( 'Bws_Latest_Posts' ) ) {
	class Bws_Latest_Posts extends WP_Widget {

		function __construct() {
			/* Instantiate the parent object */
			parent::__construct(
				'ltstpsts_latest_posts_widget',
				__( 'Latest Posts Widget', 'bws-latest-posts' ),
				array( 'description' => __( 'Widget for displaying Latest Posts.', 'bws-latest-posts' ) )
			);
		}

		/* Outputs the content of the widget */
		function widget( $args, $instance ) {
			global $post, $ltstpsts_options;
			if ( empty( $ltstpsts_options ) )
				$ltstpsts_options = get_option( 'ltstpsts_options' );

			$widget_title 		= ( ! empty( $instance['widget_title'] ) ) ? apply_filters( 'widget_title', $instance['widget_title'], $instance, $this->id_base ) : '';
			$count            	= isset( $instance['count'] ) ? $instance['count'] : $ltstpsts_options['count'];
			$category			= isset( $instance['category'] ) ? $instance['category'] : 0;

			$show_comments		= isset( $instance['show_comments'] ) ? $instance['show_comments'] : 1;
			$show_date			= isset( $instance['show_date'] ) ? $instance['show_date'] : 1;
			$show_author		= isset( $instance['show_author'] ) ? $instance['show_author'] : 1;
			$show_reading_time 	= isset( $instance['show_reading_time'] ) ? $instance['show_reading_time'] : 1;
			$show_image			= isset( $instance['show_image'] ) ? $instance['show_image'] : 1;
			$show_excerpt		= isset( $instance['show_excerpt'] ) ? $instance['show_excerpt'] : 1;			

			echo $args['before_widget'];
			if ( ! empty( $widget_title ) ) {
				if ( ! empty( $category ) )
					echo '<a href="' . esc_url( get_category_link( $category ) ) . '">';
				echo $args['before_title'] . $widget_title . $args['after_title'];
				if ( ! empty( $category ) )
					echo '</a>';
			}
			$post_title_tag = $this->get_post_title_tag( $args['before_title'] ); ?>
			<div class="ltstpsts-latest-posts">
				<?php $query_args = array(
					'post_type'				=> 'post',
					'post_status'			=> 'publish',
					'orderby'				=> 'date',
					'order'					=> 'DESC',
					'posts_per_page'		=> $count,
					'ignore_sticky_posts' 	=> 1
				);
				if ( ! empty( $category ) )
					$query_args['cat'] = $category;

				$the_query = new WP_Query( $query_args );
				/* The Loop */
				if ( $the_query->have_posts() ) {
					add_filter( 'excerpt_length', 'ltstpsts_latest_posts_excerpt_length' );
					add_filter( 'excerpt_more', 'ltstpsts_latest_posts_excerpt_more' );
					while ( $the_query->have_posts() ) {
						$the_query->the_post(); ?>
						<div class="clear"></div>
						<article class="post type-post format-standard">
							<header class="entry-header">
								<a href="<?php the_permalink(); ?>">
									<?php echo "<{$post_title_tag} class=\"ltstpsts_posts_title\">"; the_title(); echo "</{$post_title_tag}>";
									if ( $show_date || $show_author || $show_comments || $show_reading_time ) { ?>
										<div class="entry-meta">
											<?php if ( 1 == $show_date ) { ?>
												<span class="ltstpsts_date entry-date">
													<?php echo human_time_diff( get_the_time( 'U' ), current_time( 'timestamp' ) ) . ' ' . __( 'ago', 'bws-latest-posts' ); ?>
												</span>
											<?php }
											if ( 1 == $show_author ) { ?>
												<span class="ltstpsts_author">
													<?php _e( 'by', 'bws-latest-posts' ) ?>
													<span class="author vcard">
														<?php echo get_the_author(); ?>
													</span>
												</span>
											<?php }
											if ( 1 == $show_reading_time ) { 
												$word = str_word_count( strip_tags( $post->post_content ) );
												$min = floor( $word / 200 );
												$sec = floor( $word % 200 / ( 200 / 60 ) ); ?>
												<span class="ltstpsts_reading_time">
													<?php if ( 0 == $min && 30 >= $sec ) {
														echo __( 'less than 1 min read', 'bws-latest-posts' );
													} elseif ( 60 < $min ) {
														echo __( 'more than 1 hour read', 'bws-latest-posts' );
													} else {
														if ( 0 != $sec )
															$min++;
														printf( __( '%s min read', 'bws-latest-posts' ), $min );
													} ?>												
												</span>
											<?php }
											if ( 1 == $show_comments ) { ?>
												<span class="ltstpsts_comments_count">
													<?php comments_number( __( 'No comments', 'bws-latest-posts' ), __( '1 Comment', 'bws-latest-posts' ), __( '% Comments', 'bws-latest-posts' ) ); ?>
												</span>
											<?php } ?>
										</div><!-- .entry-meta -->
									<?php } ?>
								</a>
							</header>
							<?php if ( $show_image || $show_excerpt ) { ?>
								<div class="entry-content">
									<?php if ( 1 == $show_image ) { ?>
										<a href="<?php the_permalink(); ?>" title="<?php the_title(); ?>">
											<?php if ( '' == get_the_post_thumbnail() ) { ?>
												<img class="attachment-thumbnail wp-post-image" src="<?php echo $ltstpsts_options['no_preview_img']; ?>" />
											<?php } else {
												echo get_the_post_thumbnail( $post->ID, array( 80, 80 ) );
											} ?>
										</a>
									<?php }
									if ( 1 == $show_excerpt )
										the_excerpt(); ?>
									<div class="clear"></div>
								</div><!-- .entry-content -->
							<?php } ?>
						</article><!-- .post -->
					<?php }
					remove_filter( 'excerpt_length', 'ltstpsts_latest_posts_excerpt_length' );
					remove_filter( 'excerpt_more', 'ltstpsts_latest_posts_excerpt_more' );
				}
				/* Restore original Post Data */
				wp_reset_postdata(); ?>
			</div><!-- .ltstpsts-latest-posts -->
			<?php echo $args['after_widget'];
		}

		/* Outputs the options form on admin */
		function form( $instance ) {
			global $ltstpsts_options;
			if ( empty( $ltstpsts_options ) )
				$ltstpsts_options = get_option( 'ltstpsts_options' );

			$widget_title		= isset( $instance['widget_title'] ) ? stripslashes( esc_html( $instance['widget_title'] ) ) : $ltstpsts_options['title'];
			$count				= isset( $instance['count'] ) ? intval( $instance['count'] ) : $ltstpsts_options['count'];
			$category			= isset( $instance['category'] ) ? $instance['category'] : 0;

			$show_comments		= isset( $instance['show_comments'] ) ? $instance['show_comments'] : $ltstpsts_options['show_comments'];
			$show_date			= isset( $instance['show_date'] ) ? $instance['show_date'] : $ltstpsts_options['show_date'];
			$show_author		= isset( $instance['show_author'] ) ? $instance['show_author'] : $ltstpsts_options['show_author'];
			$show_reading_time	= isset( $instance['show_reading_time'] ) ? $instance['show_reading_time'] : $ltstpsts_options['show_reading_time'];
			$show_image			= isset( $instance['show_image'] ) ? $instance['show_image'] : $ltstpsts_options['show_image'];
			$show_excerpt		= isset( $instance['show_excerpt'] ) ? $instance['show_excerpt'] : $ltstpsts_options['show_excerpt']; ?>
			<p>
				<label for="<?php echo $this->get_field_id( 'widget_title' ); ?>"><?php _e( 'Title', 'bws-latest-posts' ); ?>: </label>
				<input class="widefat" id="<?php echo $this->get_field_id( 'widget_title' ); ?>" name="<?php echo $this->get_field_name( 'widget_title' ); ?>" type="text" maxlength="250" value="<?php echo esc_attr( $widget_title ); ?>"/>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'count' ); ?>"><?php _e( 'Number of posts to show', 'bws-latest-posts' ); ?>: 
				<input class="tiny-text" id="<?php echo $this->get_field_id( 'count' ); ?>" name="<?php echo $this->get_field_name( 'count' ); ?>" type="number" min="1" max="1000" value="<?php echo esc_attr( $count ); ?>"/></label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'category' ); ?>"><?php _e( 'Category', 'bws-latest-posts' ); ?>: </label>
				<?php wp_dropdown_categories( array( 'show_option_all' => __( 'All categories', 'bws-latest-posts' ), 'name' => $this->get_field_name( 'category' ), 'id' => $this->get_field_id( 'category' ), 'selected' => $category ) ); ?>
			</p>
			<p>
				<?php _e( 'Show', 'bws-latest-posts' ); ?>:<br />
				<label for="<?php echo $this->get_field_id( 'show_date' ); ?>">
					<input id="<?php echo $this->get_field_id( 'show_date' ); ?>" name="<?php echo $this->get_field_name( 'show_date' ); ?>" type="checkbox" value="1"<?php if ( 1 == $show_date ) echo ' checked="checked"'; ?> />
					<?php _e( 'post date', 'bws-latest-posts' ); ?>
				</label><br />
				<label for="<?php echo $this->get_field_id( 'show_author' ); ?>">
					<input id="<?php echo $this->get_field_id( 'show_author' ); ?>" name="<?php echo $this->get_field_name( 'show_author' ); ?>" type="checkbox" value="1"<?php if ( 1 == $show_author ) echo ' checked="checked"'; ?> />
					<?php _e( 'author', 'bws-latest-posts' ); ?>
				</label><br />
				<label for="<?php echo $this->get_field_id( 'show_reading_time' ); ?>">
					<input id="<?php echo $this->get_field_id( 'show_reading_time' ); ?>" name="<?php echo $this->get_field_name( 'show_reading_time' ); ?>" type="checkbox" value="1"<?php if ( 1 == $show_reading_time ) echo ' checked="checked"'; ?> />
					<?php _e( 'reading time', 'bws-latest-posts' ); ?>
				</label><br />			
				<label for="<?php echo $this->get_field_id( 'show_comments' ); ?>">
					<input id="<?php echo $this->get_field_id( 'show_comments' ); ?>" name="<?php echo $this->get_field_name( 'show_comments' ); ?>" type="checkbox" value="1"<?php if ( 1 == $show_comments ) echo ' checked="checked"'; ?> />
					<?php _e( 'comments number', 'bws-latest-posts' ); ?>
				</label><br />
				<label for="<?php echo $this->get_field_id( 'show_image' ); ?>">
					<input id="<?php echo $this->get_field_id( 'show_image' ); ?>" name="<?php echo $this->get_field_name( 'show_image' ); ?>" type="checkbox" value="1"<?php if ( 1 == $show_image ) echo ' checked="checked"'; ?> />
					<?php _e( 'featured image', 'bws-latest-posts' ); ?>
				</label><br />
				<label for="<?php echo $this->get_field_id( 'show_excerpt' ); ?>">
					<input id="<?php echo $this->get_field_id( 'show_excerpt' ); ?>" name="<?php echo $this->get_field_name( 'show_excerpt' ); ?>" type="checkbox" value="1"<?php if ( 1 == $show_excerpt ) echo ' checked="checked"'; ?> />
					<?php _e( 'excerpt', 'bws-latest-posts' ); ?>
				</label>				
			</p>
		<?php }

		/* Processing widget options on save */
		function update( $new_instance, $old_instance ) {
			global $ltstpsts_options;
			if ( empty( $ltstpsts_options ) )
				$ltstpsts_options = get_option( 'ltstpsts_options' );
			$instance = array();
			$instance['widget_title']	= ( isset( $new_instance['widget_title'] ) ) ? stripslashes( esc_html( $new_instance['widget_title'] ) ) : $ltstpsts_options['title'];
			$instance['count']			= ( ! empty( $new_instance['count'] ) ) ? intval( $new_instance['count'] ) : $ltstpsts_options['count'];
			$instance['category']		= ( ! empty( $new_instance['category'] ) ) ? intval( $new_instance['category'] ) : 0;

			$show_options = array( 'comments', 'date', 'author', 'reading_time', 'image', 'excerpt' );
			foreach ( $show_options as $item )
				$instance["show_{$item}"] = isset( $new_instance["show_{$item}"] ) ? absint( $new_instance["show_{$item}"] ) : 0;

			return $instance;
		}

		function get_post_title_tag( $widget_tag ) {
			preg_match( '/h[1-5]{1}/', $widget_tag, $matches );

			if ( empty( $matches ) )
				return 'h1';

			$number = absint( preg_replace( '/h/', '', $matches[0] ) );
			$number ++;
			return "h{$number}";
		}
	}
}

/* Filter the number of words in an excerpt */
if ( ! function_exists ( 'ltstpsts_latest_posts_excerpt_length' ) ) {
	function ltstpsts_latest_posts_excerpt_length( $length ) {
		global $ltstpsts_options;
		return $ltstpsts_options['excerpt_length'];
	}
}

/* Filter the string in the "more" link displayed after a trimmed excerpt */
if ( ! function_exists ( 'ltstpsts_latest_posts_excerpt_more' ) ) {
	function ltstpsts_latest_posts_excerpt_more( $more ) {
		global $ltstpsts_options;
		return $ltstpsts_options['excerpt_more'];
	}
}

if ( ! function_exists( 'ltstpsts_admin_scripts' ) ) {
	function ltstpsts_admin_scripts() {
		if ( isset( $_REQUEST['page'] ) && 'latest-posts.php' == $_REQUEST['page'] ) {
			bws_enqueue_settings_scripts();
			bws_plugins_include_codemirror();
		}
	}
}

/* Proper way to enqueue scripts and styles */
if ( ! function_exists ( 'ltstpsts_wp_head' ) ) {
	function ltstpsts_wp_head() {
		wp_enqueue_style( 'ltstpsts_stylesheet', plugins_url( 'css/style.css', __FILE__ ) );
	}
}

/* Function to handle action links */
if ( ! function_exists( 'ltstpsts_plugin_action_links' ) ) {
	function ltstpsts_plugin_action_links( $links, $file ) {
		if ( ! is_network_admin() ) {
			/* Static so we don't call plugin_basename on every plugin row. */
			static $this_plugin;
			if ( ! $this_plugin )
				$this_plugin = plugin_basename(__FILE__);

			if ( $file == $this_plugin ) {
				$settings_link = '<a href="admin.php?page=latest-posts.php">' . __( 'Settings', 'bws-latest-posts' ) . '</a>';
				array_unshift( $links, $settings_link );
			}
		}
		return $links;
	}
}

/* Add costom links for plugin in the Plugins list table */
if ( ! function_exists ( 'ltstpsts_register_plugin_links' ) ) {
	function ltstpsts_register_plugin_links( $links, $file ) {
		$base = plugin_basename(__FILE__);
		if ( $file == $base ) {
			if ( ! is_network_admin() )
				$links[] = '<a href="admin.php?page=latest-posts.php">' . __( 'Settings', 'bws-latest-posts' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com" target="_blank">' . __( 'FAQ', 'bws-latest-posts' ) . '</a>';
			$links[] = '<a href="https://support.bestwebsoft.com">' . __( 'Support', 'bws-latest-posts' ) . '</a>';
		}
		return $links;
	}
}

/* Register a widget */
if ( ! function_exists ( 'ltstpsts_register_widgets' ) ) {
	function ltstpsts_register_widgets() {
		register_widget( 'Bws_Latest_Posts' );
	}
}

/* Check if image status = 200 */
if ( ! function_exists ( 'ltstpsts_is_200' ) ) {
	function ltstpsts_is_200( $url ) {
		if ( filter_var( $url, FILTER_VALIDATE_URL ) === FALSE )
			return false;

		$options['http'] = array(
				'method' => "HEAD",
				'ignore_errors' => 1,
				'max_redirects' => 0
		);
		$body = file_get_contents( $url, NULL, stream_context_create( $options ) );
		sscanf( $http_response_header[0], 'HTTP/%*d.%*d %d', $code );
		return $code === 200;
	}
}

/* add help tab  */
if ( ! function_exists( 'ltstpsts_add_tabs' ) ) {
	function ltstpsts_add_tabs() {
		$screen = get_current_screen();
		$args = array(
			'id' 			=> 'ltstpsts',
			'section' 		=> ''
		);
		bws_help_tab( $screen, $args );
	}
}

/* add admin notices */
if ( ! function_exists ( 'ltstpsts_admin_notices' ) ) {
	function ltstpsts_admin_notices() {
		global $hook_suffix, $ltstpsts_options;
		
		$admin_pages = array( 'widgets.php', 'plugins.php', 'update-core.php' );
		if ( in_array( $hook_suffix, $admin_pages ) || ( isset( $_GET['page'] ) && "latest-posts.php" == $_GET['page'] ) ) {
			if ( empty( $ltstpsts_options ) )
				ltstpsts_set_options();

			if ( ! $ltstpsts_options['display_not_supported_notice'] )
				return;

			if ( isset( $_POST['bws_hide_not_supported_notice'] ) && check_admin_referer( plugin_basename( __FILE__ ), 'bws_settings_nonce_name' ) ) {
				$ltstpsts_options['display_not_supported_notice'] = 0;
				update_option( 'ltstpsts_options', $ltstpsts_options );
				return;
			} ?>
			<div class="updated" style="padding: 0; margin: 0; border: none; background: none;">
				<div class="bws_banner_on_plugin_page bws_banner_to_settings">
					<div class="icon">
						<img title="" src="<?php echo plugins_url( 'images/latest-posts-icon.png', __FILE__ ); ?>" alt="" />
					</div>
					<div class="text">
						<strong><?php printf( __( '%s becomes %s plugin', 'bestwebsoft' ), 'Latest Posts', 'Relevant' ); ?></strong>
						<br />
						<?php printf( __( '%s plugin is now a part of %s plugin. It will be no longer supported (updates will be unavailable) starting from July 2017. Install %s plugin now to automatically apply your current settings and get new amazing features.', 'bestwebsoft' ), 'Latest Posts', 'Relevant – Related, Featured, Latest, and Popular Posts', 'Relevant' ); ?>
						<br />
						<a href="https://wordpress.org/plugins/relevant/" target="_blank"><?php _e( 'Install Now', 'bestwebsoft' ); ?></a>
					</div>
					<form action="" method="post">
						<button class="notice-dismiss bws_hide_settings_notice" title="<?php _e( 'Close notice', 'bestwebsoft' ); ?>"></button>
						<input type="hidden" name="bws_hide_not_supported_notice" value="hide" />
						<?php wp_nonce_field( plugin_basename( __FILE__ ), 'bws_settings_nonce_name' ); ?>
					</form>
				</div>
			</div>
		<?php }
	}
}

/**
 * Delete plugin options
 */
if ( ! function_exists( 'ltstpsts_plugin_uninstall' ) ) {
	function ltstpsts_plugin_uninstall() {
		global $wpdb;
		/* Delete options */
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$old_blog = $wpdb->blogid;
			/* Get all blog ids */
			$blogids = $wpdb->get_col( "SELECT `blog_id` FROM $wpdb->blogs" );
			foreach ( $blogids as $blog_id ) {
				switch_to_blog( $blog_id );
				delete_option( 'ltstpsts_options' );
			}
			switch_to_blog( $old_blog );
		} else {
			delete_option( 'ltstpsts_options' );
		}

		require_once( dirname( __FILE__ ) . '/bws_menu/bws_include.php' );
		bws_include_init( plugin_basename( __FILE__ ) );
		bws_delete_plugin( plugin_basename( __FILE__ ) );
	}
}

/* Add option page in admin menu */
add_action( 'admin_menu', 'ltstpsts_admin_menu' );

/* Plugin initialization */
add_action( 'init', 'ltstpsts_init' );
/* Register a widget */
add_action( 'widgets_init', 'ltstpsts_register_widgets' );
/* Plugin initialization for admin page */
add_action( 'admin_init', 'ltstpsts_admin_init' );
add_action( 'plugins_loaded', 'ltstpsts_plugins_loaded' );

/* Additional links on the plugin page */
add_filter( 'plugin_action_links', 'ltstpsts_plugin_action_links', 10, 2 );
add_filter( 'plugin_row_meta', 'ltstpsts_register_plugin_links', 10, 2 );
add_action( 'admin_enqueue_scripts', 'ltstpsts_admin_scripts' );
add_action( 'wp_enqueue_scripts', 'ltstpsts_wp_head' );
/* add admin notices */
add_action( 'admin_notices', 'ltstpsts_admin_notices' );

register_uninstall_hook( __FILE__, 'ltstpsts_plugin_uninstall' );