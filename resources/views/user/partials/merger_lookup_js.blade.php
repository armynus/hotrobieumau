<script>
$(document).ready(function () {

    function resetLowerLevels(level) {
        if (level === 'province') {
            // chỉ clear district và ward, nhưng không disable district nếu ta đang select
            $('#search_pre_district').val('').prop('disabled', true).removeData('selected-id');
            $('#search_pre_ward').val('').prop('disabled', true).removeData('selected-id');
            $('#preResultProvince').text('');
            $('#preResultProvinceOld').text('');
            $('#preResultWard').text('');
            $('#preResultWardOld').text('');
        }
        if (level === 'district') {
            $('#search_pre_ward').val('').prop('disabled', true).removeData('selected-id');
            $('#preResultWard').text('');
            $('#preResultWardOld').text('');
        }
    }

    function enableForceAutocomplete($input) {
        $input.on('focus', function () {
            // Trigger search even with empty value
            $(this).autocomplete('search', this.value || '');
        });
    }

    // --- TỈNH ---
    $('#search_pre_province').autocomplete({
        source(request, response) {
            $.ajax({
                url: '{{ route("old_provinces.search") }}',
                data: { q: request.term },
                success(data) {
                    response($.map(data, item => ({
                        label: item.name, value: item.name,
                        id: item.id, old_names: item.old_names
                    })));
                }
            });
        },
        minLength: 0,
        select(event, ui) {
            // 1) Clear ward (chỉ cấp dưới cấp district)
            resetLowerLevels('district');

            // 2) Set selected-id & hiển thị province
            $(this).data('selected-id', ui.item.id);

            $.ajax({
                url: '{{ route("old_provinces.detail") }}',
                data: { id: ui.item.id },
                success(res) {
                    const list = res.old_list;            // ex: ['Phường 1','Phường 3',...]
                    const selected = ui.item.label;       // ex: 'Phường 1'

                    // 2) Bọc <strong> cho phần trùng
                    const highlighted = list.map(name =>
                        name === selected
                            ? `<strong>${name}</strong>`
                            : name
                    );

                    // 3) Gán HTML xuống thẻ kết quả
                    $('#preResultProvince').text(res.new_name);
                    $('#preResultProvinceOld').html(highlighted.join(', '));
                }
            });

            $('#search_pre_district')
            .prop('disabled', false)
            .val('')
            .autocomplete('search', '');  // optional: show dropdown ngay
        },
        change: function(event, ui) {
            if (!ui.item) resetLowerLevels('province');
        }
    });
    enableForceAutocomplete($('#search_pre_province'));

    // --- HUYỆN ---
    $('#search_pre_district').autocomplete({
        source(request, response) {
            const provinceId = $('#search_pre_province').data('selected-id');
            if (!provinceId) return response([]);
            $.ajax({
                url: '{{ route("old_districts.search") }}',
                dataType: 'json',
                data: { province_id: provinceId, q: request.term },
                success(data) {
                    response($.map(data, item => ({
                        label: item.name,
                        value: item.name,
                        id: item.id
                    })));
                }
            });
        },
        minLength: 0,
        select(event, ui) {
            // 1) Clear chỉ phần Ward
            resetLowerLevels('district');
            // 2) Đặt selected-id cho District
            $(this).data('selected-id', ui.item.id);
            // 3) Hiển thị hoặc xử lý nếu cần
            // $('#preResultDistrict').text(ui.item.label);
            // 4) Enable Ward input
            $('#search_pre_ward')
                .prop('disabled', false)
                .val('')
                .autocomplete('search', ''); // optional: show suggestions ngay
        },
        change(event, ui) {
            if (!ui.item) {
                // nếu user gõ tay hoặc clear thì reset ward
                resetLowerLevels('district');
            }
        }
    });
    enableForceAutocomplete($('#search_pre_district'));

    // --- XÃ ---
    $('#search_pre_ward').autocomplete({
        source(request, response) {
            const districtId = $('#search_pre_district').data('selected-id');
            if (!districtId) return response([]);
            $.ajax({
                url: '{{ route("old_wards.search") }}',
                dataType: 'json',
                data: { district_id: districtId, q: request.term },
                success(data) {
                    response($.map(data, item => ({
                        label: item.name,
                        value: item.name,
                        id: item.id,
                        old_names: item.old_names || []
                    })));
                }
            });
        },
        minLength: 0,
        select(event, ui) {
            // Gọi API chi tiết mapping để lấy tên mới + danh sách cũ
            $.ajax({
                url: '{{ route("old_wards.detail") }}',
                data: { id: ui.item.id },
                success(res) {
                    const list = res.old_list;            // ex: ['Phường 1','Phường 3',...]
                    const selected = ui.item.label;       // ex: 'Phường 1'

                    // 2) Bọc <strong> cho phần trùng
                    const highlighted = list.map(name =>
                        name === selected
                            ? `<strong>${name}</strong>`
                            : name
                    );

                    // 3) Gán HTML xuống thẻ kết quả
                    $('#preResultWard').text(res.new_name);
                    $('#preResultWardOld').html(highlighted.join(', '));
                }
            });
        },
        change(event, ui) {
            if (!ui.item) {
                $('#preResultWard').text('');
                $('#preResultWardOld').text('');

            }
        }
    })
    // Khi focus (click) thì phóng luôn dropdown
    .focus(function() {
        $(this).autocomplete('search', $(this).val() || '');
    });
    enableForceAutocomplete($('#search_pre_ward'));
});

$(document).ready(function () {
    $('#search_post_ward').autocomplete({
        source(request, response) {
            const provinceId = $('#search_post_province').data('selected-id');
            if (!provinceId) return response([]);
            $.ajax({
                url: '{{ route("new_wards.search") }}',
                dataType: 'json',
                data: { province_id: provinceId, q: request.term },
                success(data) {
                    response($.map(data, item => ({
                        label: item.name,
                        value: item.name,
                        id: item.id,
                        old_names: item.old_names || []
                    })));
                }
            });
        },
        minLength: 0,
        select(event, ui) {
            // Gọi API chi tiết mapping để lấy tên mới + danh sách cũ
            $.ajax({
                url: '{{ route("new_wards.detail") }}',
                data: { id: ui.item.id },
                success(res) {
                    const list = res.old_list;            // ex: ['Phường 1','Phường 3',...]
                    const selected = ui.item.label;       // ex: 'Phường 1'

                    // 2) Bọc <strong> cho phần trùng
                    const highlighted = list.map(name =>
                        name === selected
                            ? `<strong>${name}</strong>`
                            : name
                    );

                    // 3) Gán HTML xuống thẻ kết quả
                    $('#postResultWard').text(res.new_name);
                    $('#postResultWardOld').html(highlighted.join(', '));
                }
            });
        },
        change(event, ui) {
            if (!ui.item) {
                $('#postResultWard').text('');
                $('#postResultWardOld').text('');
            }
        }
    }).focus(function() {
        // khi focus vào input thì phóng luôn dropdown
        $(this).autocomplete('search', $(this).val() || '');
    });
});



</script>