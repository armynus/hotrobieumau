<?php

namespace App\View\Components;

use Illuminate\View\Component;

class UploadModal extends Component
{
    public string $actionRoute;
    public string $modalId;
    public string $modalLabel;
    public string $inputName;
    public string $buttonText;

    /**
     * Create a new component instance.
     */
    public function __construct(
        string $actionRoute, 
        string $modalId, 
        string $modalLabel = "Upload File", 
        string $inputName = "file", 
        string $buttonText = "Upload"
    ) {
        $this->actionRoute = $actionRoute;
        $this->modalId = $modalId;
        $this->modalLabel = $modalLabel;
        $this->inputName = $inputName;
        $this->buttonText = $buttonText;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render()
    {
        return view('components.upload-modal');
    }
}
