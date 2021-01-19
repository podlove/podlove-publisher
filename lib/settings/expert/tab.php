<?php

namespace Podlove\Settings\Expert;

use Podlove\Settings\Settings;

/**
 * Represents one Expert Settings Tab.
 */
class Tab
{
    protected $page_type = 'settings api';

    /**
     * Tab title.
     *
     * @var string
     */
    private $title;

    /**
     * If this is true, use it if no tab is selected.
     *
     * @var bool
     */
    private $is_default;

    public function __construct($title, $is_default = false)
    {
        $this->set_title($title);
        $this->is_default = $is_default;
    }

    public function is_active()
    {
        $is_current_tab = isset($_REQUEST['podlove_tab']) && $this->get_slug() == $_REQUEST['podlove_tab'];

        return $is_current_tab || !isset($_REQUEST['podlove_tab']) && $this->is_default;
    }

    public function get_title()
    {
        return $this->title;
    }

    public function set_title($title)
    {
        $this->title = $title;
    }

    public function get_slug()
    {
        return '';
    }

    public function get_url()
    {
        return sprintf('?page=%s&podlove_tab=%s', filter_var($_REQUEST['page'], FILTER_SANITIZE_STRING), $this->get_slug());
    }

    public function page()
    {
        if ($this->page_type == 'settings api') {
            $screen_base = get_current_screen()->base; ?>
			<form method="post" action="options.php">
				<?php if (isset($_REQUEST['podlove_tab'])) { ?>
					<input type="hidden" name="podlove_tab" value="<?php echo esc_attr($_REQUEST['podlove_tab']); ?>" />
				<?php } ?>

				<?php settings_fields($screen_base); ?>
				<?php do_settings_sections($screen_base); ?>
				
				<?php submit_button(__('Save Changes'), 'button-primary', 'submit', true); ?>
			</form>
			<?php
        } else {
            do_action('podlove_expert_settings_page');
        }
    }

    public function init()
    {
        throw Exception('You need to subclass Tab and implement Tab::init');
    }
}
