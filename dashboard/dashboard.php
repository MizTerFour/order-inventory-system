<?php
require_once "../auth.php";
require_once "../php/connect.php";

// --- 1. รับค่าช่วงเวลา ---
$period = $_GET['period'] ?? '3m';
switch ($period) {
    case '3m':
        $interval = "3 MONTH";
        $loop_limit = 3;
        $unit = "months";
        break;
    case '1m':
        $interval = "1 MONTH";
        $loop_limit = 30;
        $unit = "days";
        break;
    case '15d':
        $interval = "15 DAY";
        $loop_limit = 15;
        $unit = "days";
        break;
    default:
        $interval = "3 MONTH";
        $loop_limit = 3;
        $unit = "months";
}

$thai_months_short = [
    1 => "ม.ค.",
    2 => "ก.พ.",
    3 => "มี.ค.",
    4 => "เม.ย.",
    5 => "พ.ค.",
    6 => "มิ.ย.",
    7 => "ก.ค.",
    8 => "ส.ค.",
    9 => "ก.ย.",
    10 => "ต.ค.",
    11 => "พ.ย.",
    12 => "ธ.ค."
];

$timeline = [];
for ($i = $loop_limit - 1; $i >= 0; $i--) {
    $time_stamp = strtotime("-$i $unit");
    if ($period == '3m') {
        $m = (int)date("m", $time_stamp);
        $timeline[] = $thai_months_short[$m] . " " . date("Y", $time_stamp);
    } else {
        $d = (int)date("d", $time_stamp);
        $m = (int)date("m", $time_stamp);
        $timeline[] = "$d " . $thai_months_short[$m];
    }
}

// 2. ข้อมูล KPI 
$sqlRev = "SELECT SUM(total_paid) AS rev FROM receipt WHERE payment_status = 'ชำระแล้ว' AND receipt_date >= DATE_SUB(CURDATE(), INTERVAL $interval)";
$resRev = mysqli_fetch_assoc(mysqli_query($conn, $sqlRev));
$revenue = $resRev['rev'] ?? 0;

$sqlCred = "SELECT SUM(total_amount) AS cred FROM orders WHERE order_status = 'รอชำระเงิน' AND order_date >= DATE_SUB(CURDATE(), INTERVAL $interval)";
$resCred = mysqli_fetch_assoc(mysqli_query($conn, $sqlCred));
$credit = $resCred['cred'] ?? 0;

// 3. ข้อมูล Top 5 สินค้าขายดี 
$sqlTop5 = "SELECT p.product_name, SUM(od.quantity * od.price) AS total 
            FROM order_detail od 
            JOIN orders o ON od.order_id = o.order_id 
            JOIN product p ON od.product_id = p.product_id 
            WHERE o.order_status = 'ชำระแล้ว' 
            AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL $interval)
            GROUP BY p.product_id 
            ORDER BY total DESC 
            LIMIT 5";
$resTop5 = mysqli_query($conn, $sqlTop5);
$topProductNames = [];
$topProductTotals = [];
while ($row = mysqli_fetch_assoc($resTop5)) {
    $topProductNames[] = $row['product_name'];
    $topProductTotals[] = (float)$row['total'];
}

// --- 4. ข้อมูลรายซัพพลายเออร์ ---
$suppliers = [];
$sRes = mysqli_query($conn, "SELECT supplier_id, supplier_name FROM supplier");
while ($s = mysqli_fetch_assoc($sRes)) {
    $suppliers[$s['supplier_id']] = ['name' => $s['supplier_name'], 'products' => []];
}

$sqlAll = "SELECT s.supplier_id, p.product_id, p.product_name, 
                  o.order_date,
                  SUM(od.quantity * od.price) AS total,
                  COUNT(DISTINCT o.customer_id) AS cust_count
           FROM order_detail od
           JOIN orders o ON od.order_id = o.order_id
           JOIN product p ON od.product_id = p.product_id
           JOIN supplier s ON p.supplier_id = s.supplier_id
           WHERE o.order_status = 'ชำระแล้ว'
           AND o.order_date >= DATE_SUB(CURDATE(), INTERVAL $interval)
           GROUP BY s.supplier_id, p.product_id, o.order_date";

