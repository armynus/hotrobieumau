<?php

namespace App\View\Components;

use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class BackPageButton extends Component
{
    public string $previous_page;
    public string $text;

    public function __construct(string $previous_page = null, string $text = 'Quay lại') {
        $this->previous_page = $previous_page ?? substr(url()->current(), 0, strrpos(url()->current(), '/'));
        $this->text = $text;
    }

    public function render(): View|Closure|string
    {
        return view('components.back-page-button');
    }
}
