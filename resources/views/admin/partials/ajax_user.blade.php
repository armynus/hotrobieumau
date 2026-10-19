@include('shared.sweetalert')
<span id="admin-user-config" hidden data-config="{{ json_encode(["routes" => ["edit" => route("admin_user_edit"), "lock" => route("admin_user_lock"), "update" => route("admin_user_update"), "store" => route("admin_user_store")]]) }}"></span>
<script src="{{ asset('js/admin/user-actions.js') }}?v={{ filemtime(public_path('js/admin/user-actions.js')) }}"></script>
