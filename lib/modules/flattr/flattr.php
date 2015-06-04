<?php
namespace Podlove\Modules\Flattr;

class Flattr extends \Podlove\Modules\Base {

	protected $module_name = 'Flattr';
	protected $module_description = 'Enable support for <a href="https://flattr.com/" target="_blank">Flattr</a>.';
	protected $module_group = 'web publishing';

	public function load() {
		add_action('wp_head', [__CLASS__, 'insert_script']);

		// extend contributor list table
		add_filter('podlove_contributor_list_table_column_default', [__CLASS__, 'contributor_list_table_column'], 10, 3);
		add_filter('podlove_contributor_list_table_columns', [__CLASS__, 'contributor_list_table_columns']);
		add_filter('podlove_contributor_list_table_search_db_columns', [__CLASS__, 'contributor_list_table_search_db_columns']);

		FeedExtension::init();
	}

	public static function insert_script() {
		\Podlove\load_template('lib/modules/flattr/views/flattr_script');
	}

	/**
	 * Provide callback for Contributor_List_Table flattr column.
	 * 
	 * @param  string|null $value 			column value (probably `null`)
	 * @param  Contributor $contributor
	 * @param  string $column_name 			column name (we only hook if it's "flattr")
	 * @return string
	 */
	public static function contributor_list_table_column($value, $contributor, $column_name) {

		if (!is_null($value) || $column_name != 'flattr')
			return $value;

		if (!is_object($contributor) || !strlen($contributor->flattr)) 
			return $value;

		return "<a 
				    target=\"_blank\"
					class=\"FlattrButton\"
					style=\"display:none;\"
		    		title=\"Flattr {$contributor->publicname}\"
		    		rel=\"flattr;uid:{$contributor->flattr};button:compact;popout:0\"
		    		href=\"https://flattr.com/profile/{$contributor->flattr}\">
				    	Flattr {$contributor->publicname}
				</a>
				<br />
				<a href='http://flattr.com/profile/" . $contributor->flattr . "'>" . $contributor->flattr . "</a>";
	}

	/**
	 * Add flattr to Contributor_List_Table columns.
	 * 
	 * @param  array $columns list of list table columns
	 * @return array          
	 */
	public static function contributor_list_table_columns($columns) {
		
		$insert_position = 4;

		$columns = array_slice($columns, 0, $insert_position, true) 
		         + ['flattr' => __('Flattr', 'podlove')]
		         + array_slice($columns, $insert_position, count($columns)-$insert_position, true);

		return $columns;
	}

	/**
	 * Add "flattr" to list of database columns in Contributor_List_Table search
	 * 
	 * @param  array $columns list of database columns to search
	 * @return array
	 */
	public static function contributor_list_table_search_db_columns($columns) {
		$columns[] = 'flattr';
		return $columns;
	}

}
