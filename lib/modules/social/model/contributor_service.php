<?php

namespace Podlove\Modules\Social\Model;

use Podlove\Model\Base;

/**
 * A contributor contributes to a podcast/show.
 */
class ContributorService extends Base
{
    use \Podlove\Model\KeepsBlogReferenceTrait;

    public function __construct()
    {
        $this->set_blog_id();
    }

    public function save()
    {
        global $wpdb;

        if (!$this->position) {
            $pos = $wpdb->get_var(
                sprintf(
                    'SELECT MAX(position)+1 FROM %s WHERE contributor_id = %d',
                    self::table_name(),
                    $this->contributor_id
                )
            );

            $this->position = $pos ? $pos : 1;
        }

        parent::save();
    }

    public function get_service()
    {
        return $this->with_blog_scope(function () {
            return Service::find_one_by_id($this->service_id);
        });
    }

    public function get_service_url()
    {
        $service = $this->get_service();

        return str_replace('%account-placeholder%', $this->value, $service->url_scheme);
    }

    public static function find_by_contributor_id_and_category($contributor_id, $category = 'social')
    {
        $contributor_id = (int) $contributor_id;
        $category = $category == 'social' ? 'social' : 'donation';

        return self::all('WHERE service_id IN (SELECT id FROM '.Service::table_name()." WHERE `category` = '".$category."' ) AND `contributor_id` = ".$contributor_id);
    }
}

ContributorService::property('id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY');
ContributorService::property('contributor_id', 'INT');
ContributorService::property('service_id', 'INT');
ContributorService::property('value', 'TEXT');
ContributorService::property('title', 'TEXT');
ContributorService::property('position', 'FLOAT');
