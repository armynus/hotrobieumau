<!-- Custom fonts for this template-->
<link href="{{asset('vendor/fontawesome-free/css/all.min.css')}}" rel="stylesheet" type="text/css">
<!-- Use the system font fallback in sb-admin-2 for offline LAN access. -->

<!-- Custom styles for this template-->
<link href="{{asset('css/sb-admin-2.min.css')}}" rel="stylesheet">
<link href="{{asset('css/style.css')}}" rel="stylesheet">
<!-- Custom styles for this page -->
<link rel="shortcut icon" type="image/png" href="{{asset('hp-logo.png')}}"/>
<link href="{{asset('vendor/datatables/dataTables.bootstrap4.min.css')}}" rel="stylesheet" >
<link href="{{ asset('css/datatables-enhancements.css') }}?v={{ filemtime(public_path('css/datatables-enhancements.css')) }}" rel="stylesheet">

<meta name="csrf-token" content="{{ csrf_token() }}">
