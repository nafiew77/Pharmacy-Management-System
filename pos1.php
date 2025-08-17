<?php
/* ---------------- boot ---------------- */
session_start();
require_once "config.php";

/* keep PHP from spraying warnings into the page */
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors','0');

/* ------------ helpers ------------- */
function current_employee_id(mysqli $conn): string {
  // Admin page: sales.e_id must reference a valid EMPLOYEE.E_ID.
  // If you have a mapping, fetch it; otherwise use a safe fallback that exists.
  if (!empty($_SESSION['eid'])) return (string)$_SESSION['eid'];

  // Try to map from admin login to an employee id with same username (optional)
  if (!empty($_SESSION['admin'])) {
    $u = $_SESSION['admin'];
    if ($stmt = $conn->prepare("SELECT E_ID FROM EMPLOYEE WHERE E_USERNAME=? LIMIT 1")) {
      $stmt->bind_param("s",$u);
      if ($stmt->execute()) {
        $res = $stmt->get_result()->fetch_row();
        if ($res) { $_SESSION['eid'] = (string)$res[0]; return (string)$res[0]; }
      }
      $stmt->close();
    }
  }

  // Fallback to a known employee id that exists in your DB (adjust if needed)
  $_SESSION['eid'] = '1';
  return '1';
}

function topbar_name(mysqli $conn): string {
  $eid = current_employee_id($conn);
  if ($stmt = $conn->prepare("SELECT E_FNAME FROM EMPLOYEE WHERE E_ID=?")) {
    $stmt->bind_param("s",$eid);
    $stmt->execute();
    $r = $stmt->get_result()->fetch_row();
    $stmt->close();
    return $r ? $r[0] : '';
  }
  return '';
}

/* ---------------- page state ---------------- */
$ename = topbar_name($conn);
$cust_msg = '';
$add_msg  = '';

/* ---------------- create (or attach to) a sale ----------------
   We create a new row in `sales` once the admin selects a real customer,
   and remember it in $_SESSION['current_sale_id'].
-----------------------------------------------------------------*/
if (isset($_POST['custadd'])) {
  $cid = trim($_POST['cid'] ?? '0');
  if ($cid === '0' || $cid === '') {
    $cust_msg = "<p style='color:#b91c1c'>Please pick a valid customer.</p>";
  } else {
    // verify customer exists
    $ok = false;
    if ($s = $conn->prepare("SELECT 1 FROM customer WHERE C_ID=?")) {
      $s->bind_param("s",$cid);
      $s->execute();
      $ok = (bool)$s->get_result()->fetch_row();
      $s->close();
    }
    if (!$ok) {
      $cust_msg = "<p style='color:#b91c1c'>That customer id doesn’t exist.</p>";
    } else {
      $eid = current_employee_id($conn);
      // start a new sale
      if ($ins = $conn->prepare("INSERT INTO sales (c_id, e_id) VALUES (?, ?)")) {
        $ins->bind_param("ss",$cid,$eid);
        if ($ins->execute()) {
          $_SESSION['current_sale_id'] = (int)$conn->insert_id;
          $cust_msg = "<p style='color:#065f46'>Sale started (ID #{$_SESSION['current_sale_id']}).</p>";
        } else {
          $cust_msg = "<p style='color:#b91c1c'>Couldn’t start sale. Try again.</p>";
        }
        $ins->close();
      }
    }
  }
}

/* the active sale id for this session */
$sid = (int)($_SESSION['current_sale_id'] ?? 0);

/* ---------------- search medicine ----------------
   We show details of the searched medicine (if any).
   NOTE: uses `location_rack` to match your schema.
----------------------------------------------------*/
$rowMed = ['', '', '', '', '', '']; // id,name,qty,category,price,location_rack
if (isset($_POST['search'])) {
  $medName = trim($_POST['medname'] ?? '');
  if ($medName !== '') {
    if ($st = $conn->prepare("SELECT med_id, med_name, med_qty, category, med_price, location_rack FROM meds WHERE med_name=?")) {
      $st->bind_param("s",$medName);
      if ($st->execute()) {
        $r = $st->get_result()->fetch_row();
        if ($r) { $rowMed = $r; }
      }
      $st->close();
    }
  }
}

