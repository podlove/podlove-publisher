<?php
namespace Podlove\Model;

use Podlove\Log;
use Podlove\ChaptersManager;
use Podlove\Model\Image;

/**
 * We could use simple post_meta instead of a table here
 */
class Episode extends Base implements Licensable {

	use KeepsBlogReferenceTrait;

	public function __construct() {
		$this->set_blog_id();
	}

	public static function find_all_by_time($args = []) {
		global $wpdb;

		$defaults = [
			'post_status' => ['private', 'draft', 'publish', 'pending', 'future']
		];
		$args = wp_parse_args($args, $defaults);

		if (!is_array($args['post_status']))
			$args['post_status'] = [$args['post_status']];

		$sql = '
			SELECT
				e.*
			FROM
				`' . Episode::table_name() . '` e 
				JOIN `' . $wpdb->posts . '` p ON e.post_id = p.ID
			WHERE
				p.post_status IN (' . implode(", ", array_map(function($s) { return '"' . $s . '"'; }, $args['post_status'])) . ')
				AND
				p.post_type = "podcast"
			ORDER BY
				p.post_date DESC';

		return Episode::find_all_by_sql($sql);
	}

	public static function latest() {
		global $wpdb;

		// Why do we fetch 10 instead of just 1?
		// Because some of the newest ones might be drafts or otherwise invalid.
		// So we grab a bunch, filter by validity and then return the first one.
		$sql = '
			SELECT
				*
			FROM
				`' . Episode::table_name() . '` e 
				JOIN `' . $wpdb->posts . '` p ON e.post_id = p.ID
			ORDER BY
				p.post_date DESC
			LIMIT 0, 10';

		$episodes = array_filter(Episode::find_all_by_sql($sql), function($e) { return $e->is_valid(); });

		return reset($episodes);
	}

	public function title() {
		return $this->with_blog_scope(function() { return get_the_title($this->post_id); });
	}

	/**
	 * Generate a human readable title.
	 * 
	 * Return name and, if available, the subtitle. Separated by a dash.
	 * 
	 * @return string
	 */
	public function full_title() {
		
		$title = $this->title();
		
		if ($this->subtitle)
			$title = $title . ' - ' . $this->subtitle;
		
		return $title;
	}

	public function description() {
		
		if ($this->summary) {
			$description = $this->summary;
		} elseif ($this->subtitle) {
			$description = $this->subtitle;
		} else {
			$description = $this->title();
		}
		
		return htmlspecialchars(trim($description));
	}

	public function post() {
		return $this->with_blog_scope(function() {
			return get_post($this->post_id);
		});
	}

	public function permalink() {
		return $this->with_blog_scope(function() {
			return get_permalink($this->post_id);
		});
	}

	public function meta($meta_key, $single = true) {
		return $this->with_blog_scope(function() use($meta_key, $single) {
			return get_post_meta($this->post_id, $meta_key, $single);
		});
	}

	public function tags($args = []) {
		return $this->with_blog_scope(function() use ($args) {
			return wp_get_post_tags($this->post_id, $args);
		});
	}

	public function categories($args = []) {

		// "wp_get_post_categories" defaults to "fields => ids" so we need to set it manually
		$args['fields'] = 'all';

		return $this->with_blog_scope(function() use ($args) {
			return wp_get_post_categories($this->post_id, $args);
		});
	}

	public function player($context = NULL) {
		return $this->with_blog_scope(function() use ($context) {
			return (new \Podlove\Modules\PodloveWebPlayer\Printer($this))->render($context);
		});
	}

	public function explicit_text() {

		if ($this->explicit == 2)
			return 'clean';

		return $this->explicit ? 'yes' : 'no';
	}

	public function media_files() {
		return $this->with_blog_scope(function() {
			$sql = '
				SELECT M.*
				FROM ' . MediaFile::table_name() . ' M
					JOIN ' . EpisodeAsset::table_name() . ' A ON A.id = M.episode_asset_id
				WHERE M.episode_id = \'' . $this->id . '\'
				ORDER BY A.position ASC
			';

			return MediaFile::find_all_by_sql($sql);
		});
	}

	public static function find_or_create_by_post_id($post_id) {
		$episode = Episode::find_one_by_property( 'post_id', $post_id );

		if ( $episode )
			return $episode;

		$episode = new Episode();
		$episode->post_id = $post_id;
		$episode->save();

		return $episode;
	}

	public function enclosure_url($episode_asset, $source = "feed", $context = null) {
		return MediaFile::find_by_episode_id_and_episode_asset_id($this->id, $episode_asset->id)->get_public_file_url($source, $context);
	}

