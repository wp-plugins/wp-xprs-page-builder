<?php
/*
  Plugin Name: WP-XPRS
  Description: Visual editor WP-XPRS
  Version: 1.0
  Author: imcreator
  Author URI: wpxprs.imcreator.com
 */
//error_reporting(E_ALL);
$wp_xprs = new WP_XPRS();

add_action( 'add_meta_boxes', array( $wp_xprs, 'myplugin_add_meta_box' ) );
add_action( 'save_post', array( $wp_xprs, 'myplugin_save_meta_box_data' ) );

add_action( 'wp', array( $wp_xprs, 'wp_action' ) );
add_action( 'init', array( $wp_xprs, 'init_action' ) );

add_filter( 'post_row_actions', array( $wp_xprs, 'add_row_action' ), 10, 2 );
add_filter( 'page_row_actions', array( $wp_xprs, 'add_row_action' ), 10, 2 );

class WP_XPRS {

	var $text_domain		 = 'wp_xprs';
	var $embed_url		 = 'https://www.imxprs.com/embed';
	var $external_page	 = ''; // for replace entire page
	var $active_mode		 = 0;
	var $active_vbid		 = '';
	var $hide_sidebar	 = 0;
	var $hide_comments	 = 0;
	var $header			 = '';
	var $wp_page         = '';
	var $supported_themes = array();

	public function __construct() {
		load_plugin_textdomain( $this->text_domain, false, dirname( plugin_basename( __FILE__ ) ) . '/langs/' );
		$this->supported_themes = array_filter(array_map("trim",file(dirname(__FILE__)."/supported_themes.txt")));
	}

    public function add_row_action( $actions, $user_object ) {
        if ( $user_object->post_type == 'page' || $user_object->post_type == 'post' ) {
            $vbid = get_post_meta( $user_object->ID, 'wp_xprs_vbid', true );
            $mode = get_post_meta( $user_object->ID, 'wp_xprs_mode', true );
            if ( ! empty( $vbid ) ) {
                $url = 'https://www.imxprs.com/wpxprs?vbid=' . $vbid . '&mode=' . $mode;
                $actions['xprs_visual_editor'] = "<a href=\"$url\">" . __( 'Visual Editor' ) . "</a>";
            }
        }
        return $actions;
    }

	public function change_rules( $rules ) {
		$home_root	 = parse_url( home_url() );
		if ( isset( $home_root[ 'path' ] ) )
			$home_root	 = trailingslashit( $home_root[ 'path' ] );
		else
			$home_root	 = '/';

		$lines = array(
			"# WPxprs: Begin Custom htaccess",
			"RewriteEngine on",
			"RewriteBase $home_root",
			"RewriteCond %{HTTP_HOST}%{REQUEST_URI}   ^(.*)",
			"RewriteCond %{REQUEST_URI} !^/wp-admin [NC]",
			"RewriteRule  (.*) https://www.imxprs.com/$1  [P]",
			"# WPxprs: End Custom htaccess",
			"" );
		return join( "\n", $lines );
	}

	/**
	 * Adds a box to the main column on the Post and Page edit screens.
	 */
	function myplugin_add_meta_box() {
		$screens = array( 'post', 'page' );
		foreach ( $screens as $screen ) {
			add_meta_box(
			'XPRS_WP_Visual_Editor_sectionid', __( 'XPRS WP Visual Editor', $this->text_domain ), array( $this, 'myplugin_meta_box_callback' ), $screen
			);
		}
	}

	/**
	 * Prints the box content.
	 *
	 * @param WP_Post $post The object for the current post/page.
	 */
	function myplugin_meta_box_callback( $post ) {
		// Add a nonce field so we can check for it later.
		wp_nonce_field( 'XPRS_WP_Visual_Editor_meta_box', 'wp_xprs_meta_box_nonce' );
		/*
		 * Use get_post_meta() to retrieve an existing value
		 * from the database and use the value for the form.
		 */
		$mode		 = get_post_meta( $post->ID, 'wp_xprs_mode', true );
		$vbid		 = get_post_meta( $post->ID, 'wp_xprs_vbid', true );
		$sidebar	 = get_post_meta( $post->ID, 'wp_xprs_sidebar', true );
		$comments	 = get_post_meta( $post->ID, 'wp_xprs_comments', true );
		$front		 = $post->ID == get_option( 'page_on_front' ) ? true : false;
		include 'views/settings_post.php';
	}

