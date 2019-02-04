<?php
namespace Podlove\Modules\Shownotes\Model;

use \Podlove\Model\Base;

class Entry extends Base
{
    use \Podlove\Model\KeepsBlogReferenceTrait;

    public function __construct()
    {
        $this->set_blog_id();
    }
}

Entry::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
Entry::property('episode_id', 'INT');
Entry::property('state', 'VARCHAR(255)');
Entry::property('unfurl_data', 'TEXT');
Entry::property('original_url', 'TEXT');
Entry::property('url', 'TEXT');
Entry::property('title', 'TEXT');
Entry::property('description', 'TEXT');
Entry::property('site_name', 'TEXT');
Entry::property('site_url', 'TEXT');
Entry::property('icon', 'TEXT');
