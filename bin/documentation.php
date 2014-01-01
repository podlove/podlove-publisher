<?php
if (!getenv('WPBASE')) {
	die("You need to set the environment variable WPBASE to your WordPress root\n");
}

require_once  getenv('WPBASE') . '/wp-load.php';
require_once  dirname(__FILE__) . '/../vendor/autoload.php';
require_once  dirname(__FILE__) . '/../bootstrap/bootstrap.php';

class MyComment {

	// the original comment text
	private $comment;

	// array with lines to parse
	private $lines;

	private $title;
	private $description;
	private $tags = [];

	public function __construct($comment) {
		$this->comment = $comment;
	}

	public function parse() {
		$c = $this->comment;
		$c = $this->removeFirstLine($c);
		$c = $this->removeLastLine($c);
		$c = $this->removeLeadingStars($c);
		$c = $this->removeOneLeadingWhitespace($c);

		$this->lines = explode("\n", $c);

		$this->title = trim($this->lines[0]);

		if (count($this->lines) === 1)
			return;

		$this->assert(empty($this->lines[1]), "Second comment line must be empty");

		$this->extractTags();
		$this->extractDescription();
	}

	private function assert($condition, $message = '') {
		assert($condition, $message . "\nComment:\n" . $this->comment);
	}

	private function removeFirstLine($c) {
		$new = preg_replace("/^\/\*\*\s*\n/", "", $c, -1, $count);
		$this->assert($count === 1, "Comments must start with /**");
		return $new;
	}

	private function removeLastLine($c) {
		$new = preg_replace("/\s*\*\/\s*$/", "", $c, -1, $count);
		$this->assert($count === 1, "Comments must end with */");
		return $new;
	}

	private function removeLeadingStars($c) {
		$new = preg_replace("/^\s*\*/m", "", $c, -1, $count);
		$this->assert($count > 0, "Comment lines must start with *");
		return $new;
	}

	private function removeOneLeadingWhitespace($c) {
		return preg_replace_callback("/^.*$/m", function ($m) {
			return preg_replace("/^\s/", '', $m[0], 1);
		}, $c);
	}

	private function extractTags() {
		$lineNo = count($this->lines) - 1;
		$continue = true;

		do {
			$line = $this->lines[$lineNo];
			if (!!preg_match("/^@(\w+)(\s+(.*))?$/i", $line, $matches)) {
				$this->tags[] = [
					'name' => $matches[1],
					'description' => isset($matches[3]) ? $matches[3] : '',
					'line' => $lineNo
				];
				$lineNo--;
			} else {
				$continue = false;
			}
		} while ($lineNo > 0 && $continue == true);
	}

	public function extractDescription() {
		$startLine = 2;

		if (count($this->tags)) {
			$endLine = min(array_map(function($t){ return $t['line']; }, $this->tags)) - 1;
		} else {
			$endLine = count($this->lines) - 1;
		}

		if ($endLine - $startLine > 0) {
			$this->description = implode("\n", array_splice($this->lines, $startLine, $endLine - $startLine));
		}
	}

	public function getTitle() {
		return $this->title;
	}

	public function getDescription() {
		return $this->description;
	}

	public function getTags() {
		return $this->tags;
	}
}

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
];

foreach ($classes as $class) {
	$reflectionClass = new ReflectionClass($class);
	$methods = $reflectionClass->getMethods();

	$accessors = array_filter($methods, function($method) {
		$comment = $method->getDocComment();
		return stripos($comment, '@accessor') !== false;
	});

	$parsedMethods = array_map(function($method) {
		$c = new MyComment($method->getDocComment());
		$c->parse();

		return [
			'methodname'  => $method->name,
			'title'       => $c->getTitle(),
			'description' => $c->getDescription(),
			'tags'        => $c->getTags()
		];
	}, $accessors);

	$classComment = new MyComment($reflectionClass->getDocComment());
	$classComment->parse();
	$templatetag = $classComment->getTags()[0]['description'];
	 
	assert(strlen($templatetag) > 0, 'templatetag must not be empty');

	// Simply list dynamically added methods.
	// Here we can't parse function doc (or can we?) so we need to find another way to get docs.
	if (isset($class::$dynamicAccessors)) {
		foreach ($class::$dynamicAccessors as $dynamicAccessor) {
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