$resAll = mysqli_query($conn, $sqlAll);
$rawSales = [];
$rawCustomers = [];
while ($row = mysqli_fetch_assoc($resAll)) {
    $t_date = strtotime($row['order_date']);
    $lab = ($period == '3m') ? $thai_months_short[(int)date("m", $t_date)] . " " . date("Y", $t_date) : (int)date("d", $t_date) . " " . $thai_months_short[(int)date("m", $t_date)];
    if (!isset($rawSales[$row['product_id']][$lab])) {
        $rawSales[$row['product_id']][$lab] = 0;
        $rawCustomers[$row['product_id']][$lab] = 0;
    }
    $rawSales[$row['product_id']][$lab] += (float)$row['total'];
    $rawCustomers[$row['product_id']][$lab] += (int)$row['cust_count'];
    $suppliers[$row['supplier_id']]['products'][$row['product_id']] = $row['product_name'];
}

$supplierChartsData = [];
foreach ($suppliers as $sid => $sInfo) {
    $productLines = [];

    // เพิ่มเงื่อนไข: ถ้าไม่มีข้อมูลการขายเลย ให้สร้างชุดข้อมูลที่เป็น 0 
    if (empty($sInfo['products'])) {
        $zeroData = array_fill(0, count($timeline), 0);
        $productLines[] = [
            'label' => 'ไม่มีรายการขาย',
            'data' => $zeroData,
            'cust_data' => $zeroData
        ];
    } else {
        foreach ($sInfo['products'] as $pid => $pName) {
            $dataPoints = [];
            $customerPoints = [];
            foreach ($timeline as $t) {
                $dataPoints[] = $rawSales[$pid][$t] ?? 0;
                $customerPoints[] = $rawCustomers[$pid][$t] ?? 0;
            }
            $productLines[] = ['label' => $pName, 'data' => $dataPoints, 'cust_data' => $customerPoints];
        }
    }
    $supplierChartsData[] = ['name' => $sInfo['name'], 'lines' => $productLines];
}

// --- 5. เพิ่มใหม่: ข้อมูลแจ้งเตือนบิลเลยกำหนด ---
$sql_overdue = "SELECT COUNT(order_id) AS total_orders, SUM(total_amount) AS total_money 
                FROM orders 
                WHERE order_status = 'รอชำระเงิน' 
                AND due_date IS NOT NULL 
                AND due_date < CURDATE()";
$res_overdue = mysqli_query($conn, $sql_overdue);
$overdue_data = mysqli_fetch_assoc($res_overdue);