	/**
	 * When the post is saved, saves our custom data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	function myplugin_save_meta_box_data( $post_id ) {
		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */
		// Check if our nonce is set.
		if ( !isset( $_POST[ 'wp_xprs_meta_box_nonce' ] ) ) {
			return;
		}
		// Verify that the nonce is valid.
		if ( !wp_verify_nonce( $_POST[ 'wp_xprs_meta_box_nonce' ], 'XPRS_WP_Visual_Editor_meta_box' ) ) {
			return;
		}
		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		// Check the user's permissions.
		if ( !current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		// Make sure that it is set.
		if ( (!isset( $_POST[ 'wp_xprs_insert_mode' ] )) && (!isset( $_POST[ 'wp_xprs_vbid' ] )) ) {
			return;
		}

		$mode	 = sanitize_text_field( $_POST[ 'wp_xprs_insert_mode' ] );
		$vbid	 = sanitize_text_field( $_POST[ 'wp_xprs_vbid' ] );
		update_post_meta( $post_id, 'wp_xprs_mode', $mode );
		update_post_meta( $post_id, 'wp_xprs_vbid', $vbid );
		update_post_meta( $post_id, 'wp_xprs_sidebar', isset( $_POST[ 'wp_xprs_hide_sidebar' ] ) ? 1 : 0  );
		update_post_meta( $post_id, 'wp_xprs_comments', isset( $_POST[ 'wp_xprs_hide_comments' ] ) ? 1 : 0  );
		// update front-page ?
		if ( $post_id == get_option( 'page_on_front' ) ) {
			if ( $mode == 5 ) {
				add_filter( 'mod_rewrite_rules', array( $this, 'change_rules' ) );
				flush_rewrite_rules( true );
				update_option( "wp_xprs_rewrite", $post_id );
			} else
				delete_option( "wp_xprs_rewrite" );
		}
	}

	function get_url( $url ) {
		$args = array(
			'timeout'		 => 10,
			'redirection'	 => 10,
			'httpversion'	 => '1.0',
			'user-agent'	 => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36',
			'blocking'		 => true,
			'body'			 => null,
			'compress'		 => false,
			'decompress'	 => true,
			'sslverify'		 => true,
			'stream'		 => false,
			'filename'		 => null
		);
		return wp_remote_retrieve_body( wp_remote_get( $url, $args ) );
	}

	public function init_action() {
		// must setup correct .htaccess on update
		$post = get_option( "wp_xprs_rewrite" );
		if ( $post && ($post == get_option( 'page_on_front' )) ) {
			add_filter( 'mod_rewrite_rules', array( $this, 'change_rules' ) );
		}
	}

	public function wp_action() {
		global $wp_query;
		if ( is_admin() )
			return;

//         wp_reset_query();
		if ( !is_singular( array( 'post', 'page' ) ) )
			return;

		$post = $wp_query->post;
		if ( !$post->ID )
			return;

		$this->active_mode	 = get_post_meta( $post->ID, 'wp_xprs_mode', true );
		$this->active_vbid	 = get_post_meta( $post->ID, 'wp_xprs_vbid', true );
		$this->hide_sidebar	 = get_post_meta( $post->ID, 'wp_xprs_sidebar', true );
		$this->hide_comments = get_post_meta( $post->ID, 'wp_xprs_comments', true );
//
//		var_dump(wp_get_sidebars_widgets());
//		die();
		if ( empty( $this->active_vbid ) ) {
			return;
		}

		$this->external_page = $this->get_url( $this->embed_url . '?vbid=' . $this->active_vbid . '&mode=' . $this->active_mode );

		//replace whole page
		if ( $this->active_mode == 2 ) {
			echo $this->external_page;
			die();
		}

		// for replace Content only
		if ( $this->active_mode == 1 AND $this->hide_comments ) {
			add_filter( 'comments_template', array( $this, 'no_comments_on_page' ) );
		}


		//-=-=-=-=-=-=-=------
//		if ( $this->active_mode == 1 AND $this->hide_sidebar ) {
//			echo $this->exclude_sidebar();
//			die();
//		}
		//-=-=-=-=-=-=-=------
		if ( $this->active_mode == 1 && !in_array( wp_get_theme()->get_template(), $this->supported_themes ) ) {
			include('views/page.php');
			die();
		}
		ob_start( array( $this, "ob_callback" ) );
	}

