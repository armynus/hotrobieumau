@include('shared.sweetalert')

@php
$accountInfoConfig = [
    'routes' => [
        'data' => route('accounts.data'),
        'add' => route('add_account'),
        'update' => route('update_account'),
        'detail' => route('detail_account'),
    ],
    'timezone' => config('app.timezone'),
];
@endphp
<span id="account-info-config" hidden data-config="{{ json_encode($accountInfoConfig) }}"></span>
<script src="{{ asset('js/user/account-info.js') }}?v={{ filemtime(public_path('js/user/account-info.js')) }}"></script>
