<?php
// stockreport.php
include "config.php";
$CURRENCY = "Tk"; // <-- change once if you need another currency
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="nav2.css?v=2">
  <link rel="stylesheet" href="table1.css?v=2">
  <style>
    .sidenav h2::before,.sidenav h2::after,.sidenav::before{content:none!important}
    .sidenav h2{font-family:Arial,sans-serif;color:#fff;text-align:center;margin:16px 0;padding:0 12px;font-weight:700}
    #table1 td.low { color:#c62828; font-weight:700; }
  </style>
  <title>Medicines - Low Stock</title>
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

  <div class="topnav">
    <a href="logout.php">Logout</a>
  </div>

  <center>
    <div class="head">
      <h2>MEDICINES LOW ON STOCK (LESS THAN 50)</h2>
    </div>
  </center>

  <table align="right" id="table1" style="margin-right:100px;">
    <tr>
      <th>Medicine ID</th>
      <th>Medicine Name</th>
      <th>Quantity Available</th>
      <th>Category</th>
      <th>Price (<?php echo htmlspecialchars($CURRENCY); ?>)</th>
    </tr>
    <?php
      // Simple, direct query — no stored procedure needed
      $sql = "SELECT med_id, med_name, med_qty, category, med_price
              FROM meds
              WHERE med_qty < 50
              ORDER BY med_qty ASC, med_name ASC";
      $result = $conn->query($sql);

      if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
          echo "<tr>";
            echo "<td>".htmlspecialchars($row['med_id'])."</td>";
            echo "<td>".htmlspecialchars($row['med_name'])."</td>";
            echo "<td class='low'>".htmlspecialchars($row['med_qty'])."</td>";
            echo "<td>".htmlspecialchars($row['category'])."</td>";
            echo "<td>".number_format((float)$row['med_price'], 2)."</td>";
          echo "</tr>";
        }
      } else {
        echo "<tr><td colspan='5' style='text-align:center;'>All medicines have 50+ quantity. 🎉</td></tr>";
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
