<?php

namespace App\View\Components;

use Illuminate\View\Component;

class DashboardTile extends Component
{
    public $icon;
    public $class;
    public $title;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($icon, $class, $title)
    {
        $this->icon = $icon;
        $this->class = $class;
        $this->title = $title;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $icon = $this->icon;
        $class = $this->class;
        $title = $this->title;

        return view('components.dashboard-tile', compact('icon', 'class', 'title'));
    }
}
