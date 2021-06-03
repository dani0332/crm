<?php

namespace App\View\Components;

use Illuminate\View\Component;
use DB;
class Auditable extends Component
{

    public $auditableId;
    public $auditableType;



    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($auditableId , $auditableType)
    {
        $this->auditableId = $auditableId;
        $this->auditableType = $auditableType;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
       $audits =  DB::table('audits')
       ->select('audits.*','users.name')
       ->join('users','audits.user_id','users.id')
       ->where('auditable_id',$this->auditableId)
       ->where('auditable_type',$this->auditableType)
       
       ->get();

        return view('components.auditable',compact('audits'));
    }
}
