<?php
include "config.php";
$CURRENCY = "Tk";
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- Page styles FIRST -->
  <link rel="stylesheet" href="table1.css?v=7">
  <!-- Navigation styles LAST so they win over table styles -->
  <link rel="stylesheet" href="nav2.css?v=7">

  <!-- Keep the sidebar title consistent -->
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
  </style>

  <title>Medicines - Soon to Expire</title>
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

  <button class="dropdown-btn">Reports <i class="down"></i></button>
  <div class="dropdown-container">
    <a href="stockreport.php">Medicines - Low Stock</a>
    <a href="expiryreport.php">Medicines - Soon to Expire</a>
    <a href="salesreport.php">Transactions Reports</a>
  </div>
</div>

<div class="topnav"><a href="logout.php">Logout</a></div>

<!-- NO <center> HERE -->
<div class="head"><h2>STOCK EXPIRING WITHIN 6 MONTHS</h2></div>

<table align="right" id="table1" style="margin-right:100px;">
  <tr>
    <th>Purchase ID</th>
    <th>Supplier ID</th>
    <th>Medicine ID</th>
    <th>Medicine Name</th>
    <th>Quantity</th>
    <th>Cost of Purchase (<?= htmlspecialchars($CURRENCY) ?>)</th>
    <th>Date of Purchase</th>
    <th>Manufacturing Date</th>
    <th>Expiry Date</th>
  </tr>
<?php
$sql = "
  SELECT p.p_id, p.sup_id, p.med_id, m.med_name, p.p_qty, p.p_cost,
         p.pur_date, p.mfg_date, p.exp_date
  FROM purchase p
  JOIN meds m ON m.med_id = p.med_id
  WHERE p.exp_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 6 MONTH)
  ORDER BY p.exp_date ASC, p.p_id ASC";
$res = $conn->query($sql);

if ($res && $res->num_rows) {
  while($r = $res->fetch_assoc()) {
    echo "<tr>";
    echo "<td>".htmlspecialchars($r['p_id'])."</td>";
    echo "<td>".htmlspecialchars($r['sup_id'])."</td>";
    echo "<td>".htmlspecialchars($r['med_id'])."</td>";
    echo "<td>".htmlspecialchars($r['med_name'])."</td>";
    echo "<td>".htmlspecialchars($r['p_qty'])."</td>";
    echo "<td>".number_format((float)$r['p_cost'],2)."</td>";
    echo "<td>".htmlspecialchars($r['pur_date'])."</td>";
    echo "<td>".htmlspecialchars($r['mfg_date'])."</td>";
    echo "<td style='color:#b45309;font-weight:600'>".htmlspecialchars($r['exp_date'])."</td>";
    echo "</tr>";
  }
} else {
  echo "<tr><td colspan='9' style='text-align:center;'>No items expiring in the next 6 months.</td></tr>";
}
$conn->close();
?>
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
