<style>
    label.error {
        color: red;
        font-size: 14px;
        display: block;
        font-weight: 400;
    }

    .error {
        color: #5a5c69;
        font-size: 16px;
        line-height: 1;
        position: relative;
        width: 100%;
    }

    .alert {
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 10px;
    }

    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }

    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
</style>

@if (Session::has('message'))
    <div class="alert alert-success" role="alert">
        {{ Session::get('message') }}
        @php Session::put('message', null); @endphp
    </div>
@endif

@if (Session::has('error'))
    <div class="alert alert-danger" role="alert">
        {{ Session::get('error') }}
        @php Session::put('error', null); @endphp
    </div>
@endif