$overdue_count = $overdue_data['total_orders'] ?? 0;
$overdue_money = $overdue_data['total_money'] ?? 0;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>Dashboard | พยากรณ์การขาย</title>
    <link rel="icon" type="image/x-icon" href="../logo.jpg">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link rel="stylesheet" href="/PROJECT/style.css">
    <style>
        body {
            margin: 0;
            font-family: 'Segoe UI', sans-serif;
            background: #f4f6f9;
            display: flex;
        }

        .content {
            flex: 1;
            padding: 30px;
        }

        .grid-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }

        .chart-box {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            height: 400px;
            display: flex;
            flex-direction: column;
        }

        .chart-container {
            flex: 1;
            position: relative;
        }

        .chart-title {
            font-weight: bold;
            margin-bottom: 15px;
            color: #374151;
            font-size: 16px;
        }

        .kpi-container {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .kpi {
            padding: 20px;
            border-radius: 10px;
            background: #fff;
            border-left: 5px solid #ccc;
            font-size: 18px;
        }

        .period-selector {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff;
            padding: 10px 20px;
            border-radius: 12px;
            margin-bottom: 25px;
        }

        .period-selector input[type="radio"] {
            display: none;
        }

        .period-selector label {
            padding: 8px 16px;
            background: #f3f4f6;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
        }

        .period-selector input[type="radio"]:checked+label {
            background: #3b82f6;
            color: #fff;
        }

        /* Forecast Analysis UI */
        .analysis-card {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-around;
            padding: 10px;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: #f8fafc;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
        }

        .stat-label {
            font-size: 13px;
            color: #64748b;
            font-weight: 600;
            display: block;
            margin-bottom: 5px;
        }

        .stat-value {
            font-size: 32px;
            font-weight: 800;
            color: #1e293b;
        }

        .stat-unit {
            font-size: 14px;
            color: #94a3b8;
            margin-left: 4px;
        }

        .health-bar-container {
            width: 100%;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            margin-top: 10px;
            overflow: hidden;
        }

        #healthBar {
            height: 100%;
            width: 0%;
            transition: width 0.5s ease;
        }


        @media print {

            /* 1. ซ่อนเฉพาะส่วนที่เป็นปุ่มและ Sidebar */
            .sidebar,
            #btnToggle,
            .period-selector,
            #alertBox,
            #pForm,
            #forecastDashboard>.chart-box:first-child {
                display: none !important;
            }

            /* 2. บังคับให้หน้า Forecast Dashboard ทั้งหมดหายไปถ้าเราจะพิมพ์หน้าหลัก */
            /* หรือถ้าจะพิมพ์หน้า Forecast ให้ซ่อนหน้าหลักแทน */
            #forecastDashboard {
                display: none !important;
                /* ถ้าต้องการพิมพ์หน้าหลักอย่างเดียว */
            }

            /* 3. จัดการให้ KPI และเนื้อหาหลักโชว์ตัว */
            .content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
                display: block !important;
            }

            /* บังคับให้ KPI โชว์ (บรรทัดสำคัญ) */
            .kpi-container,
            #topKpi {
                display: flex !important;
                /* เปลี่ยนจาก table เป็น flex หรือ block */
                visibility: visible !important;
                margin-bottom: 20px !important;
            }

            .kpi {
                flex: 1 !important;
                display: block !important;
                border: 1px solid #ddd !important;
                padding: 15px !important;
            }

            .grid-container#mainDashboard {
                display: block !important;
            }

            .chart-box {
                page-break-inside: avoid;
                margin-bottom: 30px !important;
                display: block !important;
            }
        }

        @media print {

            /* ซ่อนส่วนที่ไม่เกี่ยวข้องกับการรายงาน */
            .sidebar,
            #btnToggle,
            .period-selector,
            #alertBox,
            #pForm,
            aside {
                display: none !important;
            }

            .content {
                margin: 0 !important;
                padding: 0 !important;
                width: 100% !important;
            }

            /* --- ส่วนควบคุมการสลับหน้าพิมพ์ --- */

            /* กรณีพิมพ์หน้าปกติ (Default) */
            body:not(.print-forecast) #forecastDashboard {
                display: none !important;
            }

            /* กรณีพิมพ์หน้าพยากรณ์ (เมื่อมี Class print-forecast) */
            body.print-forecast #mainDashboard,
            body.print-forecast #topKpi,
            body.print-forecast h2 {
                display: none !important;
            }

            body.print-forecast #forecastDashboard {
                display: block !important;
            }

            /* ตกแต่งกราฟพยากรณ์ตอนพิมพ์ */
            .chart-box {
                page-break-inside: avoid;
                height: auto !important;
                border: 1px solid #eee !important;
                margin-bottom: 20px !important;
            }

            /* ซ่อนแถบเลือกโรงงาน/สินค้าในหน้าพยากรณ์ตอนพิมพ์ */
            #forecastDashboard .chart-box:first-child {
                display: none !important;
            }
        }
    </style>
</head>

