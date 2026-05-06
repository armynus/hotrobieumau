<?php

namespace App\View\Components;

use Illuminate\View\Component;
use App\Models\FormType;

class SidebarFormTypes extends Component
{
    public $formTypes;
    public $isMenuOpen;
    public function __construct()
    {

        $this->formTypes = cache()->remember('form_types', 30, function () {
            return FormType::all();
        });
        $this->isMenuOpen = request()->routeIs('support_forms.index', 'support_forms.show');
        
    }

    public function render()
    {
        return view('components.sidebar-form-types');
    }
}
