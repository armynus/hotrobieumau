<!-- Bao gồm jQuery và jQuery UI (nếu chưa có) -->
<link href="{{ asset('vendor/jquery/jquery-ui.css') }}" rel="stylesheet">
<script src="{{ asset('vendor/jquery/jquery-ui.min.js') }}"></script>

<script>
$("#search_topbar").autocomplete({
    source: function(request, response) {
        $.ajax({
            url: "{{ route('customer.search') }}",
            dataType: "json",
            data: { query: request.term },
            success: function(data) {
                response(data);
            }
        });
    },
    minLength: 2,
    select: function(event, ui) {
        
    }
});

</script>