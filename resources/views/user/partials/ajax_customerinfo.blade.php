@include('shared.sweetalert')

@php
$customerInfoConfig = [
    'routes' => [
        'data' => route('customers.data'),
        'add' => route('add_customer'),
        'update' => route('update_customer'),
        'detail' => route('detail_customer'),
    ],
    'timezone' => config('app.timezone'),
];
@endphp
<span id="customer-info-config" hidden data-config="{{ json_encode($customerInfoConfig) }}"></span>
<script src="{{ asset('js/user/customer-info.js') }}?v={{ filemtime(public_path('js/user/customer-info.js')) }}"></script>
