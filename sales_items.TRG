TYPE=TRIGGERS
triggers='CREATE DEFINER=`root`@`localhost` TRIGGER `SALEINSERT` AFTER INSERT ON `sales_items` FOR EACH ROW BEGIN\nUPDATE meds SET MED_QTY=MED_QTY-new.SALE_QTY WHERE meds.MED_ID=new.MED_ID;\nEND' 'CREATE DEFINER=`root`@`localhost` TRIGGER `SALEDELETE` AFTER DELETE ON `sales_items` FOR EACH ROW BEGIN\nUPDATE meds SET MED_QTY=MED_QTY+old.SALE_QTY WHERE meds.MED_ID=old.MED_ID;\nEND'
sql_modes=524288 524288
definers='root@localhost' 'root@localhost'
client_cs_names='utf8mb4' 'utf8mb4'
connection_cl_names='utf8mb4_general_ci' 'utf8mb4_general_ci'
db_cl_names='utf8mb4_general_ci' 'utf8mb4_general_ci'
created=1755091558919208 1755091558913786
