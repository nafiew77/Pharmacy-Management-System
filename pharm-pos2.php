<?php
/* pharm-pos2.php — Sales invoice / complete order */
session_start();
require_once "config.php";

/* Require a logged-in user */
if (empty($_SESSION['user'])) {
  header("Location: login.php");
  exit;
}

/* Resolve sale id from querystring (preferred) or session */
$sid = isset($_GET['sid']) ? (int)$_GET['sid'] : (int)($_SESSION['current_sale_id'] ?? 0);
if ($sid <= 0) {
  header("Location: pharm-pos1.php");
  exit;
}

/* Employee name for top bar */
$ename = '';
if ($stmt = $conn->prepare("SELECT E_FNAME FROM EMPLOYEE WHERE E_ID=?")) {
  $stmt->bind_param("s", $_SESSION['user']);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($row = $res->fetch_row()) $ename = $row[0];
  $stmt->close();
}

/* -------- Delete a line item (and restock) -------- */
if (isset($_GET['del'])) {
  $mid = (int) $_GET['del'];

  $conn->begin_transaction();
  try {
    /* lock the row and read qty */
    $stmt = $conn->prepare(
      "SELECT sale_qty FROM sales_items WHERE sale_id=? AND med_id=? FOR UPDATE"
    );
    $stmt->bind_param("ii", $sid, $mid);
    $stmt->execute();
    $line = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($line) {
      $qty = (int)$line['sale_qty'];

      $del = $conn->prepare("DELETE FROM sales_items WHERE sale_id=? AND med_id=?");
      $del->bind_param("ii", $sid, $mid);
      $del->execute();
      $del->close();

      $upd = $conn->prepare("UPDATE meds SET med_qty = med_qty + ? WHERE med_id=?");
      $upd->bind_param("ii", $qty, $mid);
      $upd->execute();
      $upd->close();
    }

    $conn->commit();
  } catch (Throwable $e) {
    $conn->rollback();
  }

  header("Location: pharm-pos2.php?sid={$sid}&msg=removed");
  exit;
}

/* -------- Complete order (write full timestamp) -------- */
if (isset($_POST['complete'])) {
  /* compute grand total from items */
  $sum = 0.0;
  $sumq = $conn->prepare("SELECT COALESCE(SUM(tot_price),0) FROM sales_items WHERE sale_id=?");
  $sumq->bind_param("i", $sid);
  $sumq->execute();
  $sumq->bind_result($sum);
  $sumq->fetch();
  $sumq->close();

  if ($sum <= 0) {
    header("Location: pharm-pos2.php?sid={$sid}&msg=empty");
    exit;
  }

  /* set total and a full datetime in s_date */
  $upd = $conn->prepare("UPDATE sales SET total_amt=?, s_date=NOW() WHERE sale_id=?");
  $upd->bind_param("di", $sum, $sid);

  if ($upd->execute()) {
    /* clear session pointer to this sale */
    if ((int)($_SESSION['current_sale_id'] ?? 0) === $sid) {
      unset($_SESSION['current_sale_id']);
    }
    header("Location: pharm-pos2.php?sid={$sid}&msg=completed");
    exit;
  } else {
    header("Location: pharm-pos2.php?sid={$sid}&msg=error");
    exit;
  }
}

/* -------- Load lines for the invoice -------- */
$rows  = [];
$grand = 0.0;

$q = "SELECT si.med_id, m.med_name, si.sale_qty, m.med_price, si.tot_price
      FROM sales_items si
      JOIN meds m ON m.med_id = si.med_id
      WHERE si.sale_id = ?
      ORDER BY si.med_id ASC";
$st = $conn->prepare($q);
$st->bind_param("i", $sid);
$st->execute();
$rs = $st->get_result();
while ($r = $rs->fetch_assoc()) {
  $rows[] = $r;
  $grand += (float)$r['tot_price'];
}
$st->close();
$has_items = !empty($rows);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Sales Invoice</title>
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <link rel="stylesheet" href="nav2.css?v=12">
  <link rel="stylesheet" href="table1.css?v=12">
  <style>
    .actions .del-btn{background:#8b0000;color:#fff;padding:8px 14px;border-radius:6px;text-decoration:none}
    .actions .del-btn:hover{opacity:.9}
    .footer-actions{
      width:78%; float:right; margin:16px 100px 40px 0;
      display:flex; gap:12px; justify-content:flex-end;
    }
    .btn{padding:10px 16px;border-radius:8px;border:none;text-decoration:none;cursor:pointer;display:inline-block}
    .btn-primary{background:#0ea5e9;color:#fff}
    .btn-success{background:#16a34a;color:#fff}
    #table1{margin-right:100px;}
  </style>
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
  <a href="logout1.php">Logout (signed in as <?= htmlspecialchars($ename ?: 'User') ?>)</a>
</div>

<center><div class="head"><h2>SALES INVOICE</h2></div></center>

<table align="right" id="table1">
  <tr>
    <th>Medicine ID</th>
    <th>Medicine Name</th>
    <th>Quantity</th>
    <th>Price</th>
    <th>Total Price</th>
    <th>Action</th>
  </tr>

  <?php if (!$rows): ?>
    <tr><td colspan="6" style="text-align:center;">No items in this sale.</td></tr>
  <?php else: foreach ($rows as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['med_id']) ?></td>
      <td><?= htmlspecialchars($r['med_name']) ?></td>
      <td><?= htmlspecialchars($r['sale_qty']) ?></td>
      <td><?= number_format((float)$r['med_price'], 2) ?></td>
      <td><?= number_format((float)$r['tot_price'], 2) ?></td>
      <td class="actions" style="text-align:center;">
        <a class="del-btn"
           href="pharm-pos2.php?sid=<?= $sid ?>&del=<?= (int)$r['med_id'] ?>"
           onclick="return confirm('Delete this item?');">Delete</a>
      </td>
    </tr>
  <?php endforeach; ?>
    <tr>
      <td colspan="4" style="text-align:right;font-weight:600;">Grand Total</td>
      <td><?= "Tk ".number_format($grand, 2) ?></td>
      <td></td>
    </tr>
  <?php endif; ?>
</table>

<div class="footer-actions">
  <a class="btn btn-primary" href="pharm-pos1.php">Go Back to Sales</a>
  <?php if ($has_items): ?>
    <form method="post" onsubmit="return confirm('Complete this order?');" style="margin:0;">
      <input type="hidden" name="complete" value="1">
      <button class="btn btn-success" type="submit">Complete Order</button>
    </form>
  <?php endif; ?>
</div>

<script>
  // sidebar dropdown toggles
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
