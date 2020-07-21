<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Submenu extends Component
{
    /**
     * The Panel title.
     *
     * @var string
     */
    public $title;

    /**
     * The Panel menu.
     *
     * @var string
     */
    public $menu;

    /**
     * The Panel device_id.
     *
     * @var string
     */
    public $deviceid;

    /**
     * The Panel current_tab.
     *
     * @var string
     */
    public $currenttab;

    /**
     * The Panel selected.
     *
     * @var string
     */
    public $selected;


    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($title, $menu, $deviceid, $currenttab, $selected)
    {
        $this->title = $title;
        $this->menu = $menu;
        $this->deviceid = $deviceid;
        $this->currenttab = $currenttab;
        $this->selected = $selected;
    }

    /**
     * Determine if the given option is the current selected option.
     *
     * @param  string  $option
     * @return bool
     */
    public function isSelected($url)
    {
        return $url === $this->selected;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\View\View|string
     */
    public function render()
    {
        return view('components.submenu');
    }
}
