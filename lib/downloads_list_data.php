<?php
namespace Podlove;

class Downloads_List_Data {
	public static function get_columns() {
		return array(
			// 'episode'   => __('Episode', 'podlove-podcasting-plugin-for-wordpress'),
			'downloads' => __('Total', 'podlove-podcasting-plugin-for-wordpress'),
			'3y' => __('3y', 'podlove-podcasting-plugin-for-wordpress'),
			'2y' => __('2y', 'podlove-podcasting-plugin-for-wordpress'),
			'1y' => __('1y', 'podlove-podcasting-plugin-for-wordpress'),
			'3q' => __('3q', 'podlove-podcasting-plugin-for-wordpress'),
			'2q' => __('2q', 'podlove-podcasting-plugin-for-wordpress'),
			'1q' => __('1q', 'podlove-podcasting-plugin-for-wordpress'),
			'4w' => __('4w', 'podlove-podcasting-plugin-for-wordpress'),
			'3w' => __('3w', 'podlove-podcasting-plugin-for-wordpress'),
			'2w' => __('2w', 'podlove-podcasting-plugin-for-wordpress'),
			'1w' => __('1w', 'podlove-podcasting-plugin-for-wordpress'),
			'6d' => __('6d', 'podlove-podcasting-plugin-for-wordpress'),
			'5d' => __('5d', 'podlove-podcasting-plugin-for-wordpress'),
			'4d' => __('4d', 'podlove-podcasting-plugin-for-wordpress'),
			'3d' => __('3d', 'podlove-podcasting-plugin-for-wordpress'),
			'2d' => __('2d', 'podlove-podcasting-plugin-for-wordpress'),
			'1d' => __('1d', 'podlove-podcasting-plugin-for-wordpress')
		);
  }
  
  public static function get_data($orderby = 'post_date', $order = 'desc')
  {
    $data = [];
		foreach (Model\Podcast::get()->episodes() as $episode) {
			$post = $episode->post();

			$data[] = [
				'title' => $post->post_title,
				'id' => (int) $episode->id,
				'post_id' => (int) $episode->post_id,
				'post_date' => $post->post_date,
				'post_date_gmt' => $post->post_date_gmt,
				'days_since_release' => $episode->days_since_release(),
				'hours_since_release' => $episode->hours_since_release(),
				'downloads' => get_post_meta($post->ID, '_podlove_downloads_total', true),
				'3y' => get_post_meta($post->ID, '_podlove_downloads_3y', true),
				'2y' => get_post_meta($post->ID, '_podlove_downloads_2y', true),
				'1y' => get_post_meta($post->ID, '_podlove_downloads_1y', true),
				'3q' => get_post_meta($post->ID, '_podlove_downloads_3q', true),
				'2q' => get_post_meta($post->ID, '_podlove_downloads_2q', true),
				'1q' => get_post_meta($post->ID, '_podlove_downloads_1q', true),
				'4w' => get_post_meta($post->ID, '_podlove_downloads_4w', true),
				'3w' => get_post_meta($post->ID, '_podlove_downloads_3w', true),
				'2w' => get_post_meta($post->ID, '_podlove_downloads_2w', true),
				'1w' => get_post_meta($post->ID, '_podlove_downloads_1w', true),
				'6d' => get_post_meta($post->ID, '_podlove_downloads_6d', true),
				'5d' => get_post_meta($post->ID, '_podlove_downloads_5d', true),
				'4d' => get_post_meta($post->ID, '_podlove_downloads_4d', true),
				'3d' => get_post_meta($post->ID, '_podlove_downloads_3d', true),
				'2d' => get_post_meta($post->ID, '_podlove_downloads_2d', true),
				'1d' => get_post_meta($post->ID, '_podlove_downloads_1d', true) 
			];
    }

		$valid_order_keys = array(
			'post_date',
			'downloads'
		);

		// look for order options
		if ( isset($orderby) && in_array($orderby, $valid_order_keys) ) {
			$orderby = $orderby;
		} else {
			$orderby = 'post_date';
		}

		// look how to sort
		if( isset($order)  ) {
			$order = strtoupper($order) == 'ASC' ? SORT_ASC : SORT_DESC;
		} else{
			$order = SORT_DESC;
		}

		array_multisort(
			\array_column($data, $orderby), $order,
			$data
		);    
    
    return $data;
  }
}
