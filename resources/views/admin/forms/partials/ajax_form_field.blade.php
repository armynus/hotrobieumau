@include('shared.sweetalert')
<span class="admin-form-fields-config-data" hidden data-config="{{ json_encode([
    "groupLabels" => $content_groups ?? [],
    "itGroupLabels" => $it_content_groups ?? [],
    "routes" => [
        "addTransactionField" => route("add_form_field"),
        "editTransactionField" => route("admin_edit_field"),
        "updateTransactionField" => route("admin_update_field"),
        "deleteTransactionField" => route("admin_delete_field")
    ]
]) }}"></span>
<script src="{{ asset('js/admin/form-fields-manager.js') }}?v={{ filemtime(public_path('js/admin/form-fields-manager.js')) }}"></script>
