<?php
/**
 * reports.php
 * Financial reports dashboard with metric cards (revenue, occupancy, pending rent),
 * a Chart.js bar chart for monthly revenue, and print/PDF export via html2canvas + jsPDF.
 */

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';
requireAuth();
requireRole('admin');

// Fetch raw data for aggregation
$properties = db()->query("SELECT * FROM properties")->fetchAll();
$contracts = db()->query("SELECT * FROM contracts")->fetchAll();
$payments = db()->query("SELECT * FROM payments ORDER BY date")->fetchAll();

// Aggregate completed payments by month for the revenue chart
$revenue_by_month = [];
foreach ($payments as $p) {
    if ($p['status'] !== 'completed') continue;
    $month = date('M Y', strtotime($p['date']));
    if (!isset($revenue_by_month[$month])) $revenue_by_month[$month] = 0;
    $revenue_by_month[$month] += (float)$p['amount'];
}

// Encode chart data for JavaScript
$chart_labels = json_encode(array_keys($revenue_by_month));
$chart_values = json_encode(array_values($revenue_by_month));

// Calculate total expected monthly rent from rented properties
$total_expected_rent = 0;
foreach ($properties as $p) {
    if ($p['status'] === 'rented') $total_expected_rent += (float)$p['rent_amount'];
}

// Sum all completed payments received
$total_received = 0;
foreach ($payments as $p) {
    if ($p['status'] === 'completed') $total_received += (float)$p['amount'];
}

// Count active and terminated contracts
$active_tenants = 0;
$terminated = 0;
foreach ($contracts as $c) {
    if ($c['status'] === 'active') $active_tenants++;
    if ($c['status'] === 'terminated') $terminated++;
}

// Calculate total amount and count of pending payments
$pending_payments_amount = 0;
$pending_payments_count = 0;
foreach ($payments as $p) {
    if ($p['status'] === 'pending') {
        $pending_payments_amount += (float)$p['amount'];
        $pending_payments_count++;
    }
}

// Compute occupancy rate from rented vs total properties
$rented = 0;
foreach ($properties as $p) { if ($p['status'] === 'rented') $rented++; }
$occupancy_rate = count($properties) > 0 ? round(($rented / count($properties)) * 100) : 0;

// Chart.js for the revenue chart, html2canvas + jsPDF for PDF export
$extra_head = <<<HTML
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
HTML;

