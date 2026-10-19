@include('shared.sweetalert')
<span class="admin-form-type-config-data" hidden data-config="{{ json_encode([
    "routes" => [
        "formTypeUpdate" => route("formtype.update"),
        "formTypeEdit" => route("formtype.edit"),
        "formTypeDelete" => route("formtype.delete"),
        "formTypeStore" => route("formtype.store")
    ]
]) }}"></span>
<script src="{{ asset('js/admin/form-type-manager.js') }}?v={{ filemtime(public_path('js/admin/form-type-manager.js')) }}"></script>