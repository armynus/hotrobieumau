(function (window) {
    'use strict';

    function initDocumentReportCharts() {
        if (typeof Chart === 'undefined') return;

        var cfgEl = document.getElementById('document-report-config');
        if (cfgEl && cfgEl.dataset && cfgEl.dataset.config) {
            try { window.DocumentReportConfig = JSON.parse(cfgEl.dataset.config); } catch (e) {}
        }
        var cfg = window.DocumentReportConfig || {};

        Chart.defaults.global.defaultFontFamily = 'Nunito, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif';
        Chart.defaults.global.defaultFontColor = '#858796';

        var monthlyCanvas = document.getElementById('monthlyDocumentsChart');
        if (monthlyCanvas) {
            new Chart(monthlyCanvas, {
                type: 'line',
                data: {
                    labels: ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'],
                    datasets: [{
                        label: 'Văn bản đến',
                        data: cfg.monthlyIncoming || [],
                        lineTension: 0.3,
                        backgroundColor: 'rgba(78, 115, 223, .08)',
                        borderColor: '#4e73df',
                        pointBackgroundColor: '#4e73df',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        borderWidth: 3
                    }, {
                        label: 'Văn bản đi',
                        data: cfg.monthlyOutgoing || [],
                        lineTension: 0.3,
                        backgroundColor: 'rgba(28, 200, 138, .05)',
                        borderColor: '#1cc88a',
                        pointBackgroundColor: '#1cc88a',
                        pointBorderColor: '#fff',
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        borderWidth: 3
                    }, {
                        label: 'Quyết định',
                        data: cfg.monthlyDecision || [],
                        lineTension: 0.3,
                        backgroundColor: 'rgba(246, 194, 62, .05)',
                        borderColor: '#f6c23e',
                        pointBackgroundColor: '#f6c23e',
                        pointRadius: 4,
                        borderWidth: 3
                    }, {
                        label: 'Chưa phân loại',
                        data: cfg.monthlyUnclassified || [],
                        lineTension: 0.3,
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
                    tooltips: {
                        intersect: false,
                        mode: 'index',
                        callbacks: {
                            label: function (item) {
                                return ' ' + item.yLabel + ' văn bản';
                            }
                        }
                    }
                }
            });
        }

        var readStatusCanvas = document.getElementById('readStatusChart');
        if (readStatusCanvas && cfg.hasReadStatus) {
            new Chart(readStatusCanvas, {
                type: 'doughnut',
                data: {
                    labels: ['Đã đọc', 'Chưa đọc'],
                    datasets: [{
                        data: [cfg.readTotal || 0, cfg.unreadTotal || 0],
                        backgroundColor: ['#1cc88a', '#f6c23e'],
                        hoverBackgroundColor: ['#17a673', '#dda20a'],
                        borderWidth: 0
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    legend: { display: false },
                    cutoutPercentage: 72,
                    tooltips: {
                        callbacks: {
                            label: function (item, data) {
                                return ' ' + data.labels[item.index] + ': ' + data.datasets[0].data[item.index];
                            }
                        }
                    }
                }
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDocumentReportCharts);
    } else {
        initDocumentReportCharts();
    }

})(window);
