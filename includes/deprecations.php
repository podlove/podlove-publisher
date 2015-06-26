<?php
add_action( 'admin_notices', 'podlove_init_deprecation_checker' );

function podlove_init_deprecation_checker() {
	
	// dashboard only
	if (filter_input(INPUT_GET, 'page') !== 'podlove_settings_handle')
		return;

	$cache = \Podlove\Cache\TemplateCache::get_instance();
	$deprecations = $cache->cache_for('podlove_template_deprecations', function() {
		return podlove_get_deprecations();
	});

	podlove_render_deprecations($deprecations);
}

function podlove_get_template_deprecations() {

	$deprecations = [];

	$deprecation_matcher = [
		'shortcode' => [
			'data'    => podlove_get_deprecated_shortcodes(),
			'matcher' => array_keys(podlove_get_deprecated_shortcodes())
		],
		'template tag' => [
			'data'    => podlove_get_deprecated_template_tags(),
			'matcher' => array_keys(podlove_get_deprecated_template_tags())
		]
	];

	foreach (\Podlove\Model\Template::all() as $template) {
		foreach ($deprecation_matcher as $deprecated_type => $matcher) {
			foreach ($matcher['matcher'] as $regex) {
				if (preg_match("/" . $regex . "/", $template->content, $matches)) {
					$deprecations[] = [
						'context' => [ 'type' => 'template', 'id' => $template->id ],
						'deprecated' => [
							'type'    => $deprecated_type,
							'content' => $matches[0]
						],
						'instead' => $matcher['data'][$regex]
					];
				}
			}
		}
	}

	return $deprecations;
}

function podlove_get_episodes_deprecations() {

	$deprecations = [];

	$shortcodes_data   = podlove_get_deprecated_shortcodes();
	$shortcode_matcher = array_keys($shortcodes_data);

	$query = new \WP_Query(['post_type' => 'podcast']);
	while ($query->have_posts()) {
		$post = $query->next_post();
		
		foreach ($shortcode_matcher as $shortcode) {
			if (preg_match("/" . $shortcode . "/", $post->post_content, $matches)) {
				$deprecations[] = [
					'context' => [
						'type' => 'post',
						'id'   => $post->ID
					],
					'deprecated' => [
						'type'    => 'shortcode',
						'content' => $matches[0]
					],
					'instead' => $shortcodes_data[$shortcode]
				];
			}
		}

		// hint: template tags don't need to be checked in episodes because they only work in templates
	}

	return $deprecations;
}

function podlove_get_deprecations() {

	$deprecations = array_merge(
		podlove_get_template_deprecations(), 
		podlove_get_episodes_deprecations()
	);	

	return apply_filters('podlove_deprecations', $deprecations);
}

function podlove_get_deprecation_context($context) {
	switch ($context['type']) {
		case 'template':
			return sprintf(
				'<a href="%s">%s</a>', 
				admin_url('admin.php?page=podlove_templates_settings_handle'),
				sprintf('template "%s"', \Podlove\Model\Template::find_by_id($context['id'])->title)
			);
			break;
		case 'post':
			return sprintf(
				'<a href="%s">%s</a>', 
				get_edit_post_link($context['id']),
				sprintf('post "%s"', get_the_title($context['id']))
			);
			break;
		default:
			return '!!unknown context type ' . $context['type'] . '!!';
			break;
	}
}

function podlove_render_deprecations($deprecations) {

	if (!count($deprecations))
		return;

	?>
	<div id="message" class="error">
		<p>
			<strong>You are using outdated shortcodes. Please fix as soon as possible.</strong>
			<ul>
			<?php foreach ($deprecations as $deprecation): ?>
				<li>
					<?php
					echo sprintf(
						"Outdated %s %s in %s. Instead, use: %s", 
						$deprecation['deprecated']['type'], 
						'<code>' . $deprecation['deprecated']['content'] . '</code>', 
						podlove_get_deprecation_context($deprecation['context']),
						$deprecation['instead']
					); ?>
				</li>
			<?php endforeach ?>
			</ul>
		</p>
	</div>
	<?php
}

function podlove_get_deprecated_shortcodes() {
	return [
		'\[podlove-episode-subtitle[^\]]*]' => '<code>{{ episode.subtitle }}</code>',
		'\[podlove-episode-summary[^\]]*]'  => '<code>{{ episode.summary }}</code>',
		'\[podlove-episode-slug[^\]]*]'     => '<code>{{ episode.slug }}</code>',
		'\[podlove-episode-duration[^\]]*]' => '<code>{{ episode.duration }}</code>',
		'\[podlove-episode-chapters[^\]]*]' => '<code>{{ episode.chapters }}</code>',
		'\[podlove-episode\s+field[^\]]*]'  => '<a href="http://docs.podlove.org/reference/template-tags/#episode">episode template tag</a>',
		'\[podlove-podcast\s+[^\]]*]'       => '<a href="http://docs.podlove.org/reference/template-tags/#podcast">podcast template tag</a>',
		'\[podlove-show[^\]]*]'             => '—',
		'\[podlove-podcast-license[^\]]*]'  => '<code>{% include \'@core/license.twig\' with {\'license\': podcast.license} %}</code>',
		'\[podlove-episode-license[^\]]*]'  => '<code>{% include \'@core/license.twig\' with {\'license\': episode.license} %}</code>',
		'\[podlove-contributors[^\]]*]'     => '<code>[podlove-episode-contributor-list]</code>',
		'\[podlove-contributor-list[^\]]*]' => '<code>[podlove-episode-contributor-list]</code>',
		'\[podlove-web-player[^\]]*]'       => '<code>[podlove-episode-web-player]</code> (or <code>{{ episode.player }}</code> in templates)',
		'\[podlove-subscribe-button[^\]]*]' => '<code>[podlove-podcast-subscribe-button]</code> (or <code>{{ podcast.subscribeButton }}</code> in templates)'
	];
}

function podlove_get_deprecated_template_tags() {
	return [
		'\{\{\s*contributor\.publicemail\s*\}\}' => 'the social module to manage and display the email',
		'\{\{\s*[^\}]*license.html\s*\}\}'       => '<code>{% include \'@core/license.twig\' %}</code>'
	];
}