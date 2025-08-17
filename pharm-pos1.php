<?php
session_start();
include "config.php";

/* ---------------- Top bar: employee name ---------------- */
$ename = '';
if (!empty($_SESSION['user'])) {
  $u = $conn->real_escape_string($_SESSION['user']);
  if ($res = $conn->query("SELECT E_FNAME FROM EMPLOYEE WHERE E_ID='$u'")) {
    if ($row = $res->fetch_row()) $ename = $row[0];
    $res->close();
  }

}

/* ---------------- Create/attach sale to selected customer ---------------- */
$cust_msg = '';
if (isset($_POST['custadd'])) {
  $cid = $_POST['cid'] ?? '0';
  if ($cid !== '0' && $cid !== '' && !empty($_SESSION['user'])) {
    $cid_esc = $conn->real_escape_string($cid);
    $uid_esc = $conn->real_escape_string($_SESSION['user']);
    $sql = "INSERT INTO sales (c_id, e_id) VALUES ('$cid_esc', '$uid_esc')";
    if ($conn->query($sql)) {
      $_SESSION['current_sale_id'] = (int)$conn->insert_id; // remember this sale
      $cust_msg = "<p style='font-size:14px;'>Customer added to current sale.</p>";
    } else {
      $cust_msg = "<p style='font-size:14px; color:red;'>Invalid! Enter valid Customer ID to record Sales.</p>";
    }
  } else {
    $cust_msg = "<p style='font-size:14px; color:red;'>Please select a valid customer first.</p>";
  }
}

/* ---------------- Load a medicine after search ---------------- */
$rowMed = null; // [0]=med_id, [1]=med_name, [2]=med_qty, [3]=category, [4]=med_price, [5]=location
if (isset($_POST['search']) && !empty($_POST['med'])) {
  $med = $conn->real_escape_string($_POST['med']);
  if ($res4 = $conn->query("SELECT * FROM meds WHERE med_name='$med'")) {
    $rowMed = $res4->fetch_row();
    $res4->close();
  }
}

