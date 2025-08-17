<?php
// supplier-update.php
require_once "config.php";

// Be quiet about notices on UI pages
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', '0');

/* ---------- 1) Handle update BEFORE any HTML ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id   = trim($_POST['sid'] ?? '');
    $name = trim($_POST['sname'] ?? '');
    $addr = trim($_POST['sadd'] ?? '');
    $phno = trim($_POST['sphno'] ?? '');
    $mail = trim($_POST['smail'] ?? '');

    // Basic validation
    if ($id === '' || $name === '' || $addr === '' || $phno === '' || $mail === '') {
        $error = "Please fill all fields.";
    } else {
        $sql = "UPDATE suppliers
                SET sup_name = ?, sup_add = ?, sup_phno = ?, sup_mail = ?
                WHERE sup_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("sssss", $name, $addr, $phno, $mail, $id);
            if ($stmt->execute()) {
                // Redirect cleanly BEFORE any output
                header("Location: supplier-view.php");
                exit;
            } else {
                $error = "Error! Unable to update.";
            }
            $stmt->close();
        } else {
            $error = "System error. Please try again.";
        }
    }
}

/* ---------- 2) Load the row to edit ---------- */
$row = null;
if (isset($_GET['id']) && $_GET['id'] !== '') {
    $id = $_GET['id'];
    if ($s = $conn->prepare("SELECT sup_id, sup_name, sup_add, sup_phno, sup_mail FROM suppliers WHERE sup_id = ?")) {
        $s->bind_param("s", $id);
        if ($s->execute()) {
            $res = $s->get_result();
            $row = $res->fetch_assoc();
        }
        $s->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="nav2.css">
  <link rel="stylesheet" href="form4.css">
  <style>
    .sidenav h2::before,.sidenav h2::after{content:none!important}
  </style>
  <title>Suppliers</title>
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

<center><div class="head"><h2>UPDATE SUPPLIER DETAILS</h2></div></center>

<div class="one">
  <div class="row">

    <?php if (!$row): ?>
      <p style="color:#b91c1c; font-size:14px; padding:6px 10px;">
        Supplier not found or no ID provided.
      </p>
    <?php else: ?>

    <form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']) . '?id=' . urlencode($row['sup_id']) ?>">
      <div class="column">
        <p>
          <label>Supplier ID:</label><br>
          <input type="text" name="sid" value="<?= htmlspecialchars($row['sup_id']) ?>" readonly>
        </p>
        <p>
          <label>Supplier Company Name:</label><br>
          <input type="text" name="sname" value="<?= htmlspecialchars($row['sup_name']) ?>">
        </p>
        <p>
          <label>Address:</label><br>
          <input type="text" name="sadd" value="<?= htmlspecialchars($row['sup_add']) ?>">
        </p>
      </div>

      <div class="column">
        <p>
          <label>Phone Number:</label><br>
          <input type="text" name="sphno" value="<?= htmlspecialchars($row['sup_phno']) ?>">
        </p>
        <p>
          <label>Email Address:</label><br>
          <input type="email" name="smail" value="<?= htmlspecialchars($row['sup_mail']) ?>">
        </p>
      </div>

      <input type="submit" name="update" value="Update">
    </form>

    <?php if (!empty($error)): ?>
      <p style="color:#b91c1c; font-size:14px; margin-top:10px;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <?php endif; ?>

  </div>
</div>

<script>
  var dropdown = document.getElementsByClassName("dropdown-btn");
  for (var i=0;i<dropdown.length;i++){
    dropdown[i].addEventListener("click", function(){
      this.classList.toggle("active");
      var c = this.nextElementSibling;
      c.style.display = (c.style.display==="block") ? "none" : "block";
    });
  }
</script>
</body>
</html>