/* ---------------- add line item ---------------- */
if (isset($_POST['add'])) {
  if ($sid <= 0) {
    $add_msg = "<p style='color:#b91c1c'>No active sale. Select a customer first.</p>";
  } else {
    $mid = (int)($_POST['medid'] ?? 0);
    $qty = (int)($_POST['mcqty'] ?? 0);

    if ($mid <= 0 || $qty <= 0) {
      $add_msg = "<p style='color:#b91c1c'>Pick a medicine and enter a valid quantity.</p>";
    } else {
      // check live stock + price
      $live = null;
      if ($c = $conn->prepare("SELECT med_qty, med_price FROM meds WHERE med_id=?")) {
        $c->bind_param("i",$mid);
        $c->execute();
        $live = $c->get_result()->fetch_assoc();
        $c->close();
      }
      if (!$live) {
        $add_msg = "<p style='color:#b91c1c'>Medicine not found.</p>";
      } elseif ($qty > (int)$live['med_qty']) {
        $add_msg = "<p style='color:#b91c1c'>Not enough stock. Available: ".(int)$live['med_qty']."</p>";
      } else {
        $price = (float)$live['med_price'];
        $line_total = $price * $qty;

        // upsert into sales_items
        $sql = "INSERT INTO sales_items (sale_id, med_id, sale_qty, tot_price)
                VALUES (?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                  sale_qty  = sale_qty  + VALUES(sale_qty),
                  tot_price = tot_price + VALUES(tot_price)";
        if ($ins = $conn->prepare($sql)) {
          $ins->bind_param("iiid", $sid, $mid, $qty, $line_total);
          if ($ins->execute()) {
            // decrement stock atomically
            if ($u = $conn->prepare("UPDATE meds SET med_qty = med_qty - ? WHERE med_id=? AND med_qty >= ?")) {
              $u->bind_param("iii",$qty,$mid,$qty);
              $u->execute();
              $u->close();
            }
            $add_msg = "<p style='color:#065f46'>Item added. <a class='button1 view-btn' href='pos2.php?sid={$sid}'>View Order</a></p>";
          } else {
            $add_msg = "<p style='color:#b91c1c'>Could not add item.</p>";
          }
          $ins->close();
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="nav2.css?v=15">
  <link rel="stylesheet" href="form3.css?v=15">
  <link rel="stylesheet" href="table2.css?v=15">
  <title>New Sales</title>
  <style>
    .sidenav h2::before,.sidenav h2::after,.sidenav::before{content:none!important}
    .sidenav h2{font-family:Arial,sans-serif;color:#fff;text-align:center;margin:16px 0;padding:0 12px;font-weight:700}
    .hint{font-size:12px;color:#666;margin-top:4px;}
    .card{background:#f5f6f8;border-radius:10px;padding:16px}
    .msg{margin:10px 0}
    .ok{color:#065f46}.err{color:#b91c1c}
    .view-btn{display:inline-block;padding:6px 10px;border-radius:6px;background:#0ea5e9;color:#fff;text-decoration:none}
  </style>
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
  <a href="pos1.php" class="active">Add New Sale</a>

  <button class="dropdown-btn">Reports <i class="down"></i></button>
  <div class="dropdown-container">
    <a href="stockreport.php">Medicines - Low Stock</a>
    <a href="expiryreport.php">Medicines - Soon to Expire</a>
    <a href="salesreport.php">Transactions Reports</a>
  </div>
</div>

<div class="topnav">
  <a href="logout.php">Logout (signed in as <?= htmlspecialchars($ename ?: 'Admin') ?>)</a>
</div>

<center><div class="head"><h2>POINT OF SALE</h2></div></center>

<!-- Select Customer (creates a sale) -->
<form method="post" action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" style="max-width:720px;margin:10px auto;">
  <label for="cid" style="display:block;margin-bottom:6px;">Select Customer ID (creates a sale)</label>
  <select id="cid" name="cid" style="width:100%;padding:8px;">
    <option value="0" selected>*Choose customer…</option>
    <?php
      if ($res = $conn->query("SELECT C_ID FROM customer ORDER BY C_ID ASC")) {
        while ($r = $res->fetch_assoc()) {
          echo '<option value="'.htmlspecialchars($r['C_ID']).'">'.htmlspecialchars($r['C_ID']).'</option>';
        }
        $res->close();
      }
    ?>
  </select>
  <div style="margin-top:8px;">
    <button type="submit" name="custadd">Add to Proceed.</button>
  </div>
  <?php if ($cust_msg) echo '<div class="msg">'.$cust_msg.'</div>'; ?>
</form>

<!-- Search medicine by name -->
<form method="post" style="max-width:720px;margin:16px auto;">
  <label for="medname" style="display:block;margin-bottom:6px;">Search Medicine</label>
  <select id="medname" name="medname" style="width:100%;padding:8px;">
    <option value="">Select medicine…</option>
    <?php
      $prev = $_POST['medname'] ?? '';
      if ($r3 = $conn->query("SELECT med_name FROM meds ORDER BY med_name ASC")) {
        while ($r = $r3->fetch_assoc()) {
          $name = $r['med_name'];
          $sel  = ($prev === $name) ? ' selected' : '';
          echo '<option'.$sel.'>'.htmlspecialchars($name).'</option>';
        }
        $r3->close();
      }
    ?>
  </select>
  <div style="margin-top:8px;">
    <button type="submit" name="search">Search</button>
  </div>
</form>

<!-- Details + add item -->
<div class="card" style="max-width:1040px;margin:18px auto;">
  <form method="post" style="display:block;">
    <div class="row" style="display:flex;gap:22px;flex-wrap:wrap;">
      <div style="flex:1 1 280px;min-width:260px;">
        <label>Medicine ID:</label>
        <input type="number" name="medid" value="<?= htmlspecialchars($rowMed[0]) ?>" readonly>
      </div>
      <div style="flex:1 1 280px;min-width:260px;">
        <label>Category:</label>
        <input type="text" name="mcat" value="<?= htmlspecialchars($rowMed[3]) ?>" readonly>
      </div>
      <div style="flex:1 1 280px;min-width:260px;">
        <label>Quantity Available:</label>
        <input type="number" name="mqty" value="<?= htmlspecialchars($rowMed[2]) ?>" readonly>
      </div>

      <div style="flex:1 1 280px;min-width:260px;">
        <label>Medicine Name:</label>
        <input type="text" name="mdname" value="<?= htmlspecialchars($rowMed[1]) ?>" readonly>
      </div>
      <div style="flex:1 1 280px;min-width:260px;">
        <label>Location:</label>
        <input type="text" name="mloc" value="<?= htmlspecialchars($rowMed[5]) ?>" readonly>
      </div>
      <div style="flex:1 1 280px;min-width:260px;">
        <label>Price of One Unit:</label>
        <input type="number" name="mprice" step="0.01" value="<?= htmlspecialchars($rowMed[4]) ?>" readonly>
      </div>

      <div style="flex:1 1 420px;min-width:320px;">
        <label>Quantity Required:</label>
        <input type="number" name="mcqty" min="1" placeholder="Enter quantity…">
      </div>
      <div style="align-self:flex-end;">
        <button type="submit" name="add">Add Medicine</button>
      </div>
    </div>

    <?php if ($add_msg) echo '<div class="msg">'.$add_msg.'</div>'; ?>
  </form>
</div>

<script>
  // sidenav toggles
  var dropdown = document.getElementsByClassName("dropdown-btn");
  for (var i=0;i<dropdown.length;i++){
    dropdown[i].addEventListener("click", function(){
      this.classList.toggle("active");
      var c=this.nextElementSibling;
      c.style.display = (c.style.display==="block") ? "none" : "block";
    });
  }
</script>
</body>
</html>
