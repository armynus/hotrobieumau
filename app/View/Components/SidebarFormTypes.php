<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\FormType;

class SidebarFormTypes extends Component
{
    public $formTypes;

    public function __construct()
    {

        $this->formTypes = cache()->remember('form_types', 3600, function () {
            return FormType::all();
        });
        
    }

    public function render()
    {
        return view('components.sidebar-form-types');
    }
}
