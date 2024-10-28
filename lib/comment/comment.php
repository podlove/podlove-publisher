<?php

namespace Podlove\Comment;

class Comment
{
    // the original comment text
    private $comment;

    // array with lines to parse
    private $lines;

    private $title;
    private $description;
    private $tags = [];

    public function __construct($comment)
    {
        $this->comment = $comment;
    }

    public function parse()
    {
        $c = $this->comment;
        $c = $this->removeFirstLine($c);
        $c = $this->removeLastLine($c);
        $c = $this->removeLeadingStars($c);
        $c = $this->removeOneLeadingWhitespace($c);

        $this->lines = explode("\n", $c);

        $this->title = trim($this->lines[0]);

        if (count($this->lines) === 1) {
            return;
        }

        $this->assert(empty($this->lines[1]), 'Second comment line must be empty');

        $this->extractTags();
        $this->extractDescription();
    }

    /**
     * Get comment title.
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Get comment description.
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Filter tags by name.
     *
     * @param string $tagName Filter tags by name
     *
     * @return array All matching tags
     */
    public function getTags($tagName = null)
    {
        if (!$tagName) {
            return $this->tags;
        }

        return array_values(
            array_filter($this->tags, function ($tag) use ($tagName) {
                return $tagName == $tag['name'];
            })
        );
    }

    /**
     * Get tag by name.
     *
     * @param string $tagName Filter tags by name
     *
     * @return array First matching tag
     */
    public function getTag($tagName)
    {
        return $this->getTags($tagName)[0];
    }

    private function assert($condition, $message = '')
    {
        assert($condition, $message."\nComment:\n".$this->comment);
    }

    private function removeFirstLine($c)
    {
        $new = preg_replace("/^\\/\\*\\*\\s*\n/", '', $c, -1, $count);
        $this->assert($count === 1, 'Comments must start with /**');

        return $new;
    }

    private function removeLastLine($c)
    {
        $new = preg_replace('/\s*\*\/\s*$/', '', $c, -1, $count);
        $this->assert($count === 1, 'Comments must end with */');

        return $new;
    }

    private function removeLeadingStars($c)
    {
        $new = preg_replace('/^\s*\*/m', '', $c, -1, $count);
        $this->assert($count > 0, 'Comment lines must start with *');

        return $new;
    }

    private function removeOneLeadingWhitespace($c)
    {
        return preg_replace_callback('/^.*$/m', function ($m) {
            return preg_replace('/^\s/', '', $m[0], 1);
        }, $c);
    }

    private function extractTags()
    {
        $lineNo = count($this->lines) - 1;
        $continue = true;

        do {
            $line = $this->lines[$lineNo];
            if ((bool) preg_match('/^@(\w+)(\s+(.*))?$/i', $line, $matches)) {
                $this->tags[] = [
                    'name' => $matches[1],
                    'description' => isset($matches[3]) ? $matches[3] : '',
                    'line' => $lineNo
                ];
                --$lineNo;
            } else {
                if (strlen($line) == 0) {
                    --$lineNo;
                } else {
                    $continue = false;
                }
            }
        } while ($lineNo > 0 && $continue == true);
    }

    private function extractDescription()
    {
        $startLine = 2;

        if (count($this->tags)) {
            $endLine = min(array_map(function ($t) { return $t['line']; }, $this->tags)) - 1;
        } else {
            $endLine = count($this->lines) - 1;
        }

        if ($endLine - $startLine > 0) {
            $this->description = implode("\n", array_splice($this->lines, $startLine, $endLine - $startLine + 1));
        }
    }
}