/* ---------------- Add item to the current sale ---------------- */
$add_msg = '';
if (isset($_POST['add'])) {
  // Prefer sale id stored for this user/session
  $sid = isset($_SESSION['current_sale_id']) ? (int)$_SESSION['current_sale_id'] : 0;

  // Very last resort fallback (not ideal but keeps legacy flows alive)
  if ($sid <= 0) {
    $res5 = $conn->query("SELECT sale_id FROM sales ORDER BY sale_id DESC LIMIT 1");
    $sid  = ($res5 && $res5->num_rows) ? (int)$res5->fetch_row()[0] : 0;
    if ($res5) $res5->close();
  }

  $mid   = (int)($_POST['medid'] ?? 0);
  $aqty  = (int)($_POST['mqty'] ?? 0);          // shown quantity
  $qty   = (int)($_POST['mcqty'] ?? 0);         // requested quantity
  $price = (float)($_POST['mprice'] ?? 0.0);    // unit price

  if ($sid <= 0) {
    $add_msg = "<p style='font-size:14px; color:red;'>No active sale found. Select a customer first.</p>";
  } elseif ($mid <= 0 || $price <= 0) {
    $add_msg = "<p style='font-size:14px; color:red;'>Select a medicine first.</p>";
  } elseif ($qty <= 0) {
    $add_msg = "<p style='font-size:14px; color:red;'>Enter a valid quantity.</p>";
  } else {
    // Double-check LIVE stock from DB
    $chk = $conn->prepare("SELECT med_qty, med_price FROM meds WHERE med_id=?");
    $chk->bind_param("i", $mid);
    $chk->execute();
    $live = $chk->get_result()->fetch_assoc();
    $chk->close();

    if (!$live) {
      $add_msg = "<p style='font-size:14px; color:red;'>Medicine not found.</p>";
    } elseif ($qty > (int)$live['med_qty']) {
      $add_msg = "<p style='font-size:14px; color:red;'>Not enough stock. Available: ".(int)$live['med_qty']."</p>";
    } else {
      $line_total = $price * $qty;

      // UPSERT: if (sale_id, med_id) exists, increase qty & total instead of crashing
      $sql = "
        INSERT INTO sales_items (sale_id, med_id, sale_qty, tot_price)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
          sale_qty  = sale_qty  + VALUES(sale_qty),
          tot_price = tot_price + VALUES(tot_price)
      ";
      $ins = $conn->prepare($sql);
      $ins->bind_param("iiid", $sid, $mid, $qty, $line_total);

      if (!$ins->execute()) {
        $add_msg = "<p style='font-size:14px; color:red;'>Error adding item: ".htmlspecialchars($conn->error)."</p>";
      } else {
        // Decrement stock safely
        $upd = $conn->prepare("UPDATE meds SET med_qty = med_qty - ? WHERE med_id = ? AND med_qty >= ?");
        $upd->bind_param("iii", $qty, $mid, $qty);
        $upd->execute();
        $upd->close();

        $view_url = "pharm-pos2.php?sid=" . urlencode($sid);
        $add_msg  = "<p style='font-size:14px;'>Item added.</p>
                     <div style='text-align:center; margin-top:10px;'>
                       <a class='button1 view-btn' href='$view_url'>View Order</a>
                     </div>";
      }
      $ins->close();
    }
  }
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- keep orders: nav last -->
  <link rel="stylesheet" href="nav2.css?v=9">
  <link rel="stylesheet" href="form3.css?v=9">
  <link rel="stylesheet" href="table2.css?v=9">
  <style>
    .sidenav h2::before, .sidenav h2::after, .sidenav::before { content:none !important; }
    .sidenav h2{ font-family:Arial, sans-serif; color:#fff; text-align:center; margin:16px 0; padding:0 12px; font-weight:700; }
    .hint{font-size:12px;color:#666;margin-top:4px;}
  </style>
  <title>New Sales</title>
</head>
<body>

  <div class="sidenav">
    <h2>Pharmacy Management System</h2>
    <a href="pharmmainpage.php">Dashboard</a>
    <a href="pharm-inventory.php">View Inventory</a>
    <a href="pharm-pos1.php">Add New Sale</a>
    <button class="dropdown-btn">Customers <i class="down"></i></button>
    <div class="dropdown-container">
      <a href="pharm-customer.php">Add New Customer</a>
      <a href="pharm-customer-view.php">View Customers</a>
    </div>
  </div>

  <div class="topnav">
    <a href="logout1.php">Logout (signed in as <?= htmlspecialchars($ename) ?>)</a>
  </div>

  <center>
    <div class="head"><h2>POINT OF SALE</h2></div>
  </center>

  <!-- Select Customer (creates a new sale) -->
  <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
    <center>
      <select id="cid" name="cid" required>
        <option value="0" selected>*Select Customer ID (only once for a customer's sales)</option>
        <?php
          if ($result1 = $conn->query("SELECT c_id FROM customer")) {
            while ($row1 = $result1->fetch_assoc()) {
              echo "<option>".htmlspecialchars($row1['c_id'])."</option>";
            }
            $result1->close();
          }
        ?>
      </select>
      &nbsp;&nbsp;
      <input type="submit" name="custadd" value="Add to Proceed.">
    </center>
  </form>

  <?php if (!empty($cust_msg)) echo "<div style='text-align:center; margin-top:8px;'>$cust_msg</div>"; ?>

  <!-- Search Medicine -->
  <form method="post" style="text-align:center; margin-top:20px;">
    <select id="med" name="med" required>
      <option value="" selected>Select Medicine</option>
      <?php
        if ($result3 = $conn->query("SELECT med_name FROM meds ORDER BY med_name ASC")) {
          $prev = $_POST['med'] ?? '';
          while ($r = $result3->fetch_assoc()) {
            $name = $r['med_name'];
            $sel = ($prev === $name) ? 'selected' : '';
            echo "<option $sel>".htmlspecialchars($name)."</option>";
          }
          $result3->close();
        }
      ?>
    </select>
    &nbsp;&nbsp;
    <input type="submit" name="search" value="Search">
  </form>

  <br><br><br>

  <div class="one row" style="margin-right:160px;">
    <form method="post">
      <div class="column">
        <label for="medid">Medicine ID:</label>
        <input type="number" name="medid" value="<?= $rowMed ? htmlspecialchars($rowMed[0]) : '' ?>" readonly><br><br>

        <label for="mdname">Medicine Name:</label>
        <input type="text" name="mdname" value="<?= $rowMed ? htmlspecialchars($rowMed[1]) : '' ?>" readonly><br><br>
      </div>

      <div class="column">
        <label for="mcat">Category:</label>
        <input type="text" name="mcat" value="<?= $rowMed ? htmlspecialchars($rowMed[3]) : '' ?>" readonly><br><br>

        <label for="mloc">Location:</label>
        <input type="text" name="mloc" value="<?= $rowMed ? htmlspecialchars($rowMed[5]) : '' ?>" readonly><br><br>
      </div>

      <div class="column">
        <label for="mqty">Quantity Available:</label>
        <input type="number" name="mqty" value="<?= $rowMed ? htmlspecialchars($rowMed[2]) : '' ?>" readonly><br><br>

        <label for="mprice">Price of One Unit:</label>
        <input type="number" name="mprice" id="mprice" step="0.01" value="<?= $rowMed ? htmlspecialchars($rowMed[4]) : '' ?>" readonly>
        <div class="hint" id="unitPreview"></div>
        <br><br>
      </div>

      <label for="mcqty">Quantity Required:</label>
      <input type="number" name="mcqty" id="mcqty" min="1" oninput="updatePreviews()">
      &nbsp;&nbsp;&nbsp;
      <input type="submit" name="add" value="Add Medicine">

      <?php if (!empty($add_msg)) echo "<div style='margin-top:12px;'>$add_msg</div>"; ?>
    </form>
  </div>

  <script>
    function fmt(n){ var x=parseFloat(n); return isNaN(x)?'':'৳ '+x.toFixed(2); }
    function updatePreviews(){
      var p=parseFloat(document.getElementById('mprice').value||'0');
      document.getElementById('unitPreview').textContent = p ? ('Unit: '+fmt(p)) : '';
    }
    updatePreviews();

    // dropdown toggles
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
