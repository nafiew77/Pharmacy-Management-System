<?php
  include "config.php";

  if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $qry1 = "SELECT * FROM employee WHERE e_id='$id'";
    $result = $conn->query($qry1);
    $row = $result->fetch_row();
  }
?>
<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- cache-bust while testing -->
  <link rel="stylesheet" type="text/css" href="nav2.css?v=2">
  <link rel="stylesheet" type="text/css" href="form4.css?v=2">
  <!-- OVERRIDES so the left-top title uses your text, not any injected content -->
  <style>
    .sidenav h2::before,
    .sidenav h2::after,
    .sidenav::before { content: none !important; }
    .sidenav h2{
      font-family: Arial, sans-serif !important;
      color: #fff !important;
      text-align: center !important;
      margin: 16px 0 !important;
      padding: 0 12px !important;
      font-weight: 700;
      letter-spacing: .4px;
      line-height: 1.2;
      white-space: normal;
    }
  </style>
  <title>Employees</title>
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
      <h2>UPDATE EMPLOYEE DETAILS</h2>
    </div>
  </center>

  <div class="one">
    <div class="row">
      <?php
        if (isset($_POST['update'])) {
          $id    = mysqli_real_escape_string($conn, $_REQUEST['eid']);
          $fname = mysqli_real_escape_string($conn, $_REQUEST['efname']);
          $lname = mysqli_real_escape_string($conn, $_REQUEST['elname']);
          $bdate = mysqli_real_escape_string($conn, $_REQUEST['ebdate']);
          $age   = mysqli_real_escape_string($conn, $_REQUEST['eage']);
          $sex   = mysqli_real_escape_string($conn, $_REQUEST['esex']);
          $etype = mysqli_real_escape_string($conn, $_REQUEST['etype']);
          $jdate = mysqli_real_escape_string($conn, $_REQUEST['ejdate']);
          $sal   = mysqli_real_escape_string($conn, $_REQUEST['esal']);
          $phno  = mysqli_real_escape_string($conn, $_REQUEST['ephno']);
          $mail  = mysqli_real_escape_string($conn, $_REQUEST['e_mail']);
          $add   = mysqli_real_escape_string($conn, $_REQUEST['eadd']);

          $sql = "UPDATE employee
                  SET e_fname='$fname', e_lname='$lname', bdate='$bdate', e_age='$age',
                      e_sex='$sex', e_type='$etype', e_jdate='$jdate', e_sal='$sal',
                      e_phno='$phno', e_mail='$mail', e_add='$add'
                  WHERE e_id='$id'";

          if ($conn->query($sql)) {
            header('Location: employee-view.php');
            exit;
          } else {
            echo "<p style='font-size:14px; color:red;'>Error! Unable to update.</p>";
          }
        }
      ?>

      <form action="<?= $_SERVER['PHP_SELF'] ?>" method="post">
        <div class="column">
          <p>
            <label for="eid">Employee ID:</label><br>
            <input type="number" name="eid" id="eid" value="<?php echo $row[0]; ?>" readonly>
          </p>
          <p>
            <label for="efname">First Name:</label><br>
            <input type="text" name="efname" id="efname" value="<?php echo $row[1]; ?>">
          </p>
          <p>
            <label for="elname">Last Name:</label><br>
            <input type="text" name="elname" id="elname" value="<?php echo $row[2]; ?>">
          </p>
          <p>
            <label for="ebdate">Date of Birth:</label><br>
            <input type="date" name="ebdate" id="ebdate" value="<?php echo $row[3]; ?>">
          </p>
          <p>
            <label for="eage">Age:</label><br>
            <input type="number" name="eage" id="eage" value="<?php echo $row[4]; ?>">
          </p>
          <p>
            <label for="esex">Sex:</label><br>
            <input type="text" name="esex" id="esex" value="<?php echo $row[5]; ?>">
          </p>
        </div>

        <div class="column">
          <p>
            <label for="etype">Employee Type:</label><br>
            <input type="text" name="etype" id="etype" value="<?php echo $row[6]; ?>">
          </p>
          <p>
            <label for="ejdate">Date of Joining:</label><br>
            <input type="date" name="ejdate" id="ejdate" value="<?php echo $row[7]; ?>">
          </p>
          <p>
            <label for="esal">Salary:</label><br>
            <input type="number" step="0.01" name="esal" id="esal" value="<?php echo $row[8]; ?>">
          </p>
          <p>
            <label for="ephno">Phone Number:</label><br>
            <input type="number" name="ephno" id="ephno" value="<?php echo $row[9]; ?>">
          </p>
          <p>
            <label for="e_mail">Email ID:</label><br>
            <input type="email" name="e_mail" id="e_mail" value="<?php echo $row[10]; ?>">
          </p>
          <p>
            <label for="eadd">Address:</label><br>
            <input type="text" name="eadd" id="eadd" value="<?php echo $row[11]; ?>">
          </p>
        </div>

        <input type="submit" name="update" value="Update">
      </form>
    </div>
  </div>

  <script>
    var dropdown = document.getElementsByClassName("dropdown-btn");
    for (var i = 0; i < dropdown.length; i++) {
      dropdown[i].addEventListener("click", function () {
        this.classList.toggle("active");
        var dropdownContent = this.nextElementSibling;
        dropdownContent.style.display =
          dropdownContent.style.display === "block" ? "none" : "block";
      });
    }
  </script>
</body>
</html>
