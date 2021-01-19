<?php

namespace Podlove\Settings\Expert;

/**
 * Manages Expert Settings Tabs.
 */
class Tabs
{
    /**
     * Tab Bar Title.
     *
     * @var string
     */
    private $title = '';

    /**
     * List of tab objects.
     *
     * @var array
     */
    private $tabs = [];

    public function __construct($title)
    {
        $this->title = $title;
    }

    public function addTab($tab)
    {
        $this->tabs[] = $tab;
    }

    public function getTabsHTML()
    {
        $html = '<h2 class="nav-tab-wrapper">';
        $html .= '<span class="nav-tab-title">'.$this->title."</span>\n";
        foreach ($this->tabs as $tab) {
            $html .= sprintf(
                '<a href="%s" class="nav-tab%s">%s</a>',
                $tab->get_url(),
                $tab->is_active() ? ' nav-tab-active' : '',
                $tab->get_title()
            );
        }
        $html .= '</h2>';

        return $html;
    }

    public function getCurrentTabPage()
    {
        if (is_object($this->getCurrentTab())) {
            return $this->getCurrentTab()->page();
        }
    }

    public function initCurrentTab()
    {
        if (is_object($this->getCurrentTab())) {
            return $this->getCurrentTab()->init();
        }
    }

    public function initAllTabs()
    {
        foreach ($this->tabs as $tab) {
            $tab->init();
        }
    }

    public function getTabs()
    {
        return $this->tabs;
    }

    private function getCurrentTab()
    {
        foreach ($this->tabs as $tab) {
            if ($tab->is_active()) {
                return $tab;
            }
        }

        return $this->tabs[0];
    }
}
