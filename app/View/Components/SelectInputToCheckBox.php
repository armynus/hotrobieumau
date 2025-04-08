<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class SelectInputToCheckBox extends Component
{
    public $name;
    public $options;
    public $selected;
    public $placeholder;
    public $required;

    public function __construct($name, $options = [], $selected = null, $placeholder = '', $required = false)
    {
        $this->name = $name;
        $this->options = $options;
        $this->selected = old($name, $selected);
        $this->placeholder = $placeholder;
        $this->required = $required;
    }
    public function render(): View|Closure|string
    {
        return view('components.select-input-to-check-box');
    }
}
class CheckValueToCheckBox extends Component
{
    public $name;
    public $options;
    public $selected;
    public $required;

    public function __construct($name, $options = [], $selected = [], $required = false)
    {
        $this->name = $name;
        $this->options = $options;
        $this->selected = old($name, $selected);
        $this->required = $required;
    }

    public function render()
    {
        return view('components.check-value-to-check-box');
    }
}
