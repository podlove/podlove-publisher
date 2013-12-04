<?php
if (!getenv('WPBASE')) {
	die("You need to set the environment variable WPBASE to your WordPress root\n");
}

require_once  getenv('WPBASE') . '/wp-load.php';
require_once  dirname(__FILE__) . '/../vendor/autoload.php';
require_once  dirname(__FILE__) . '/../bootstrap/bootstrap.php';

$episode = new ReflectionClass('\Podlove\Template\Episode');
$methods = $episode->getMethods();

$accessors = array_filter($methods, function($method) {
	$comment = $method->getDocComment();
	return stripos($comment, '@accessor') !== false;
});

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
		$c = $this->ltrimLines($c);

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

	private function ltrimLines($c) {
		return preg_replace_callback("/^.*$/m", function ($m) {
			return ltrim($m[0]);
		}, $c);
	}

	private function extractTags() {
		$lineNo = count($this->lines) - 1;
		$continue = true;

		do {
			$line = $this->lines[$lineNo];
			if (!!preg_match("/^@(\w+)(\s+(.*))?$/", $line, $matches)) {
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

		$this->description = implode("\n", array_splice($this->lines, $startLine, $endLine - $startLine));
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

print_r(array_map(function($method) {
	$c = new MyComment($method->getDocComment());
	$c->parse();

	return [
		$c->getTitle(),
		$c->getDescription(),
		$c->getTags()
	];
}, $accessors));