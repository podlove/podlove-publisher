<?php
namespace Podlove\Settings;
use \Podlove\Model;

class Support {

	static $pagehook;
	
	public function __construct( $handle ) {
		
		Support::$pagehook = add_submenu_page(
			/* $parent_slug*/ $handle,
			/* $page_title */ 'Support',
			/* $menu_title */ 'Support',
			/* $capability */ 'administrator',
			/* $menu_slug  */ 'podlove_Support_settings_handle',
			/* $function   */ array( $this, 'page' )
		);
	}

	public function page() {
		?>
		<div class="wrap">
			<?php screen_icon( 'podlove-podcast' ); ?>
			<h2><?php echo __( 'Support', 'podlove' ); ?></h2>

			<p>
				If you are using a caching plugin, please clear the cache.
				When you report a bug, please append the following system report to help us trace the root cause:
			</p>

			<p>
				<?php 
				$r = new SystemReport;
				echo $r->render();
				?>
			</p>

			<!--
			- check for caching constants to determine popular caching plugins
			- modules: mod_rewrite
			-->

			<p>
				<a href="">Report a Bug at GitHub</a>
			</p>

		</div>	
		<?php
	}

}

class SystemReport {

	private $fields = array();
	private $notices = array();
	private $errors = array();

	public function __construct() {
		
		$this->fields = array(
			'site'        => array( 'title' => 'Website', 'value' => function() { return get_site_url(); } ),
			'php_version' => array( 'title' => 'PHP Version', 'value' => function() { return phpversion(); } ),
			'wp_version'  => array( 'title' => 'WordPress Version', 'value' => function() { return get_bloginfo('version'); } ),
			'curl'        => array( 'title' => 'curl Version', 'value' => function() {
				$module_loaded = in_array( 'curl', get_loaded_extensions() );
				$function_disabled = stripos( ini_get( 'disable_functions' ), 'curl_exec' ) !== false;
				$out = '';

				if ( $module_loaded ) {
					$curl = curl_version();
					$out .= $curl['version'];
				} else {
					$out .= 'EXTENSION MISSING';
					$this->errors[] = 'curl extension is not loaded';
				}

				if ( $function_disabled ) {
					$out = ' | curl_exec is disabled';
					$this->errors[] = 'curl_exec is disabled';
				}

				return $out;
			} ),
			'allow_url_fopen' => array( 'value' => function() { return ini_get( 'allow_url_fopen' ); } ),
			'max_execution_time' => array( 'value' => function() { return ini_get( 'max_execution_time' ); } ),
			'upload_max_filesize' => array( 'value' => function() { return ini_get( 'upload_max_filesize' ); } ),
			'memory_limit' => array( 'value' => function() { return ini_get( 'memory_limit' ); } ),
			'disable_classes' => array( 'value' => function() { return ini_get( 'disable_classes' ); } ),
			'disable_functions' => array( 'value' => function() { return ini_get( 'disable_functions' ); } )
		);
	}

	public function render() {

		$rfill = function ( $string, $length, $fillchar = ' ' ) {
			while ( strlen( $string ) < $length ) {
				$string .= $fillchar;
			}
			return $string;
		};

		$fill_length = 1 + max( array_map( function($k) { return strlen($k); }, array_keys( $this->fields ) ) );

		$out = '';

		foreach ( $this->fields as $field_key => $field ) {
			$title = isset( $field['title'] ) ? $field['title'] : $field_key;
			$out .= $rfill( $title, $fill_length ) . call_user_func( $field['value'] ) . "\n";
		}

		$out .= "\n";

		if ( count( $this->errors ) ) {
			$out .= count( $this->errors ) . " CRITICAL ERRORS: \n";
			foreach ( $this->errors as $error ) {
				$out .= "$error\n";
			}
		} else {
			$out .= "0 errors\n";
		}

		if ( count( $this->notices ) ) {
			$out .= count( $this->notices ) . " notices (no dealbreaker, but should be fixed if possible): \n";
			foreach ( $this->notices as $error ) {
				$out .= "$error\n";
			}
		} else {
			$out .= "0 notices\n";
		}

		return '<pre>' . $out . '</pre>';
	}

}