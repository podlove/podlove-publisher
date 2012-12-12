<?php
/**
 * Atom Feed Template for displaying Atom Posts feed.
 *
 * @package WordPress
 */

header( 'Content-Type: ' . feed_content_type( 'atom' ) . '; charset=' . get_option( 'blog_charset' ), true );
$more = 1;

echo '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?>'; ?>
<feed
  xmlns="http://www.w3.org/2005/Atom"
  xmlns:thr="http://purl.org/syndication/thread/1.0"
  <?php do_action('atom_ns'); ?>
 >
	<title type="text"><?php echo apply_filters( 'podlove_feed_title', '' ); ?></title>
	<subtitle type="text"><?php echo apply_filters( 'podlove_atom_feed_subtitle', get_bloginfo_rss( 'description' ) ) ?></subtitle>

	<updated><?php echo mysql2date( 'Y-m-d\TH:i:s\Z', get_lastpostmodified( 'GMT' ), false ); ?></updated>

	<link rel="alternate" type="text/html" href="<?php bloginfo_rss( 'url' ) ?>" />
	<id><?php echo get_bloginfo( 'atom_url' ); ?></id>
	<?php do_action( 'atom_head' ); ?>
	<?php while ( have_posts() ) : the_post(); ?>
	<entry>
		<author>
			<?php do_action( 'atom_author' ); ?>
		</author>
		<title type="<?php echo apply_filters( 'podlove_feed_title_type', 'text' ); ?>"><?php the_title_rss() ?></title>
		<link rel="alternate" type="text/html" href="<?php the_permalink() ?>" />
		<id><?php echo htmlspecialchars(get_the_guid()) ; ?></id>
		<updated><?php echo get_post_modified_time('Y-m-d\TH:i:s\Z', true); ?></updated>
		<published><?php echo get_post_time('Y-m-d\TH:i:s\Z', true); ?></published>
		<?php atom_enclosure(); ?>
		<?php do_action('atom_entry'); ?>
	</entry>
	<?php endwhile ; ?>
</feed>