	public function exclude_sidebar() {
		ob_start();
		get_header();
		?>

		<div id="main-content" class="main-content">
			<div id="primary" class="content-area">
				<div id="content" class="site-content" role="main">
					<article id="post-<?php echo $post->ID; ?>" <?php post_class(); ?>>
						<div class="entry-content">
							<?php echo $this->external_page; ?>
						</div><!-- .entry-content -->
					</article><!-- #post -->
					<?php if ( !$this->hide_comments ) comments_template( '', true ); ?>
				</div><!-- #content -->
			</div><!-- #primary -->
		</div><!-- #main-content -->
		<?php
		get_footer();
		$new_content = ob_get_clean();
		$theme		 = wp_get_theme();
		$method_name = 'adapt_theme_' . $theme->get_template();
		$method_name = str_replace( '-', '_', $method_name );
		if ( method_exists( $this, $method_name ) ) {
			$new_content = $this->{$method_name}( $new_content );
		}
		return $new_content;
	}

	public function no_comments_on_page( $file ) {
		return dirname( __FILE__ ) . '/no-comments.php';
	}

	function ob_callback( $buffer ) {
		if ( (empty( $this->active_mode )) || (empty( $this->active_vbid )) )
			return $buffer;
//		return $buffer;
		$new_content = $buffer;
		switch ( $this->active_mode ) {
			case 1: // replace post only
				$theme		 = wp_get_theme();
				$theme_name	 = $theme->get_template();
				switch ( $theme_name ) {
					case 'customizr':
						$new_content = preg_replace( '#<article\s+id="(page|post)-\d+[\s\S]*?</article>#', $this->external_page, $buffer );
						break;
					case 'virtue':
						$new_content = preg_replace( '#<div class="entry-content"[\s\S]*?</div>#', $this->external_page, $buffer );
						break;
					case 'onetone':
						$new_content = preg_replace( '#<article class="post-entry"[\s\S]*?</article>#', $this->external_page, $buffer );
						break;
					case 'enigma':
						$new_content = preg_replace( '#<div class="enigma_blog_post_content[\s\S]*?</div>#', $this->external_page, $buffer );
						break;
					case 'mh-magazine-lite':
						$new_content = preg_replace( '#<div id="main-content"[\s\S]*?</div>#', $this->external_page, $buffer );
						break;
					case 'evolve':
						$new_content = preg_replace( '@<!--BEGIN .entry-content .article-->[\s\S]*?<!--END .entry-content .article-->@', $this->external_page, $buffer );

						break;
					case 'point':
						$new_content = preg_replace( '@<div class="single_page">[\s\S]*?<!--.post-content box mark-links-->@', '<div class="single_page">' . $this->external_page, $buffer );
						break;
					case 'modality':
//						if ( $this->hide_sidebar ) {
//							$new_content = preg_replace( '@<div id="content-box">[\s\S]*?<!--content-box-->@', $this->external_page, $buffer );
//						} else {
						$new_content = preg_replace( '@<div id="content-box">[\s\S]*(<div id="comments"[\s\S]*)<!-- #comments -->[\s\S]*<!--content-box-->@', '<div id="content-box">' . $this->external_page . '$1</div><!--content-box-->', $buffer );
//						}
						break;
					default:
						$new_content = preg_replace( '#<article\s+id="post-\d+.*?</article>#s', $this->external_page, $buffer );
						break;
				}

				if ( $this->hide_sidebar ) {
					switch ( $theme_name ) {
						case 'onetone':
							$new_content =   preg_replace( '#<div class="sidebar[\s\S]*?<!--sidebar-->#', '', $new_content );
							break;
						case 'enigma':
//							$new_content =   preg_replace( '#<div class="col-md-4 enigma-sidebar[\s\S]*?</div>#', '', $new_content );
//							$new_content =   preg_replace( '#<div class="enigma_sidebar_widget[\s\S]*?</div>#', '', $new_content );
							break;
						case 'modality':
							$new_content = preg_replace( '#<div class="sidebar">[\s\S]*?<!--sidebar-frame-->#', '', $new_content );
							break;
						case 'evolve':
							$new_content = preg_replace( '@<div.*?class="aside[\s\S]*?<!--END #secondary .aside-->@', '', $new_content );
							break;

						default :
							$new_content = preg_replace( '#<aside.*?</aside>#s', '', $new_content );
					}
				}

				$theme		 = wp_get_theme();
				$method_name = 'adapt_theme_' . $theme->get_template();
				$method_name = str_replace( '-', '_', $method_name );
				if ( method_exists( $this, $method_name ) ) {
					$new_content = $this->{$method_name}( $new_content );
				}
//				$new_content = $theme_name;
				break;
			case 3:// before
				$new_content = preg_replace( '#<article\s+id="post-\d+#s', $this->external_page . "\n\n<article", $buffer );
				break;
			case 4:// after
				$new_content = preg_replace( '#<article\s+id="post-\d+.*?</article>#s', '$0' . "\n\n" . $this->external_page, $buffer );
				break;
		}
		return $new_content;
	}

