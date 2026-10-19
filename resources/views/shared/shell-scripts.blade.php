{{-- Tải jQuery trước Bootstrap và các plugin của shell và view con. --}}
<script src="{{ asset('js/ajax/libs/jquery/3.6.0/jquery.min.js') }}?v={{ filemtime(public_path('js/ajax/libs/jquery/3.6.0/jquery.min.js')) }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}?v={{ filemtime(public_path('vendor/bootstrap/js/bootstrap.bundle.min.js')) }}"></script>
<script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}?v={{ filemtime(public_path('vendor/jquery-easing/jquery.easing.min.js')) }}"></script>
<script src="{{ asset('js/sb-admin-2.min.js') }}?v={{ filemtime(public_path('js/sb-admin-2.min.js')) }}"></script>
<script src="{{ asset('js/shared/shell-init.js') }}?v={{ filemtime(public_path('js/shared/shell-init.js')) }}"></script>

