<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\Contracts\View\View;
use App\Models\FormType;

class FormTypeForm extends Component
{
    public string $modalId;
    public string $modalLabelId;
    public string $title;
    public array $fields;
    public array $selectFields;
    public array $selectOptions;
    public array $placeholders;
    public array $dateFields;
    public array $disabledFields;
    public array $values;
    public string $closeText;
    public string $submitText;
    public string $submitId;
    public string $formId;

    /**
     * Create a new component instance.
     *
     * @param string $modalId
     * @param string $modalLabelId
     * @param string $title
     * @param array $fields
     * @param array $selectFields
     * @param array $selectOptions
     * @param array $placeholders
     * @param array $dateFields
     * @param array $disabledFields
     * @param array $values
     * @param string $closeText
     * @param string $submitText
     * @param string $submitId
     * @param string $formId
     */
    public function __construct(
        string $modalId,
        string $modalLabelId,
        string $title,
        array $fields = [],
        array $selectFields = [],
        array $selectOptions = [],
        array $placeholders = [],
        array $dateFields = [],
        array $disabledFields = [],
        array $values = [],
        string $closeText = 'Đóng',
        string $submitText = 'Lưu',
        string $submitId = '',
        string $formId = ''
    ) {
        $this->modalId = $modalId;
        $this->modalLabelId = $modalLabelId;
        $this->title = $title;
        $this->fields = $fields;
        $this->selectFields = $selectFields;
        $this->selectOptions = $selectOptions;
        $this->placeholders = $placeholders;
        $this->dateFields = $dateFields;
        $this->disabledFields = $disabledFields;
        $this->values = $values;
        $this->closeText = $closeText;
        $this->submitText = $submitText;
        $this->submitId = $submitId;
        $this->formId = $formId;

        // Auto-configure select for form_type_id if present
        foreach (['form_type_id', 'edit_form_type_id'] as $field) {
            if (array_key_exists($field, $fields)) {
                if (!in_array($field, $this->selectFields)) {
                    $this->selectFields[] = $field;
                }
                if (empty($this->selectOptions[$field])) {
                    $this->selectOptions[$field] = FormType::pluck('type_name', 'id')->toArray();
                }
            }
        }
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.form-type-form');
    }
}
