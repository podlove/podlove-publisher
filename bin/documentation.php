<?php
require_once 'vendor/autoload.php'; # composer autoloader

use Podlove\Comment\Comment;

if (!getenv('WPBASE')) {
	die("You need to set the environment variable WPBASE to your WordPress root\n");
}

require_once  getenv('WPBASE') . '/wp-load.php';
require_once  dirname(__FILE__) . '/../vendor/autoload.php';
require_once  dirname(__FILE__) . '/../bootstrap/bootstrap.php';

$output_dir = '/Users/ericteubert/code/podlove.github.com/sources/template';

$classes = [
	'\Podlove\Template\Podcast',
	'\Podlove\Template\Feed',
	'\Podlove\Template\Episode',
	'\Podlove\Template\Asset',
	'\Podlove\Template\File',
	'\Podlove\Template\Chapter',
	'\Podlove\Template\License',
	'\Podlove\Template\FileType',
	'\Podlove\Modules\Contributors\Template\Contributor'
];

foreach ($classes as $class) {
	$reflectionClass = new ReflectionClass($class);
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

	$classComment = new Comment($reflectionClass->getDocComment());
	$classComment->parse();
	$templatetag = $classComment->getTags()[0]['description'];
	 
	assert(strlen($templatetag) > 0, 'templatetag must not be empty');

	// Simply list dynamically added methods.
	// Here we can't parse function doc (or can we?) so we need to find another way to get docs.
	if (isset($class::$dynamicAccessors[$class::get_class_slug()])) {
		foreach ($class::$dynamicAccessors[$class::get_class_slug()] as $dynamicAccessor) {
			$parsedMethods[] = [
				'methodname'  => $dynamicAccessor,
				'title'       => '',
				'description' => '',
				'tags'        => array()
			];
		}
	}

	$classdoc = [
		'class' => [
			'classname' => $reflectionClass->getShortName(),
			'templatetag' => $templatetag,
			'description' => $classComment->getDescription()
		],
		'methods' => array_values($parsedMethods)
	];

	file_put_contents($output_dir . '/' . $templatetag . '.json', json_encode($classdoc), LOCK_EX);
}
