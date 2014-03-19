<?php
namespace Podlove\Modules\Contributors\Settings;

use Podlove\Model;
use \Podlove\Modules\Contributors\Model\Contributor;
use \Podlove\Modules\Contributors\Model\ContributorRole;
use \Podlove\Modules\Contributors\Model\ContributorGroup;
use \Podlove\Modules\Contributors\Model\DefaultContribution;

class ContributorDefaults {

	static $pagehook;
	
	public function __construct( $handle ) {
		add_action( 'admin_init', array( $this, 'process_form' ) );
	}

	public function process_form() {
		if ( ! isset( $_REQUEST['podlove_contributor_defaults'] ) )
			return;

		$action = ( isset( $_REQUEST['action'] ) ) ? $_REQUEST['action'] : NULL;

		if ( $action === 'save' ) {
			$this->save_setting();
		}				
	}

	public function save_setting()
	{

		$contributor_appearances = $_REQUEST['podlove_contributor_defaults']['contributor'];

		foreach (DefaultContribution::all() as $contribution) {
			$contribution->delete();
		}

		$position = 0;
		foreach ($contributor_appearances as $contributor_appearance) {
			foreach ($contributor_appearance as $contributor_id => $contributor) {
				$c = new DefaultContribution;

				if ($role = ContributorRole::find_one_by_slug( $contributor['role'] )) {
					$c->role_id = $role->id;
				}

				if ($group = ContributorGroup::find_one_by_slug( $contributor['group'] )) {
					$c->group_id = $group->id;
				}

				$c->contributor_id = $contributor_id;

				if (isset($contributor['comment'])) {
					$c->comment = $contributor['comment'];
				}

				$c->position = $position++;
				$c->save();
			}
		}
	}

	public function page() {
		?>
		<div class="wrap">
			<p>
				Default Contributors will be automatically added to the list of contributors for new episodes.
			</p>
			<form method="post" action="admin.php?page=podlove_contributor_settings&action=save" id="contributor_default_form">
			<?php
				$this->default_contrib_form();
			?>
			<p>
				<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes"  />
			</p>
			</form>
		</div>	
		<?php
	}
	
	private function default_contrib_form() {
		$contributions = DefaultContribution::all();

		// map indices to IDs
		$map = array();
		foreach ($contributions as $c) {
			$map[$c->id] = $c;
		}

		\Podlove\Modules\Contributors\Contributors::contributors_form_table($map, 'podlove_contributor_defaults[contributor]');
	}

}