	public function cover_art_with_fallback() {
		return $this->with_blog_scope(function() {

			if ( ! $image = $this->cover_art() )
				$image = Podcast::get()->cover_art();

			return $image;
		});
	}

	public function cover_art() {
		return $this->with_blog_scope(function() {
			$podcast = Podcast::get();
			$asset_assignment = AssetAssignment::get_instance();

			if ( ! $asset_assignment->image )
				return false;
			
			if ($asset_assignment->image == 'manual') {
				$cover_art = trim($this->cover_art);
				if (empty($cover_art)) {
					return false;
				} else {
					return new Image($cover_art, $this->title());
				}
			}

			$cover_art_file_id = $asset_assignment->image;
			if ( ! $asset = EpisodeAsset::find_one_by_id( $cover_art_file_id ) )
				return false;

			if ( ! $file = MediaFile::find_by_episode_id_and_episode_asset_id( $this->id, $asset->id ) )
				return false;

			return ( $file->size > 0 ) ? new Image($file->get_file_url(), $this->title()) : false;
		});
	}

	/**
	 * Get episode chapters.
	 * 
	 * @param  string $format object, psc, mp4chaps, json. Default: object
	 * @return mixed
	 */
	public function get_chapters($format = 'object') {
		return $this->with_blog_scope(function() use ($format) {
			return (new ChaptersManager($this))->get($format);
		});
	}

	public function refetch_files() {

		$valid_files = array();
		foreach ( EpisodeAsset::all() as $asset ) {
			if ( $file = MediaFile::find_by_episode_id_and_episode_asset_id( $this->id, $asset->id ) ) {
				$file->determine_file_size();
				$file->save();
				
				if ( $file->is_valid() )
					$valid_files[] = $file->id;
			}
		}

		if ( empty( $valid_files ) && get_post_status( $this->post_id ) == 'publish' )
			Log::get()->addAlert( 'All assets for this episode are invalid!', array( 'episode_id' => $this->id ) );
	}

	public function get_duration($format = 'HH:MM:SS') {
		return (new \Podlove\Duration($this->duration))->get($format);
	}

	/**
	 * @todo episode should not know about cache; better: $cache->delete_for($episode) 
	 */
	public function delete_caches() {

		// delete caches for current episode
		delete_transient( 'podlove_chapters_string_' . $this->id );

		// delete caches for revisions of this episode
		if ( $revisions = wp_get_post_revisions( $this->post_id ) ) {
			foreach ( $revisions as $revision ) {
				if ( $revision_episode = Episode::find_one_by_post_id( $revision->ID ) ) {
					delete_transient( 'podlove_chapters_string_' . $revision_episode->id );
				}
			}
		}

		\Podlove\Cache\TemplateCache::get_instance()->setup_purge();
	}

	/**
	 * Check for basic validity.
	 *
	 * - MUST have an existing associated post
	 * - associated post MUST be of type 'podcast'
	 * - MUST NOT be deleted/trashed
	 * 
	 * @return boolean
	 */
	public function is_valid() {

		$post = get_post( $this->post_id );

		if ( ! $post )
			return false;

		// skip deleted podcasts
		if ( ! in_array( $post->post_status, array( 'private', 'draft', 'publish', 'pending', 'future' ) ) )
			return false;

		// skip versions
		if ( $post->post_type != 'podcast' )
			return false;

		return true;
	}

	public function is_published() {
		
		if (!$post = get_post($this->post_id))
			return false;

		return in_array($post->post_status, array('private', 'publish'));
	}

	public function get_license()
	{
		$license = new License('episode', array(
			'license_name' => $this->license_name,
			'license_url'  => $this->license_url
		));

		return $license;
	}

	public function get_license_picture_url() {
		return $this->get_license()->getPictureUrl();
	}

	public function get_license_html() {
		return $this->get_license()->getHtml();
	}	
}

Episode::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Episode::property( 'post_id', 'INT' );
Episode::property( 'subtitle', 'TEXT' );
Episode::property( 'summary', 'TEXT' );
Episode::property( 'enable', 'INT' ); // listed in podcast directories or not?
Episode::property( 'slug', 'VARCHAR(255)' );
Episode::property( 'duration', 'VARCHAR(255)' );
Episode::property( 'cover_art', 'VARCHAR(255)' );
Episode::property( 'chapters', 'TEXT' );
Episode::property( 'recording_date', 'DATETIME' );
Episode::property( 'explicit', 'TINYINT' );
Episode::property( 'license_name', 'TEXT' );
Episode::property( 'license_url', 'TEXT' );
