@include('shared.sweetalert')
<span class="admin-org-config-data" hidden data-config="{{ json_encode([
    "routes" => [
        "branchStore" => route("admin_branches_store"),
        "branchEdit" => route("admin_branches_edit"),
        "branchUpdate" => route("admin_branches_update"),
        "branchLock" => route("admin_branches_lock")
    ]
]) }}"></span>
<script src="{{ asset('js/admin/org-management.js') }}?v={{ filemtime(public_path('js/admin/org-management.js')) }}"></script>