	public function adapt_theme_mh_magazine_lite( $content ) {
		$content = preg_replace( '#<h1 class="page-title">[\s\S]*</h1>#', '', $content);
		if ( !$this->hide_sidebar ) {
			$content = str_replace( '<div class="mh-wrapper clearfix"', '<div class="mh-wrapper clearfix" style="width:60%; float:left;"', $content );
			$content = preg_replace( '#(<div class="mh-wrapper[\s\S]*?</aside>)#', '<div class="clearfix">${1}</div>', $content );
		}
		return $content;
	}

	public function adapt_theme_modality( $content ) {
		if ( $this->hide_sidebar ) {
			$content = str_replace( '<div id="content-box"', '<div id="content-box" style="width:100%;"', $content );
		}
		return $content;
	}

	public function adapt_theme_onetone( $content ) {
		if ( $this->hide_sidebar ) {
			$content = str_replace( 'div class="main-content"', 'div class="main-content" style=" padding: 0 0;"', $content );
			$content = str_replace( 'header class="archive-header"', 'header class="archive-header" style=" padding-left: 50px;"', $content );
			$content = str_replace( 'div class="comments-area"', 'div class="comments-area" style=" padding: 0 50px;"', $content );
		}
		return $content;
	}

	public function adapt_theme_flat( $content ) {

		$content = str_replace( 'class="sub container', 'style="padding:0px;" class="sub container', $content );
		if ( $this->hide_sidebar ) {
			$content = str_replace( 'id="vbid-', 'style="padding: 0 0;" id="vbid-', $content );
		}

		return $content;
	}

	public function adapt_theme_make( $content ) {

		$content = str_replace( 'class="sub container', 'style="padding:0px;" class="sub container', $content );

		return $content;
	}

	public function adapt_theme_point( $content ) {

		if ( $this->hide_sidebar ) {
			$content = str_replace( '<article class="article"', '<article class="article" style="width:100%;"', $content );
		}
		return $content;
	}

	public function adapt_theme_vantage( $content ) {

		if ( $this->hide_sidebar ) {
//			$content = str_replace( '<div class="full-container"', '<div class="full-container" style="max-width:none;"', $content );
//			$content = str_replace( 'id="primary"', 'id="primary" style="width:100%"', $content );
			$content = str_replace( 'id="xprs"', 'id="primary" style="width:133.3%"', $content );
		}
		return $content;
	}

	public function adapt_theme_enigma( $content ) {

		$content = str_replace( 'class="sub container', 'style="padding-left:0px;padding-right:0px;" class="sub container', $content );
		$content = str_replace( 'div id="content" ', 'div style="padding:0px;" id="content" ', $content );
		$content = str_replace( 'div class="enigma_header_breadcrum_title"', 'div class="enigma_header_breadcrum_title" style="margin-bottom:0px;"', $content );
		if ( $this->hide_sidebar ) {
			$content = preg_replace( '/<div class="col-md-4 enigma-sidebar[\s\S]*<!-- enigma Callout Section -->/', '</div></div>', $content );
			$content = str_replace( '<div class="col-md-8"', '<div class="col-md-8" style="width:100%;"', $content );
			$content = str_replace( '<div class="container">', '<div class="container" style="width:100%">', $content );
		}
		return $content;
	}