ob_start();
?>
<div class="space-y-6">
    <!-- Header with Print and Export PDF buttons -->
    <div class="sm:flex sm:items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-gray-900">Financial Reports & Summary</h1>
        <div class="mt-4 sm:ml-16 sm:mt-0 flex space-x-3">
            <button type="button" onclick="window.print()" class="flex items-center justify-center rounded-lg bg-gray-100 px-4 py-2 text-sm font-semibold text-gray-900 shadow-sm hover:bg-gray-200 border border-gray-300 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print Report
            </button>
            <button type="button" onclick="exportPDF()" class="flex items-center justify-center rounded-lg bg-[#7B5CFA] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-[#6849E3] transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export to PDF
            </button>
        </div>
    </div>

    <!-- Report content area: metric cards and revenue chart, target for print/PDF capture -->
    <div id="reportContent" class="space-y-6 bg-gray-50 p-2 sm:p-4 rounded-xl -m-2 sm:-m-4">
        <!-- Metric cards showing total revenue, expected rent, occupancy, active tenants, moved out, pending rent -->
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            <div class="bg-white px-4 py-5 shadow rounded-xl border border-gray-100 sm:p-6 flex items-start space-x-4">
                <div class="p-3 rounded-lg bg-green-100"><svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <div><dt class="text-sm font-medium text-gray-500 truncate">Total Received Revenue</dt><dd class="mt-1 text-2xl font-semibold text-gray-900"><?= formatCurrency($total_received) ?></dd></div>
            </div>
            <div class="bg-white px-4 py-5 shadow rounded-xl border border-gray-100 sm:p-6 flex items-start space-x-4">
                <div class="p-3 rounded-lg bg-blue-100"><svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg></div>
                <div><dt class="text-sm font-medium text-gray-500 truncate">Monthly Expected Rent</dt><dd class="mt-1 text-2xl font-semibold text-gray-900"><?= formatCurrency($total_expected_rent) ?></dd></div>
            </div>
            <div class="bg-white px-4 py-5 shadow rounded-xl border border-gray-100 sm:p-6 flex items-start space-x-4">
                <div class="p-3 rounded-lg bg-purple-100"><svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
                <div><dt class="text-sm font-medium text-gray-500 truncate">Occupancy Rate</dt><dd class="mt-1 text-2xl font-semibold text-gray-900"><?= $occupancy_rate ?>%</dd></div>
            </div>
            <div class="bg-white px-4 py-5 shadow rounded-xl border border-gray-100 sm:p-6 flex items-start space-x-4">
                <div class="p-3 rounded-lg bg-indigo-100"><svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"/></svg></div>
                <div><dt class="text-sm font-medium text-gray-500 truncate">Active Tenants (Waliopo)</dt><dd class="mt-1 text-2xl font-semibold text-gray-900"><?= $active_tenants ?></dd></div>
            </div>
            <div class="bg-white px-4 py-5 shadow rounded-xl border border-gray-100 sm:p-6 flex items-start space-x-4">
                <div class="p-3 rounded-lg bg-gray-100"><svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg></div>
                <div><dt class="text-sm font-medium text-gray-500 truncate">Moved Out (Waliohama)</dt><dd class="mt-1 text-2xl font-semibold text-gray-900"><?= $terminated ?></dd></div>
            </div>
            <div class="bg-white px-4 py-5 shadow rounded-xl border border-gray-100 sm:p-6 flex items-start space-x-4">
                <div class="p-3 rounded-lg bg-yellow-100"><svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 truncate">Pending Rent (Ambao Bado)</dt>
                    <dd class="mt-1 text-2xl font-semibold text-gray-900"><?= formatCurrency($pending_payments_amount) ?></dd>
                    <dd class="mt-0.5 text-xs font-medium text-yellow-600"><?= $pending_payments_count ?> pending payment(s)</dd>
                </div>
            </div>
        </div>

        <!-- Revenue chart card with Chart.js bar chart -->
        <div class="bg-white rounded-xl shadow border border-gray-100 p-6">
            <h3 class="text-lg font-medium leading-6 text-gray-900 mb-6">Revenue Over Time (Received)</h3>
            <div class="h-80 w-full">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js initialization and PDF export function using html2canvas + jsPDF -->
<script>
const labels = <?= $chart_labels ?>;
const values = <?= $chart_values ?>;

new Chart(document.getElementById('revenueChart'), {
    type: 'bar',
    data: {
        labels: labels,
        datasets: [{
            label: 'Revenue (TZS)',
            data: values,
            backgroundColor: '#3B82F6',
            borderRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(ctx) {
                        return 'TZS ' + ctx.parsed.y.toLocaleString();
                    }
                }
            }
        },
        scales: {
            x: { grid: { display: false } },
            y: {
                grid: { color: '#E5E7EB' },
                ticks: {
                    callback: function(value) {
                        return 'TZS ' + (value >= 1000 ? value/1000 + 'k' : value);
                    }
                }
            }
        }
    }
});

// Capture report content as an image and generate a downloadable PDF
async function exportPDF() {
    const { jsPDF } = window.jspdf;
    const el = document.getElementById('reportContent');
    try {
        const canvas = await html2canvas(el, { scale: 2 });
        const imgData = canvas.toDataURL('image/png');
        const pdf = new jsPDF('p', 'mm', 'a4');
        const pdfWidth = pdf.internal.pageSize.getWidth();
        const pdfHeight = (canvas.height * pdfWidth) / canvas.width;
        pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
        pdf.save('RMS_Financial_Report_<?= date('M_Y') ?>.pdf');
    } catch(e) {
        alert('Failed to generate PDF');
    }
}
</script>

<?php
$content = ob_get_clean();
$page_title = 'Reports';
require __DIR__ . '/includes/layout.php';
