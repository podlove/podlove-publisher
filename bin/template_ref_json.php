<?php
/**
 * Extracts template reference and saves them to JSON files.
 * 
 * Complete workflow for generating reference markdown:
 *
 * 1. $> WPBASE=/path/to/wordpress php bin/template_ref_json.php
 * 2. $> ruby bin/template_ref.rb > doc/tempate_ref.md
 */

require_once 'vendor/autoload.php';

use Podlove\Comment\Comment;

if (!getenv('WPBASE')) {
	die("You need to set the environment variable WPBASE to your WordPress root\n");
}

require_once dirname(__FILE__) . '/../lib/helper.php';
require_once getenv('WPBASE') . '/wp-load.php';
require_once dirname(__FILE__) . '/../bootstrap/bootstrap.php';

// $output_dir = '/tmp/podlove/doc';
$output_dir = dirname(__FILE__) . '/../doc/data/template';
@mkdir($output_dir, 0777, true);

// classes containing dynamic accessors
$dynamicAccessorClasses = [
	'\Podlove\Modules\Contributors\TemplateExtensions',
	'\Podlove\Modules\Social\TemplateExtensions',
	'\Podlove\Modules\SubscribeButton\TemplateExtensions',
];

$classes = [
	'\Podlove\Template\Podcast',
	'\Podlove\Template\Feed',
	'\Podlove\Template\Episode',
	'\Podlove\Template\Asset',
	'\Podlove\Template\File',
	'\Podlove\Template\Duration',
	'\Podlove\Template\Chapter',
	'\Podlove\Template\License',
	'\Podlove\Template\DateTime',
	'\Podlove\Template\FileType',
	'\Podlove\Template\Tag',
	'\Podlove\Template\Category',
	'\Podlove\Template\Image',
	'\Podlove\Modules\Contributors\Template\Contributor',
	'\Podlove\Modules\Contributors\Template\ContributorGroup',
	'\Podlove\Modules\Flattr\Template\Flattr',
	'\Podlove\Modules\Social\Template\Service',
	'\Podlove\Modules\Networks\Template\Network',
	'\Podlove\Modules\Networks\Template\PodcastList'
];

// first, parse dynamic accessors
$dynamicAccessors = [];
foreach ($dynamicAccessorClasses as $class) {
	$reflectionClass = new ReflectionClass($class);
	$methods = $reflectionClass->getMethods();

	$accessors = array_filter($methods, function($method) {
		$comment = $method->getDocComment();
		return stripos($comment, '@accessor') !== false && stripos($comment, '@dynamicAccessor') !== false;
	});

	$parsedMethods = array_map(function($method) {
		$c = new Comment($method->getDocComment());
		$c->parse();

		$dynamicAccessor = $c->getTag('dynamicAccessor');
		$callData = explode(".", $dynamicAccessor['description']);

		return [
			'methodname'  => $callData[1],
			'title'       => $c->getTitle(),
			'description' => $c->getDescription(),
			'tags'        => $c->getTags(),
			'class' => $callData[0]
		];
	}, $accessors);

	foreach ($parsedMethods as $method) {
		if (!isset($dynamicAccessors[$method['class']]))
			$dynamicAccessors[$method['class']] = [];

		$dynamicAccessors[$method['class']][$method['methodname']] = $method;
	}
}

foreach ($classes as $class) {
	$reflectionClass = new ReflectionClass($class);
	$className = $reflectionClass->getShortName();
	$methods = $reflectionClass->getMethods();

	$accessors = array_filter($methods, function($method) {
		$comment = $method->getDocComment();
		return stripos($comment, '@accessor') !== false;
	});

	$parsedMethods = array_map(function($method) {
		$c = new Comment($method->getDocComment());
		$c->parse();

		return [
			'methodname'  => $method->name,
			'title'       => $c->getTitle(),
			'description' => $c->getDescription(),
			'tags'        => $c->getTags()
		];
	}, $accessors);

	if (isset($dynamicAccessors[strtolower($className)])) {
		foreach ($dynamicAccessors[strtolower($className)] as $dynamicMethodName => $dynamicMethod) {
			$parsedMethods[] = $dynamicMethod;
		}
	}

	$classComment = new Comment($reflectionClass->getDocComment());
	$classComment->parse();
	$templatetag = $classComment->getTags()[0]['description'];
	 
	assert(strlen($templatetag) > 0, 'templatetag must not be empty');

	$classdoc = [
		'class' => [
			'classname'   => $className,
			'templatetag' => $templatetag,
			'description' => $classComment->getDescription()
		],
		'methods' => array_values($parsedMethods)
	];

	file_put_contents($output_dir . '/' . $templatetag . '.json', json_encode($classdoc), LOCK_EX);
}
