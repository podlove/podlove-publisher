<?php
namespace Podlove\Modules\Flattr;

/**
 * Inject Flattr into Contributors module.
 */
class ContributorExtension {

	public static function init() {
		add_filter('podlove_contributor_list_table_column_default', [__CLASS__, 'contributor_list_table_column'], 10, 3);
		add_filter('podlove_contributor_list_table_columns', [__CLASS__, 'contributor_list_table_columns']);
		add_filter('podlove_contributor_list_table_search_db_columns', [__CLASS__, 'contributor_list_table_search_db_columns']);
		add_action('admin_head-podcast_page_podlove_contributors_settings_handle', ['\Podlove\Modules\Flattr\Flattr', 'insert_script']);
		add_filter('podlove_contributors_general_fields', [__CLASS__, 'add_flattr_field_to_contributor_settings']);
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
		return \Podlove\PHP\array_insert($columns, 'episodes', ['flattr' => __('Flattr', 'podlove')]);
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

	public static function add_flattr_field_to_contributor_settings($fields) {
		
		$field = [
			'flattr' => [
				'field_type' => 'string',
				'field_options' => [
					'label'       => 'Flattr',
					'description' => __('Flattr username', 'podlove'),
					'html'        => ['class' => 'podlove-contributor-field podlove-check-input']
				]
			]
		];

		return \Podlove\PHP\array_insert($fields, 'slug', $field);
	}
}
