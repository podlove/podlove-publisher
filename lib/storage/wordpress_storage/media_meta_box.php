<?php
namespace Podlove\Storage\WordpressStorage;

use \Podlove\Model;
use \Podlove\Model\Episode;
use \Podlove\Model\EpisodeAsset;
use \Podlove\Model\MediaFile;

class MediaMetaBox {

	public function __construct() {
		add_action('add_meta_boxes_podcast', [$this, 'add_meta_box']);
		add_action('save_post_podcast', [$this, 'save_post']);
	}

	public function add_meta_box() {
		add_meta_box(
			/* $id       */ 'podlove_podcast_media',
			/* $title    */ __( 'Podcast Media', 'podlove' ),
			/* $callback */ [$this, 'meta_box_callback'],
			/* $page     */ 'podcast',
			/* $context  */ 'advanced',
			/* $priority */ 'high'
		);
	}

	public function save_post($post_id)
	{
		if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) 
			return;
		
		if (empty($_POST['podlove_noncename']) || ! wp_verify_nonce($_POST['podlove_noncename'], \Podlove\PLUGIN_FILE))
			return;
		
		if ('podcast' !== $_POST['post_type'])
			return;

		if (!current_user_can('edit_post', $post_id))
			return;

		if (!isset($_POST['_podlove_media']))
			return;

		$attachment_id = (int) $_POST['_podlove_media'];
		$attachment_meta = wp_get_attachment_metadata($attachment_id);

		update_post_meta($post_id, 'podlove_media_attachment_id', $attachment_id);
		$episode = Episode::find_or_create_by_post_id($post_id);

		// @fixme: ensure it's the correct asset, not just any
		$asset = EpisodeAsset::first();
		if (!$file = MediaFile::find_by_episode_id_and_episode_asset_id($episode->id, $asset->id)) {
			$file = new MediaFile;
			$file->episode_id = $episode->id;
			$file->episode_asset_id = $asset->id;
		}
		
		$file->size = $attachment_meta['filesize'];
		$file->save();
	}

	public function meta_box_callback($post) {

		$post_id = $post->ID;

		$podcast = Model\Podcast::get();
		$episode = Model\Episode::find_or_create_by_post_id($post_id);

		$attachment_id = get_post_meta($post_id, 'podlove_media_attachment_id', true);
		$attachment = wp_prepare_attachment_for_js($attachment_id);

		?>

<div class="podlove_media_upload" <?php if ($attachment): ?>style="display:none"<?php endif ?>>
	<button class="button" id="podlove_episode_media_upload_button">Add Episode Media</button>
	<input type="hidden" value="<?php echo $attachment_id ?>" name="_podlove_media" id="podlove_episode_media_field" />
</div>

<div class="podlove_media_attachment" <?php if (!$attachment): ?>style="display:none"<?php endif ?>>
	<div class="podlove-icon"></div>
	<div class="podlove-meta">
		<table>
			<tr>
				<th>Permalink</th>
				<td><div class="podlove-permalink"></div></td>
			</tr>
			<tr>
				<th>Size</th>
				<td><div class="podlove-size"></div></td>
			</tr>
			<tr>
				<th>Duration</th>
				<td><div class="podlove-duration"></div></td>
			</tr>
		</table>
	</div>
</div>

<script type="text/javascript">
var podlove_media_attachment_data = <?php echo $attachment ? json_encode($attachment) : 'null' ?>;
</script>

<script type="text/javascript">
(function($) {

	function render_attachment(attachment) {
		var wrapper   = $(".podlove_media_attachment"),
			icon      = wrapper.find(".podlove-icon"),
			permalink = wrapper.find(".podlove-permalink"),
			size      = wrapper.find(".podlove-size"),
			duration  = wrapper.find(".podlove-duration"),
			upload    = $(".podlove_media_upload")
		;

		icon.html('<img src="' + attachment.image.src + '" />');
		permalink.html('<a href="' + attachment.url + '">' + attachment.filename + '</a>');
		size.html(attachment.filesizeHumanReadable);
		duration.html(attachment.fileLength);

		wrapper.show();
		upload.hide();
	}

	function init_media_select() {
		var params = {	
			frame:   'select',
			library: { type: 'audio' },
			button:  { text: 'Select Media' },
			className: 'media-frame',
			title: 'Episode Media',
			state: 'podlove_episode_media_state'
		},
		  file_frame = wp.media(params),
		  library = new wp.media.controller.Library({
			id:         params['state'],
			priority:   20,
			filterable: false,
			searchable: true,
			content: 'upload',
			library:    wp.media.query( file_frame.options.library ),
			multiple:   false,
			editable:   false,
			displaySettings: false,
			allowLocalEdits: false
		});

		file_frame.states.add([library]);
		file_frame.on('select update insert', function() {
			var state      = file_frame.state(), 
				attachment = state.get('selection').first().toJSON(),
				value      = attachment.id
				;

			$("#podlove_episode_media_field").val(value);
			render_attachment(attachment);
		});

		$("#podlove_episode_media_upload_button, .podlove-icon").on('click', function(e) {
			e.preventDefault();
			file_frame.open();
		});
	}

	$(document).ready(function () {
		init_media_select();

		if (podlove_media_attachment_data) {
			render_attachment(podlove_media_attachment_data);
		};
	});

})(jQuery);	 
</script>

<style type="text/css">
#podlove_podcast_media {
    background: transparent;
    border: transparent;
    padding: 0;
    margin: 20px 0 0 0;
}

#podlove_podcast_media .handlediv,
#podlove_podcast_media .ui-sortable-handle { display: none; }

.podlove_media_upload {
	width: 100%;
	border: 4px dashed #b4b9be;
	background: #f1f1f1;
	text-align: center;
	padding: 20px;
	box-sizing: border-box;
}

.podlove_media_attachment .podlove-icon {
	cursor: pointer;
	float: left;
	margin-right: 10px;
	width: 64px;
}

.podlove_media_attachment .podlove-icon img {
	max-width: 100%;
	margin-top: 1px;
}

.podlove_media_attachment table th {
	text-align: left;
}
</style>
		<?php
	}

}