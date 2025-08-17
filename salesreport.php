<?php
include "config.php";

// --- settings ---
$CURRENCY = "Tk"; // change to "Rs", "$", etc.

// default to last 30 days if nothing submitted
$start = isset($_POST['start']) && $_POST['start'] !== "" ? $_POST['start'] : date('Y-m-d', strtotime('-30 days'));
$end   = isset($_POST['end'])   && $_POST['end']   !== "" ? $_POST['end']   : date('Y-m-d');
?>
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- PAGE styles FIRST -->
  <link rel="stylesheet" href="table1.css?v=8">
  <!-- NAV styles LAST so the sidebar title stays white everywhere -->
  <link rel="stylesheet" href="nav2.css?v=8">

  <!-- FINAL GUARD for the sidebar title -->
  <style>
    .sidenav h2, .sidenav h2 *{
      color:#fff !important;
      font-family:Arial, sans-serif !important;
      font-weight:800 !important;
      line-height:1.2 !important;
      text-align:center !important;
      margin:16px 12px !important;
      padding:0 !important;
      white-space:normal !important;
    }
    .sidenav h2::before,
    .sidenav h2::after,
    .sidenav::before { content:none !important; }

    /* Make the date filters line up under the page header like other pages */
    .report-filters{
      width:78%;
      float:right;
      margin:12px 53px 0 0; /* top 12px, right 53 like the header, bottom 0, left 0 */
    }
    .report-filters form{
      display:flex;
      align-items:center;
      flex-wrap:wrap;
      gap:12px;
    }
    .report-filters label{ font-weight:600; }
  </style>

  <title>Transaction Reports</title>
</head>
<body>
<div class="sidenav">
  <h2>Pharmacy Management System</h2>
  <a href="adminmainpage.php">Dashboard</a>
  <button class="dropdown-btn">Inventory <i class="down"></i></button>
  <div class="dropdown-container">
    <a href="inventory-add.php">Add New Medicine</a>
    <a href="inventory-view.php">Manage Inventory</a>
  </div>
  <button class="dropdown-btn">Suppliers <i class="down"></i></button>
  <div class="dropdown-container">
    <a href="supplier-add.php">Add New Supplier</a>
    <a href="supplier-view.php">Manage Suppliers</a>
  </div>
  <button class="dropdown-btn">Stock Purchase <i class="down"></i></button>
  <div class="dropdown-container">
    <a href="purchase-add.php">Add New Purchase</a>
    <a href="purchase-view.php">Manage Purchases</a>
  </div>
  <button class="dropdown-btn">Employees <i class="down"></i></button>
  <div class="dropdown-container">
    <a href="employee-add.php">Add New Employee</a>
    <a href="employee-view.php">Manage Employees</a>
  </div>
  <button class="dropdown-btn">Customers <i class="down"></i></button>
  <div class="dropdown-container">
    <a href="customer-add.php">Add New Customer</a>
    <a href="customer-view.php">Manage Customers</a>
  </div>
  <a href="sales-view.php">View Sales Invoice Details</a>
  <a href="salesitems-view.php">View Sold Products Details</a>
  <a href="pos1.php">Add New Sale</a>
  <button class="dropdown-btn">Reports <i class="down"></i></button>
  <div class="dropdown-container">
    <a href="stockreport.php">Medicines - Low Stock</a>
    <a href="expiryreport.php">Medicines - Soon to Expire</a>
    <a href="salesreport.php">Transactions Reports</a>
  </div>
</div>

<div class="topnav"><a href="logout.php">Logout</a></div>

<!-- Header aligned like other pages -->
<div class="head"><h2>TRANSACTION REPORTS</h2></div>

<!-- Filters placed directly under the header, not inside <center> -->
<div class="report-filters">
  <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
    <label>Start Date:</label>
    <input type="date" name="start" value="<?= htmlspecialchars($start) ?>">
    <label>End Date:</label>
    <input type="date" name="end" value="<?= htmlspecialchars($end) ?>">
    <button type="submit" name="submit">View Records</button>
  </form>
</div>