	public function adapt_theme_sydney( $content ) {

		$content = str_replace( 'class="sub container', 'style="padding-left:0px;padding-right:0px;" class="sub container', $content );
		$content = str_replace( 'div id="content" ', 'div style="padding:0px;" id="content" ', $content );
		if ( $this->hide_sidebar ) {
			$content = str_replace( 'id="primary"', 'id="primary" style="width:100%"', $content );
		}
		return $content;
	}

	public function adapt_theme_virtue( $content ) {
		$content = preg_replace( '#<div class="page-header">[\s\S]*?</div>#', '', $content );
		$content = str_replace( 'class="sub container', 'style="padding-left:0px;padding-right:0px;" class="sub container', $content );
		if ( $this->hide_sidebar ) {
			$content = str_replace( 'class="main col-lg-9 col-md-8"', 'class="main col-lg-9 col-md-8" style="width:100%;padding-top:10px;"', $content );
		} else {
			$content = str_replace( 'class="main col-lg-9 col-md-8"', 'class="main col-lg-9 col-md-8" style="padding-top:10px;"', $content );
		}
		return $content;
	}

	public function adapt_theme_twentyfifteen( $content ) {
		$content = str_replace( '<main id="main"', '<main id="main" style="padding:0 0 7.6923% 0"', $content );
		if ( $this->hide_sidebar ) {
			$content = preg_replace( '#<div id="sidebar[\s\S]*?<!-- .sidebar -->#', '', $content );
			$content = str_replace( '<head>', '<head><style> body:before{ background-color: #f1f1f1; box-shadow: none; z-index: -1; }</style>', $content );
			$content = str_replace( 'id="content"', 'id="content" style="margin-left: 0px; width: 100%;"', $content );
			$content = str_replace( 'footer id="colophon"', 'footer id="colophon" style="margin-left: 8.5%; width: 83%;"', $content );
		}
		return $content;
	}

	public function adapt_theme_twentyfourteen( $content ) {
		$content = str_replace( 'class="content-area"', 'class="content-area" style="padding-top:0px;"', $content );
		if ( $this->hide_sidebar ) {
			$content = str_replace( '<head>', '<head><style> .my-site:before{ z-index: -1; }</style>', $content );
			$content = str_replace( '<div id="page" class="hfeed site">', '<div id="page" class="hfeed site my-site">', $content );
			$content = str_replace( 'id="content"', 'id="content" style="margin-left: 0px"', $content );
		}
		return $content;
	}

	public function adapt_theme_colormag( $content ) {
		if ( $this->hide_sidebar ) {
			$content = str_replace( 'id="primary"', 'id="primary" style="width:100%"', $content );
		}
		return $content;
	}

	public function adapt_theme_storefront( $content ) {
		if ( $this->hide_sidebar ) {
			$content = str_replace( 'id="primary"', 'id="primary" style="width:100%"', $content );
		}
		return str_replace( 'class="site-header"', 'class="site-header" style="margin-bottom:0px;"', $content );

	}

	public function adapt_theme_zerif_lite( $content ) {
		$content = str_replace( 'class="sub container', 'style="padding-left:0px;padding-right:0px;" class="sub container', $content );
		if ( $this->hide_sidebar ) {
			$content = str_replace( 'class="content-left-wrap col-md-9"', 'class="content-left-wrap col-md-9" style="width:100%;padding-top:10px;"', $content );
		} else {
			$content = str_replace( 'class="content-left-wrap col-md-9"', 'class="content-left-wrap col-md-9" style="padding-top:10px;"', $content );
		}
		$content = str_replace( 'class="item-content', 'style="-webkit-transform:none;transform:none" class="item-content', $content );
		return $content;
	}

	public function adapt_theme_customizr( $content ) {
		$content = str_replace( 'class="sub container', 'style="padding-left:0px;padding-right:0px;" class="sub container', $content );
		if ( $this->hide_sidebar ) {
//			$content = str_replace( 'class="span9 article-container tc-gallery-style"', 'class="span9 article-container tc-gallery-style" style="width:100%;"', $content );
		}

		return $content;
	}

}
