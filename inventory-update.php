<?php
// inventory-update.php
session_start();
require_once "config.php";

error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors','0');

/* ---- Settings (adjust if your column is different) ---- */
$locationColumn = 'location_rack';   // change to 'location' if that's your meds column name

/* ---- Resolve the medicine id ---- */
$medId = null;
if (isset($_POST['medid'])) {
  $medId = (int) $_POST['medid'];
} elseif (isset($_GET['id'])) {
  $medId = (int) $_GET['id'];
}

/* ---- If updating (POST), do it BEFORE any HTML output ---- */
if (isset($_POST['update'])) {
  if ($medId > 0) {
    $name  = trim($_POST['medname'] ?? '');
    $qty   = (int)($_POST['qty'] ?? 0);
    $cat   = trim($_POST['cat'] ?? '');
    $price = (float)($_POST['sp'] ?? 0);
    $loc   = trim($_POST['loc'] ?? '');

    $sql = "UPDATE meds
            SET med_name = ?, med_qty = ?, category = ?, med_price = ?, {$locationColumn} = ?
            WHERE med_id = ?";

    if ($st = $conn->prepare($sql)) {
      $st->bind_param("sisdsi", $name, $qty, $cat, $price, $loc, $medId);
      if ($st->execute()) {
        $st->close();
        header("Location: inventory-view.php?msg=updated");
        exit;
      }
      $st->close();
      header("Location: inventory-view.php?msg=update_error");
      exit;
    } else {
      header("Location: inventory-view.php?msg=prepare_error");
      exit;
    }
  } else {
    header("Location: inventory-view.php?msg=invalid_id");
    exit;
  }
}

/* ---- Load the row to edit (GET render path) ---- */
$row = null;
if ($medId > 0) {
  $sel = $conn->prepare("SELECT med_id, med_name, med_qty, category, med_price, {$locationColumn} FROM meds WHERE med_id = ?");
  $sel->bind_param("i", $medId);
  $sel->execute();
  $res = $sel->get_result();
  $row = $res->fetch_assoc();
  $sel->close();
}

/* If nothing found or id missing, send back to list with a message */
if (!$row) {
  header("Location: inventory-view.php?msg=not_found");
  exit;
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" type="text/css" href="nav2.css?v=2" />
  <link rel="stylesheet" type="text/css" href="form4.css?v=2" />
  <style>
    .sidenav h2::before, .sidenav h2::after { content:none !important; }
  </style>
  <title>Medicines</title>
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

<center><div class="head"><h2>UPDATE MEDICINE DETAILS</h2></div></center>

<div class="one">
  <div class="row">
    <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" autocomplete="off">
      <div class="column">
        <p>
          <label>Medicine ID:</label><br>
          <input type="number" name="medid" value="<?= htmlspecialchars($row['med_id']) ?>" readonly>
        </p>

        <p>
          <label>Medicine Name:</label><br>
          <input type="text" name="medname" value="<?= htmlspecialchars($row['med_name']) ?>" required>
        </p>

        <p>
          <label>Quantity:</label><br>
          <input type="number" name="qty" value="<?= htmlspecialchars($row['med_qty']) ?>" min="0" required>
        </p>

        <p>
          <label>Category:</label><br>
          <input type="text" name="cat" value="<?= htmlspecialchars($row['category']) ?>" required>
        </p>
      </div>

      <div class="column">
        <p>
          <label>Price (Tk):</label><br>
          <input type="number" step="0.01" name="sp" value="<?= htmlspecialchars($row['med_price']) ?>" min="0" required>
        </p>

        <p>
          <label>Location:</label><br>
          <input type="text" name="loc" value="<?= htmlspecialchars($row[$locationColumn]) ?>" required>
        </p>
      </div>

      <input type="submit" name="update" value="Update">
    </form>
  </div>
</div>

<script>
  var dd = document.getElementsByClassName("dropdown-btn");
  for (var i = 0; i < dd.length; i++) {
    dd[i].addEventListener("click", function () {
      this.classList.toggle("active");
      var c = this.nextElementSibling;
      c.style.display = (c.style.display === "block") ? "none" : "block";
    });
  }
</script>
</body>
</html>
