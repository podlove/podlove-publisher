<?php
namespace Podlove\Modules\ImportExport\Import;

trait PodcastImportJobTableTrait
{

    /**
     * Fully qualified name of the model class
     *
     * Example: '\Podlove\Model\EpisodeAsset'
     *
     * @return string
     */
    abstract protected static function get_import_table_class();

    /**
     * Name of the group in export file
     *
     * Example: 'asset'
     *
     * @return string
     */
    abstract protected static function get_import_item_name();

    protected function get_xml_group()
    {
        return $this->xml->xpath('//wpe:' . self::get_import_item_name());
    }

    public function setup()
    {
        $this->setupXml();
        $this->hooks['init'] = [$this, 'init_job'];
    }

    public function init_job()
    {
        $table = self::get_import_table_class();
        $table::delete_all();

        $this->job->state = 0;
    }

    public function get_total_steps()
    {
        return count($this->get_xml_group());
    }

    protected function do_step()
    {
        $group = $this->get_xml_group();
        $item  = $group[$this->job->state];

        $table    = self::get_import_table_class();
        $new_item = new $table;
        foreach ($item->children('wpe', true) as $attribute) {
            $new_item->{$attribute->getName()} = (string) $attribute;
        }
        $new_item->save();

        $this->job->state++;

        return 1;
    }

}
