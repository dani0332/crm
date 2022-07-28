<?php

namespace App\View\Components;

use Illuminate\View\Component;

class Action extends Component
{
    public $editRoute;
    public $deleteRoute;
    public $viewRoute;
    public $permission;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($editRoute, $deleteRoute, $viewRoute, $permission)
    {
        $this->editRoute = $editRoute;
        $this->deleteRoute = $deleteRoute;
        $this->viewRoute = $viewRoute;
        $this->permission = $permission;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $editRoute = $this->editRoute;
        $deleteRoute = $this->deleteRoute;
        $viewRoute = $this->viewRoute;
        $permission = $this->permission;

        return view('components.action', compact('editRoute', 'deleteRoute', 'viewRoute', 'permission'));
    }
}
