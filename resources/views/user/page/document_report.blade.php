@extends('user.layouts.app')
@section('title', 'Báo cáo Văn bản')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Báo cáo Văn bản</h1>
            <div class="small text-muted">Thống kê theo Ngày văn bản và tình trạng đọc</div>
        </div>
        <form method="GET" action="{{ route('document_reports') }}" class="form-inline mt-2 mt-sm-0">
            <label class="mr-2 font-weight-bold" for="reportYear">Năm</label>
            <select class="form-control" id="reportYear" name="year" onchange="this.form.submit()">
                @foreach($years as $year)
                <option value="{{ $year }}" @selected($year == $selectedYear)>{{ $year }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Tổng văn bản hệ thống</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($totalSystem) }}</div><div class="small text-muted mt-1">Đến: {{ number_format($incomingTotal) }} · Đi: {{ number_format($outgoingTotal) }} · Quyết định: {{ number_format($decisionTotal) }} · Chưa phân loại: {{ number_format($unclassifiedTotal) }}</div></div><div class="col-auto"><i class="fas fa-folder-open fa-2x text-gray-300"></i></div></div></div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-info text-uppercase mb-1">Văn bản bạn được xem</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($visibleTotal) }}</div></div><div class="col-auto"><i class="fas fa-eye fa-2x text-gray-300"></i></div></div></div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-success text-uppercase mb-1">Đã đọc</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($readTotal) }}</div><div class="small text-muted mt-1">Không tính kho văn bản cũ</div></div><div class="col-auto"><i class="fas fa-check-circle fa-2x text-gray-300"></i></div></div></div></div>
        </div>
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2"><div class="card-body"><div class="row no-gutters align-items-center"><div class="col mr-2"><div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Chưa đọc</div><div class="h5 mb-0 font-weight-bold text-gray-800">{{ number_format($unreadTotal) }}</div><div class="small text-muted mt-1">Không tính kho văn bản cũ</div></div><div class="col-auto"><i class="fas fa-envelope fa-2x text-gray-300"></i></div></div></div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-8 col-lg-7">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex align-items-center justify-content-between"><h6 class="m-0 font-weight-bold text-primary">Văn bản theo Ngày văn bản năm {{ $selectedYear }}</h6><span class="badge badge-primary">{{ number_format($yearTotal) }} văn bản</span></div>
                <div class="card-body">
                    <div class="chart-area"><canvas id="monthlyDocumentsChart"></canvas></div>
                    @if($missingIssuedDateTotal > 0)
                    <div class="small text-muted mt-3">
                        <i class="fas fa-info-circle mr-1"></i>{{ number_format($missingIssuedDateTotal) }} văn bản chưa có Ngày văn bản nên chưa được tính vào biểu đồ theo năm.
                    </div>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-lg-5">
            <div class="card shadow mb-4">
                <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Trạng thái đọc của bạn</h6></div>
                <div class="card-body"><div class="chart-pie pt-4 pb-2"><canvas id="readStatusChart"></canvas></div><div class="mt-4 text-center small"><span class="mr-2"><i class="fas fa-circle text-success"></i> Đã đọc</span><span><i class="fas fa-circle text-warning"></i> Chưa đọc</span></div></div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('vendor/jquery-easing/jquery.easing.min.js') }}"></script>
<script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="{{ asset('js/sb-admin-2.min.js') }}"></script>
<script src="{{ asset('vendor/chart.js/Chart.min.js') }}"></script>
<script>
Chart.defaults.global.defaultFontFamily = 'Nunito, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
Chart.defaults.global.defaultFontColor = '#858796';

new Chart(document.getElementById('monthlyDocumentsChart'), {
    type: 'line',
    data: {
        labels: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
        datasets: [{
            label: 'Văn bản đến',
            data: @json($monthlyIncomingCounts),
            lineTension: .3,
            backgroundColor: 'rgba(78, 115, 223, .08)',
            borderColor: '#4e73df',
            pointBackgroundColor: '#4e73df',
            pointBorderColor: '#fff',
            pointRadius: 4,
            pointHoverRadius: 6,
            borderWidth: 3
        }, {
            label: 'Văn bản đi',
            data: @json($monthlyOutgoingCounts),
            lineTension: .3,
            backgroundColor: 'rgba(28, 200, 138, .05)',
            borderColor: '#1cc88a',
            pointBackgroundColor: '#1cc88a',
            pointBorderColor: '#fff',
            pointRadius: 4,
            pointHoverRadius: 6,
            borderWidth: 3
        }, {
            label: 'Quyết định',
            data: @json($monthlyDecisionCounts),
            lineTension: .3,
            backgroundColor: 'rgba(246, 194, 62, .05)',
            borderColor: '#f6c23e',
            pointBackgroundColor: '#f6c23e',
            pointRadius: 4,
            borderWidth: 3
        }, {
            label: 'Chưa phân loại',
            data: @json($monthlyUnclassifiedCounts),
            lineTension: .3,
            backgroundColor: 'rgba(133, 135, 150, .05)',
            borderColor: '#858796',
            pointBackgroundColor: '#858796',
            pointBorderColor: '#fff',
            pointRadius: 4,
            pointHoverRadius: 6,
            borderWidth: 3
        }]
    },
    options: {
        maintainAspectRatio: false,
        legend: { display: true, position: 'bottom' },
        scales: {
            xAxes: [{ gridLines: { display: false, drawBorder: false } }],
            yAxes: [{ ticks: { beginAtZero: true, precision: 0, padding: 10 }, gridLines: { color: '#eaecf4', borderDash: [2] } }]
        },
        tooltips: { intersect: false, mode: 'index', callbacks: { label: function(item) { return ' ' + item.yLabel + ' văn bản'; } } }
    }
});

new Chart(document.getElementById('readStatusChart'), {
    type: 'doughnut',
    data: { labels: ['Đã đọc', 'Chưa đọc'], datasets: [{ data: [@json($readTotal), @json($unreadTotal)], backgroundColor: ['#1cc88a', '#f6c23e'], hoverBackgroundColor: ['#17a673', '#dda20a'], borderWidth: 0 }] },
    options: { maintainAspectRatio: false, legend: { display: false }, cutoutPercentage: 72, tooltips: { callbacks: { label: function(item, data) { return ' ' + data.labels[item.index] + ': ' + data.datasets[0].data[item.index]; } } } }
});
</script>
@endpush
