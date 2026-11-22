<?php
require_once __DIR__ . '/../../helpers/Auth.php';
Auth::checkRole(array('admin'));
require_once __DIR__ . '/../partials/header.php';
require_once __DIR__ . '/../partials/navbar.php';
?>

<div class="page-head">
    <div>
        <h2>Thống kê</h2>
        <p class="lead">Tháng để theo dõi vận hành. Quý để xem xu hướng 2 năm. Doanh thu chỉ tính hóa đơn đã thu.</p>
    </div>
    <div class="actions">
        <button class="btn-navy" type="button" data-grain="month">Theo tháng</button>
        <button class="btn-ghost" type="button" data-grain="quarter">Theo quý</button>
    </div>
</div>

<div class="stat-grid">
    <div class="card"><p class="lead">Bác sĩ</p><h2><?php echo (int) $staff['doctors']; ?></h2></div>
    <div class="card"><p class="lead">Y tá</p><h2><?php echo (int) $staff['nurses']; ?></h2></div>
    <div class="card"><p class="lead">Lễ tân</p><h2><?php echo (int) $staff['receptionists']; ?></h2></div>
    <div class="card"><p class="lead">Bệnh nhân</p><h2><?php echo (int) $staff['patients']; ?></h2></div>
</div>

<div class="chart-grid">
    <div class="card"><h3>Doanh thu đã thu</h3><canvas id="chart-revenue"></canvas></div>
    <div class="card"><h3>Bệnh nhân mới</h3><canvas id="chart-patients"></canvas></div>
    <div class="card"><h3>Hồ sơ bệnh án</h3><canvas id="chart-records"></canvas></div>
    <div class="card"><h3>Lượt khám</h3><canvas id="chart-visits"></canvas></div>
</div>

<link rel="preconnect" href="https://cdn.jsdelivr.net">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
var statCharts = {};
function paintStats(grain) {
    jQuery.getJSON('/index.php?action=admin_stats_data&grain=' + grain, function (data) {
        Object.keys(statCharts).forEach(function (key) { statCharts[key].destroy(); });
        function bar(id, label, values, color) {
            statCharts[id] = new Chart(document.getElementById(id), {
                type: id === 'chart-revenue' ? 'line' : 'bar',
                data: { labels: data.labels, datasets: [{ label: label, data: values, borderColor: color, backgroundColor: color }] },
                options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
            });
        }
        bar('chart-revenue', 'Đã thu', data.revenue, '#0a3a6b');
        bar('chart-patients', 'Bệnh nhân mới', data.patients, '#c24e12');
        bar('chart-records', 'Bệnh án', data.records, '#1e5b32');
        bar('chart-visits', 'Lượt khám', data.visits, '#5c7ea3');
    });
}
document.querySelectorAll('[data-grain]').forEach(function (btn) {
    btn.addEventListener('click', function () {
        document.querySelectorAll('[data-grain]').forEach(function (other) {
            other.className = other === btn ? 'btn-navy' : 'btn-ghost';
        });
        paintStats(btn.getAttribute('data-grain'));
    });
});
paintStats('month');
</script>

<?php require_once __DIR__ . '/../partials/footer.php'; ?>