<body>
    <?php include "../aside.php"; ?>
    <div class="content">
        <h2>เเดชบอร์ด วิเคราะห์ข้อมูล</h2>

        <?php if ($overdue_count > 0) { ?>
            <div id="alertBox" style="background: #fff; border-left: 5px solid #ef4444; padding: 12px 20px; border-radius: 8px; margin-bottom: 25px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); display: flex; align-items: center; justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 15px;">
                    <span style="font-size: 24px;">⚠️</span>
                    <div>
                        <strong style="color: #b91c1c; display: block; font-size: 15px;">พบรายการเลยกำหนดชำระ!</strong>
                        <span style="color: #666; font-size: 14px;">
                            จำนวน <b><?= $overdue_count ?></b> รายการ | ยอดรวม: <b style="color: #ef4444;"><?= number_format($overdue_money, 2) ?> บาท</b>
                        </span>
                    </div>
                </div>
                <a href="../order/order_list.php" style="background: #ef4444; color: #fff; text-decoration: none; padding: 6px 15px; border-radius: 6px; font-size: 13px; font-weight: 600; transition: 0.2s;" onmouseover="this.style.background='#dc2626'" onmouseout="this.style.background='#ef4444'">
                    จัดการ
                </a>
            </div>
        <?php } ?>

        <div class="kpi-container" id="topKpi">
            <div class="kpi" style="border-color: #10b981;">💰 ยอดขาย: <b><?= number_format($revenue, 2) ?></b> บาท</div>
            <div class="kpi" style="border-color: #f97316;">⏳ ยอดค้างชำระ: <b><?= number_format($credit, 2) ?></b> บาท</div>
        </div>

        <div class="period-selector">
            <form method="get" id="pForm" style="display:flex; gap:10px; margin:0;">
                <input type="radio" name="period" value="15d" id="p15d" <?= $period == '15d' ? 'checked' : '' ?> onchange="this.form.submit()">
                <label for="p15d">15 วัน</label>
                <input type="radio" name="period" value="1m" id="p1m" <?= $period == '1m' ? 'checked' : '' ?> onchange="this.form.submit()">
                <label for="p1m">1 เดือน</label>
                <input type="radio" name="period" value="3m" id="p3m" <?= $period == '3m' ? 'checked' : '' ?> onchange="this.form.submit()">
                <label for="p3m">3 เดือน</label>
            </form>
            <button type="button" id="btnToggle" onclick="toggleForecast()" style="background:#f59e0b; color:#fff; border:none; border-radius:8px; padding:8px 15px; cursor:pointer; margin-left:auto;">📊 เข้าสู่โหมดพยากรณ์</button>
            <button type="button" onclick="window.print()" style="background:#10b981; color:#fff; border:none; border-radius:8px; padding:8px 15px; cursor:pointer; margin-left:10px;">
                🖨️ พิมพ์รายงาน
            </button>
        </div>

        <div class="grid-container" id="mainDashboard">
            <div class="chart-box">
                <div class="chart-title">🏆 สินค้าขายดี 5 อันดับแรก</div>
                <div class="chart-container"><canvas id="top5Chart"></canvas></div>
            </div>
            <?php foreach ($supplierChartsData as $idx => $s): ?>
                <div class="chart-box">
                    <div class="chart-title">🏭 <?= htmlspecialchars($s['name']) ?></div>
                    <div class="chart-container"><canvas id="sChart_<?= $idx ?>"></canvas></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid-container" id="forecastDashboard" style="display: none;">
            <div class="chart-box" style="grid-column: span 2; height: auto;">
                <div style="display: flex; gap: 20px; align-items: center;">
                    <b>เลือกโรงงาน:</b> <select id="selSupplier" onchange="updateForecastCharts()" style="padding:8px; border-radius:6px; border:1px solid #ddd;"></select>
                    <b>เลือกสินค้า:</b> <select id="selProduct" onchange="updateForecastCharts()" style="padding:8px; border-radius:6px; border:1px solid #ddd;"></select>
                </div>
            </div>
            <div class="chart-box">
                <div class="chart-title" id="forecastTitle">📈 พยากรณ์แนวโน้มยอดขาย (SMA)</div>
                <div class="chart-container"><canvas id="productForecastChart"></canvas></div>
            </div>
            <div class="chart-box">
                <div class="chart-title">🎯ปัจจัยสนับสนุน Forecast</div>
                <div class="analysis-card">
                    <div class="stat-item">
                        <span class="stat-label">ยอดซื้อเฉลี่ยต่อคน (AOV)</span>
                        <div class="stat-value" id="avgValue" style="color:#10b981;">0</div>
                        <span class="stat-unit">บาท/คน</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">จำนวนลูกค้า</span>
                        <div class="stat-value" id="custCount" style="color:#8b5cf6;">0</div>
                        <span class="stat-unit">ราย</span>
                    </div>
                    <div style="padding: 0 10px;">
                        <span class="stat-label" id="healthText">ความหนาแน่นของฐานลูกค้า: ต่ำ</span>
                        <div class="health-bar-container">
                            <div id="healthBar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#14b8a6'];
        const thaiMonths = ["ม.ค.", "ก.พ.", "มี.ค.", "เม.ย.", "พ.ค.", "มิ.ย.", "ก.ค.", "ส.ค.", "ก.ย.", "ต.ค.", "พ.ย.", "ธ.ค."];
        const rawData = <?= json_encode($supplierChartsData) ?>;
        const timeline = <?= json_encode($timeline) ?>;
        let isForecastMode = false,
            forecastChart1;

        // Init Main Charts
        new Chart(document.getElementById('top5Chart'), {
            type: 'bar',
            data: {
                labels: <?= json_encode($topProductNames) ?>,
                datasets: [{
                    label: 'ยอดเงิน',
                    data: <?= json_encode($topProductTotals) ?>,
                    backgroundColor: '#3b82f6'
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false
            }
        });

        <?php foreach ($supplierChartsData as $idx => $s): ?>
            new Chart(document.getElementById('sChart_<?= $idx ?>'), {
                type: 'line',
                data: {
                    labels: timeline,
                    datasets: [<?php foreach ($s['lines'] as $lIdx => $l): ?> {
                            label: '<?= htmlspecialchars($l['label']) ?>',
                            data: <?= json_encode($l['data']) ?>,
                            borderColor: colors[<?= $lIdx ?> % colors.length],
                            tension: 0.3
                        }, <?php endforeach; ?>]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            suggestedMax: 10 // ช่วยให้กราฟ 0 ไม่ลอยอยู่ตรงกลาง แต่จะเกาะอยู่เส้นขอบล่าง
                        }
                    }
                }
            });
        <?php endforeach; ?>

        function toggleForecast() {
            const main = document.getElementById('mainDashboard'),
                fore = document.getElementById('forecastDashboard'),
                topKpi = document.getElementById('topKpi'),
                pForm = document.getElementById('pForm'),
                btn = document.getElementById('btnToggle'),
                alertBox = document.getElementById('alertBox'); // เพิ่มการควบคุมกล่องแจ้งเตือน

            document.body.classList.toggle('print-forecast');

            if (!isForecastMode) {

                main.style.display = 'none';
                topKpi.style.display = 'none';
                pForm.style.visibility = 'hidden';
                if (alertBox) alertBox.style.display = 'none'; // ซ่อนตอนเข้าหน้าพยากรณ์

                fore.style.display = 'grid';
                btn.innerText = '🏠 กลับหน้าหลัก';
                btn.style.background = '#f59e0b';

                if (document.getElementById('selSupplier').options.length === 0) {
                    rawData.forEach((s, i) => document.getElementById('selSupplier').add(new Option(s.name, i)));
                }
                updateForecastCharts();
            } else {

                main.style.display = 'grid';
                topKpi.style.display = 'grid';
                pForm.style.visibility = 'visible';
                if (alertBox) alertBox.style.display = 'flex';

                fore.style.display = 'none';
                btn.innerText = '📊 เข้าสู่โหมดพยากรณ์';
                btn.style.background = '#f59e0b';
            }
            isForecastMode = !isForecastMode;
        }

        function updateForecastCharts() {
            const sIdx = document.getElementById('selSupplier').value || 0,
                supplier = rawData[sIdx],
                selProd = document.getElementById('selProduct');

            // --- ปรับปรุงส่วนการจัดการ Dropdown สินค้า ---
            const prevVal = selProd.value;
            selProd.innerHTML = '<option value="all">--- รวมทุกสินค้า ---</option>'; // เพิ่มตัวเลือก "รวม"
            supplier.lines.forEach((l, i) => {
                let opt = new Option(l.label, i);
                selProd.add(opt);
            });

            // รักษาค่าที่เลือกไว้เดิม ถ้าไม่ใช่ครั้งแรกที่โหลด
            if (prevVal !== "") selProd.value = prevVal;

            let actualSales, actualCust, labelName;

            if (selProd.value === 'all') {
                // กรณี "รวมทุกสินค้า": นำข้อมูลทุก Line มาบวกกัน
                labelName = "ภาพรวม: " + supplier.name;
                // สร้าง Array ค่าเริ่มต้นเป็น 0 ตามความยาว timeline
                actualSales = new Array(timeline.length).fill(0);
                actualCust = new Array(timeline.length).fill(0);

                supplier.lines.forEach(line => {
                    line.data.forEach((val, idx) => actualSales[idx] += val);
                    line.cust_data.forEach((val, idx) => actualCust[idx] += val);
                });
            } else {
                // กรณีเลือกรายสินค้า
                const pIdx = selProd.value;
                const product = supplier.lines[pIdx];
                actualSales = product.data;
                actualCust = product.cust_data;
                labelName = product.label;
            }


            // --- เพิ่มบรรทัดนี้เพื่ออัปเดตชื่อหัวกราฟ ---
            document.getElementById('forecastTitle').innerText = `📈 พยากรณ์แนวโน้มยอดขาย (SMA) : ${supplier.name} - ${labelName}`;

            // 1. SMA Forecast Calculation
            let fData = [...actualSales],
                nextLabels = [];
            const currentPeriod = "<?= $period ?>";
            for (let i = 1; i <= 5; i++) {
                let last5 = fData.slice(-5);
                let sma = last5.reduce((a, b) => a + b, 0) / (last5.length || 1);
                fData.push(sma);

                let lastDate = timeline[timeline.length - 1];
                if (currentPeriod === '3m') {
                    let p = lastDate.split(' '),
                        mIdx = thaiMonths.indexOf(p[0]);
                    let d = new Date(parseInt(p[1]), mIdx + i, 1);
                    nextLabels.push(`${thaiMonths[d.getMonth()]} ${d.getFullYear()}`);
                } else {
                    let p = lastDate.split(' '),
                        dVal = parseInt(p[0]),
                        mIdx = thaiMonths.indexOf(p[1]);
                    let d = new Date(new Date().getFullYear(), mIdx, dVal + i);
                    nextLabels.push(`${d.getDate()} ${thaiMonths[d.getMonth()]}`);
                }
            }

            // 2. Render Forecast Graph
            if (forecastChart1) forecastChart1.destroy();
            forecastChart1 = new Chart(document.getElementById('productForecastChart'), {
                type: 'line',
                data: {
                    labels: [...timeline, ...nextLabels],
                    datasets: [{
                        label: 'ยอดขาย (พยากรณ์เส้นประ)',
                        data: fData,
                        borderColor: '#3b82f6',
                        backgroundColor: 'rgba(59, 130, 246, 0.05)',
                        fill: true,
                        segment: {
                            borderDash: ctx => ctx.p1DataIndex >= actualSales.length ? [5, 5] : undefined
                        },
                        tension: 0.3
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });

            // 3. Update Analysis UI
            const sumSales = actualSales.reduce((a, b) => a + b, 0);
            const sumCust = actualCust.reduce((a, b) => a + b, 0);
            const aov = sumCust > 0 ? (sumSales / sumCust) : 0;

            document.getElementById('avgValue').innerText = aov.toLocaleString(undefined, {
                maximumFractionDigits: 0
            });
            document.getElementById('custCount').innerText = sumCust.toLocaleString();

            const healthBar = document.getElementById('healthBar');
            const healthText = document.getElementById('healthText');
            let score = (sumCust / 10) * 100; // สมมติว่าเป้าหมายคือ 10 ราย
            if (score > 100) score = 100;

            healthBar.style.width = score + '%';
            if (score < 30) {
                healthBar.style.backgroundColor = '#ef4444';
                healthText.innerText = "⚠️ ความเสี่ยงสูง: ฐานลูกค้าบางเบา";
            } else if (score < 70) {
                healthBar.style.backgroundColor = '#f59e0b';
                healthText.innerText = "🟡 ความเสี่ยงปานกลาง: ควรหาลูกค้าเพิ่ม";
            } else {
                healthBar.style.backgroundColor = '#10b981';
                healthText.innerText = "✅ ความเสี่ยงต่ำ : ฐานลูกค้าแข็งแกร่ง";
            }
        }
    </script>
</body>

</html>