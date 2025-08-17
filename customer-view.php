<!DOCTYPE html>
<html>
<head>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" type="text/css" href="nav2.css?v=2">
  <link rel="stylesheet" type="text/css" href="table1.css?v=2">
  <style>
    .sidenav h2::before, .sidenav h2::after, .sidenav::before { content: none !important; }
    .sidenav h2{
      font-family: Arial, sans-serif !important;
      color: #fff !important;
      text-align: center !important;
      margin: 16px 0 !important;
      padding: 0 12px !important;
      font-weight: 700;
      letter-spacing: .4px;
      line-height: 1.2;
    }
  </style>
  <title>Customers</title>
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
      <h2>CUSTOMER LIST</h2>
    </div>
  </center>

  <table align="right" id="table1" style="margin-right:20px;">
    <tr>
      <th>Customer ID</th>
      <th>First Name</th>
      <th>Last Name</th>
      <th>Age</th>
      <th>Sex</th>
      <th>Phone Number</th>
      <th>Email Address</th>
      <th>Action</th>
    </tr>

    <?php
      include "config.php";
      // Adjust column names if your table uses different ones.
      $sql = "SELECT c_id, c_fname, c_lname, c_age, c_sex, c_phno, c_mail FROM customer";
      $result = $conn->query($sql);

      if ($result && $result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
          echo "<tr>";
            echo "<td>".htmlspecialchars($row['c_id'])."</td>";
            echo "<td>".htmlspecialchars($row['c_fname'])."</td>";
            echo "<td>".htmlspecialchars($row['c_lname'])."</td>";
            echo "<td>".htmlspecialchars($row['c_age'])."</td>";
            echo "<td>".htmlspecialchars($row['c_sex'])."</td>";
            echo "<td>".htmlspecialchars($row['c_phno'])."</td>";
            echo "<td>".htmlspecialchars($row['c_mail'])."</td>";
            echo "<td align='center'>";
              echo "<a class='button1 edit-btn' href='customer-update.php?id=".urlencode($row['c_id'])."'>Edit</a> ";
              echo "<a class='button1 del-btn' href='customer-delete.php?id=".urlencode($row['c_id'])."' onclick=\"return confirm('Are you sure to delete?');\">Delete</a>";
            echo "</td>";
          echo "</tr>";
        }
      } else {
        echo "<tr><td colspan='8' style='text-align:center;'>No customers found.</td></tr>";
      }
      $conn->close();
    ?>
  </table>

  <script>
    var dropdown = document.getElementsByClassName("dropdown-btn");
    for (var i = 0; i < dropdown.length; i++) {
      dropdown[i].addEventListener("click", function() {
        this.classList.toggle("active");
        var dropdownContent = this.nextElementSibling;
        dropdownContent.style.display =
          dropdownContent.style.display === "block" ? "none" : "block";
      });
    }
  </script>
</body>
</html>
