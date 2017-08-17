<?php
namespace Podlove\Modules\TitleMigration;

/**
 * Describes the state of the migration workflow.
 * 
 * - INITIALIZED
 * 	-> show admin message where to find the tool
 * - FINISHED
 * 	-> show admin message to deactivate the module
 * - FINISHED_HIDDEN
 *  -> finished but message hidden
 * 
 */
class State {

	const INITIALIZED        = 'initialized';
	const INITIALIZED_HIDDEN = 'initialized_hidden';
	const FINISHED           = 'finished';
	const FINISHED_HIDDEN    = 'finished_hidden';

	const OPTION = 'podlove_title_migration_state';

	public function is_initialized()
	{
		return $this->get_current_state() == self::INITIALIZED;
	}

	public function is_initialized_hidden()
	{
		return $this->get_current_state() == self::INITIALIZED_HIDDEN;
	}

	public function is_finished()
	{
		return $this->get_current_state() == self::FINISHED;
	}

	public function is_finished_hidden()
	{
		return $this->get_current_state() == self::FINISHED_HIDDEN;
	}

	public function get_current_state()
	{
		return get_option(self::OPTION, self::INITIALIZED);
	}

	/**
	 * Set current state.
	 * 
	 * Only accepts valid states.
	 * 
	 * @param  string $state
	 * @return bool   false if state is invalid, update failed or state was not changed. Otherwise true.
	 */
	public function set_current_state($state)
	{
		if (!in_array($state, $this->states()))
			return false;

		return update_option(self::OPTION, $state);
	}

	public function states()
	{
		return [
			self::INITIALIZED,
			self::INITIALIZED_HIDDEN,
			self::FINISHED,
			self::FINISHED_HIDDEN
		];
	}

}
