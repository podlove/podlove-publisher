<?php
/**
 * RSS2 Feed Template for displaying RSS2 Posts feed.
 *
 * @package WordPress
 */

header( 'Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=' . get_option( 'blog_charset' ), true );
$more = 1;

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.'>'; ?>
<rss version="2.0"
	xmlns:atom="http://www.w3.org/2005/Atom"
	<?php do_action('rss2_ns'); ?>
>

<channel>
	<title><?php echo apply_filters( 'podlove_feed_title', '' ); ?></title>
	<link><?php bloginfo_rss( 'url' ) ?></link>
	<description><?php echo apply_filters( 'podlove_rss_feed_description', get_bloginfo_rss( 'description' ) ) ?></description>
	<lastBuildDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_lastpostmodified( 'GMT' ), false ); ?></lastBuildDate>
	<?php do_action( 'rss2_head' ); ?>
	<?php while( have_posts()) : the_post(); ?>
	<item>
		<title><?php the_title_rss() ?></title>
		<link><?php the_permalink() ?></link>
		<pubDate><?php echo mysql2date( 'D, d M Y H:i:s +0000', get_post_time( 'Y-m-d H:i:s', true ), false ); ?></pubDate>
		<guid isPermaLink="false"><?php echo htmlspecialchars( get_the_guid() ); ?></guid>
		<?php 
			$episode =  \Podlove\Model\Episode::find_one_by_post_id(get_the_ID());
			$contributors = \Podlove\Modules\Contributors\Model\EpisodeContribution::find_all_by_episode_id($episode->id);
			foreach ($contributors as $list_number => $contributor) {
				$contributor_details = $contributor->getContributor("showpublic=1");
				if($contributor_details->showpublic == 1) {
					echo "		<atom:contributor>\n";
					echo "			<atom:name>".$contributor_details->publicname."</atom:name>\n";
					echo "			<atom:uri>".$contributor_details->guid."</atom:uri>\n";
					echo "		</atom:contributor>\n";
				}
			}
		?>
	<?php rss_enclosure(); ?>
	<?php do_action( 'rss2_item' ); ?>
	</item>
	<?php endwhile; ?>
</channel>
</rss>
