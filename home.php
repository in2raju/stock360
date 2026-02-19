<?php 
require 'db.php';

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user'])) {
    header("Location: login.php");
    exit();
}

// Fetch key metrics
$userOrg = $_SESSION['user']['org_code'] ?? '';
$brCode  = $_SESSION['user']['br_code'] ?? '';

// Total Clients
$totalClients = $pdo->prepare("SELECT COUNT(*) FROM customer_info WHERE org_code=? AND (delete_status IS NULL OR delete_status='')");
$totalClients->execute([$userOrg]);
$totalClients = $totalClients->fetchColumn();

// Total Products
$totalProducts = $pdo->prepare("SELECT SUM(quantity) FROM stock_dtl WHERE org_code=? AND br_code=?");
$totalProducts->execute([$userOrg, $brCode]);
$totalProducts = $totalProducts->fetchColumn();

// Total Sales
$totalSales = $pdo->prepare("SELECT SUM(sold) FROM stock_details_view WHERE org_code=? AND br_code=?");
$totalSales->execute([$userOrg, $brCode]);
$totalSales = $totalSales->fetchColumn();

// Total Distributors
$totalDistributors = $pdo->prepare("SELECT COUNT(*) FROM distributor WHERE org_code=? AND br_code=?");
$totalDistributors->execute([$userOrg, $brCode]);
$totalDistributors = $totalDistributors->fetchColumn();

// Prepare statement
$categoryData = $pdo->prepare("
    SELECT 
        product_category_name, 
        SUM(remaining) AS total_quantity
    FROM 
        stock_details_view 
    WHERE 
        org_code = :org_code AND br_code = :br_code
    GROUP BY 
        product_category_name
");

// Bind parameters explicitly
$categoryData->bindParam(':org_code', $userOrg, PDO::PARAM_STR);
$categoryData->bindParam(':br_code', $brCode, PDO::PARAM_STR);

// Execute
$categoryData->execute();

$categories = [];
$quantities = [];
while ($row = $categoryData->fetch(PDO::FETCH_ASSOC)) {
    $categories[] = $row['product_category_name'];
    $quantities[] = $row['total_quantity'] ?? 0;
}


include 'header.php';
?>

<main class="flex-grow-1 container py-5">
    <h1 class="mb-4 text-dark">Dashboard</h1>

    <p class="mb-5">
        Welcome to <?= htmlspecialchars($_SESSION['user']['org_name'] ?? 'Organization') ?>. 
        Use the menu above to navigate.
    </p>

    <div class="row g-3">
        <!-- Dashboard Cards -->
        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden hover-scale">
                <div class="card-body p-3 bg-gradient-primary text-white">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="text-uppercase opacity-75">Total Clients</h6>
                            <h2 class="fw-bold mb-0 fs-4 count" data-count="<?= $totalClients ?>">0</h2>
                        </div>
                        <div class="fs-2"><i class="bi bi-people-fill"></i></div>
                    </div>
                    <p class="mb-0 small opacity-75">All registered customers in the system</p>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden hover-scale">
                <div class="card-body p-3 bg-gradient-success text-white">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="text-uppercase opacity-75">Total Products</h6>
                            <h2 class="fw-bold mb-0 fs-4 count" data-count="<?= $totalProducts ?>">0</h2>
                        </div>
                        <div class="fs-2"><i class="bi bi-box-seam"></i></div>
                    </div>
                    <p class="mb-0 small opacity-75">All products available in stock</p>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden hover-scale">
                <div class="card-body p-3 bg-gradient-warning text-white">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="text-uppercase opacity-75">Total Sales</h6>
                            <h2 class="fw-bold mb-0 fs-4 count" data-count="<?= $totalSales ?>">0</h2>
                        </div>
                        <div class="fs-2"><i class="bi bi-bar-chart-line-fill"></i></div>
                    </div>
                    <p class="mb-0 small opacity-75">Total quantity sold from all sales</p>
                </div>
            </div>
        </div>

        <div class="col-6 col-lg-3">
            <div class="card shadow-sm border-0 rounded-4 overflow-hidden hover-scale">
                <div class="card-body p-3 bg-gradient-warning1 text-white">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <div>
                            <h6 class="text-uppercase opacity-75">Total Distributors</h6>
                            <h2 class="fw-bold mb-0 fs-4 count" data-count="<?= $totalDistributors ?>">0</h2>
                        </div>
                        <div class="fs-2"><i class="bi bi-building"></i></div>
                    </div>
                    <p class="mb-0 small opacity-75">All registered distributors in the system</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Column Chart -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm rounded-4 p-3">
                <h5 class="mb-3">Stock View</h5>
                <div class="chart-container" style="overflow-x:auto;">
                    <canvas id="productColumnChart" style="height:350px;"></canvas>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const counters = document.querySelectorAll('.count');
    counters.forEach(counter => {
        let target = +counter.getAttribute('data-count');
        let count = 0;
        const step = Math.ceil(target / 100);
        const update = () => {
            count += step;
            if(count > target) count = target;
            counter.textContent = count.toLocaleString();
            if(count < target) requestAnimationFrame(update);
        };
        requestAnimationFrame(update);
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('productColumnChart').getContext('2d');

// Set wider chart width
const categoryCount = <?= count($categories) ?>;
const canvas = document.getElementById('productColumnChart');
canvas.style.width = Math.max(categoryCount * 1200, window.innerWidth) + 'px'; // 120px per bar

const productColumnChart = new Chart(ctx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($categories) ?>,
        datasets: [{
            label: 'Quantity',
            data: <?= json_encode($quantities) ?>,
            backgroundColor: '#4e73df'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return context.dataset.label + ': ' + context.raw.toLocaleString();
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: { 
                    display: true,
                    callback: function(value) { return value.toLocaleString(); } 
                }
            },
            x: { ticks: { autoSkip: false } }
        }
    }
});
</script>

<style>
.bg-gradient-primary { background: linear-gradient(135deg, #4e73df, #224abe); }
.bg-gradient-success { background: linear-gradient(135deg, #1cc88a, #17a673); }
.bg-gradient-warning { background: linear-gradient(135deg, #f6c23e, #f4b619); }
.bg-gradient-warning1 { background: linear-gradient(135deg, #048a78, #048a78); }

.hover-scale {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.hover-scale:hover {
    transform: translateY(-5px) scale(1.03);
    box-shadow: 0 12px 20px rgba(0,0,0,0.15);
}

.card-body { padding: 0.8rem !important; }
.fs-4 { font-size: 1.25rem !important; }
.fs-2 { font-size: 1.5rem !important; }

.chart-container {
    width: 100%;
    max-width: 100%;
}
</style>

<?php include 'footer.php'; ?>
