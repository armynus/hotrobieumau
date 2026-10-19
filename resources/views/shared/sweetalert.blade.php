@once
@push('styles')
<link rel="stylesheet" type="text/css" href="{{ asset('css/sweetalert.css') }}">
@endpush
@push('scripts')
<script src="{{ asset('js/sweetalert.min.js') }}?v={{ filemtime(public_path('js/sweetalert.min.js')) }}"></script>
@endpush
@endonce
