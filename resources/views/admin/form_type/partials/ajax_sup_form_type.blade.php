@include('shared.sweetalert')
<span class="admin-form-type-config-data" hidden data-config="{{ json_encode([
    "routes" => [
        "supFormTypeDelete" => route("supformtype.delete"),
        "supFormTypeEdit" => route("supformtype.edit"),
        "supFormTypeUpdate" => route("supformtype.update"),
        "supFormTypeStore" => route("supformtype.store")
    ]
]) }}"></span>
<script src="{{ asset('js/admin/form-type-manager.js') }}?v={{ filemtime(public_path('js/admin/form-type-manager.js')) }}"></script>