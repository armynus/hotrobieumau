<span class="admin-form-fields-config-data" hidden data-config="{{ json_encode([
    "itGroupLabels" => $it_content_groups ?? [],
    "routes" => [
        "addItField" => route("admin.it_support.form_fields.store"),
        "editItField" => route("admin.it_support.form_fields.edit"),
        "updateItField" => route("admin.it_support.form_fields.update"),
        "deleteItField" => route("admin.it_support.form_fields.destroy")
    ]
]) }}"></span>