<?php
// PURCHASES within range
$pq = "
  SELECT p_id, sup_id, med_id, p_qty, p_cost, pur_date
  FROM purchase
  WHERE pur_date BETWEEN ? AND ?
  ORDER BY pur_date ASC, p_id ASC";
$stmtP = $conn->prepare($pq);
$stmtP->bind_param("ss", $start, $end);
$stmtP->execute();
$resP = $stmtP->get_result();

$totalPurch = 0.0;
?>
<table align="right" id="table1" style="margin-right:100px; margin-top:24px;">
  <tr>
    <th>Purchase ID</th><th>Supplier ID</th><th>Medicine ID</th>
    <th>Quantity</th><th>Date of Purchase</th><th>Cost of Purchase (<?= $CURRENCY ?>)</th>
  </tr>
  <?php if ($resP && $resP->num_rows): ?>
    <?php while($r = $resP->fetch_assoc()): ?>
      <?php $totalPurch += (float)$r['p_cost']; ?>
      <tr>
        <td><?= $r['p_id'] ?></td>
        <td><?= $r['sup_id'] ?></td>
        <td><?= $r['med_id'] ?></td>
        <td><?= $r['p_qty'] ?></td>
        <td><?= $r['pur_date'] ?></td>
        <td><?= number_format($r['p_cost'], 2) ?></td>
      </tr>
    <?php endwhile; ?>
    <tr>
      <td colspan="5" style="text-align:right;font-weight:600;">Total</td>
      <td><?= $CURRENCY . " " . number_format($totalPurch, 2) ?></td>
    </tr>
  <?php else: ?>
    <tr><td colspan="6" style="text-align:center;">No purchases in this range.</td></tr>
  <?php endif; ?>
</table>
<?php
$stmtP->close();

// SALES within range
$sq = "
  SELECT sale_id, c_id, e_id, s_date, total_amt
  FROM sales
  WHERE s_date BETWEEN ? AND ?
  ORDER BY s_date ASC, sale_id ASC";
$stmtS = $conn->prepare($sq);
$stmtS->bind_param("ss", $start, $end);
$stmtS->execute();
$resS = $stmtS->get_result();

$totalSales = 0.0;
?>
<table align="right" id="table1" style="margin-right:100px; margin-top:24px;">
  <tr>
    <th>Sale ID</th><th>Customer ID</th><th>Employee ID</th><th>Date</th><th>Sale Amount (<?= $CURRENCY ?>)</th>
  </tr>
  <?php if ($resS && $resS->num_rows): ?>
    <?php while($r = $resS->fetch_assoc()): ?>
      <?php $totalSales += (float)$r['total_amt']; ?>
      <tr>
        <td><?= $r['sale_id'] ?></td>
        <td><?= $r['c_id'] ?></td>
        <td><?= $r['e_id'] ?></td>
        <td><?= $r['s_date'] ?></td>
        <td><?= number_format($r['total_amt'], 2) ?></td>
      </tr>
    <?php endwhile; ?>
    <tr>
      <td colspan="4" style="text-align:right;font-weight:600;">Total</td>
      <td><?= $CURRENCY . " " . number_format($totalSales, 2) ?></td>
    </tr>
  <?php else: ?>
    <tr><td colspan="5" style="text-align:center;">No sales in this range.</td></tr>
  <?php endif; ?>
</table>
<?php
$stmtS->close();

// PROFIT
$profit = $totalSales - $totalPurch;
?>
<table align="right" id="table1" style="margin:24px 100px 100px 0;">
  <tr style="background:#f2f2f2;">
    <td style="font-weight:600;">Transaction Amount (Sales − Purchases)</td>
    <td><?= $CURRENCY . " " . number_format($profit, 2) ?></td>
  </tr>
</table>

<script>
  var dropdown = document.getElementsByClassName("dropdown-btn");
  for (var i = 0; i < dropdown.length; i++) {
    dropdown[i].addEventListener("click", function() {
      this.classList.toggle("active");
      var c = this.nextElementSibling;
      c.style.display = (c.style.display === "block") ? "none" : "block";
    });
  }
</script>
</body>
</html>
