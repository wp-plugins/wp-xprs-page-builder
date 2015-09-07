<?php if (!defined('ABSPATH')) die('qweqwe');?>
<?php get_header(); ?>
<?php echo $this->external_page; ?>
<?php if(!$this->hide_sidebar) get_sidebar(); ?>
<div id="main-content" class="main-content">
	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">
			<article id="post-<?php echo $post->ID; ?>" <?php post_class(); ?>>
				<div class="entry-content">

				</div><!-- .entry-content -->
			</article><!-- #post -->

		</div><!-- #content -->
	</div><!-- #primary -->

</div><!-- #main-content -->
<?php if(!$this->hide_comments) comments_template( '', true ); ?>
<?php get_footer(); ?>
