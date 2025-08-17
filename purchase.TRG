TYPE=TRIGGERS
triggers='CREATE DEFINER=`root`@`localhost` TRIGGER `QTYINSERT` AFTER INSERT ON `purchase` FOR EACH ROW BEGIN\nUPDATE meds SET MED_QTY=MED_QTY+new.P_QTY WHERE meds.MED_ID=new.MED_ID;\nEND' 'CREATE DEFINER=`root`@`localhost` TRIGGER `QTYUPDATE` AFTER UPDATE ON `purchase` FOR EACH ROW BEGIN\nUPDATE meds SET MED_QTY=MED_QTY-old.P_QTY WHERE meds.MED_ID=new.MED_ID;\nUPDATE meds SET MED_QTY=MED_QTY+new.P_QTY WHERE meds.MED_ID=new.MED_ID;\nEND' 'CREATE DEFINER=`root`@`localhost` TRIGGER `QTYDELETE` AFTER DELETE ON `purchase` FOR EACH ROW BEGIN\nUPDATE meds SET MED_QTY=MED_QTY-old.P_QTY WHERE meds.MED_ID=old.MED_ID;\nEND'
sql_modes=524288 524288 524288
definers='root@localhost' 'root@localhost' 'root@localhost'
client_cs_names='utf8mb4' 'utf8mb4' 'utf8mb4'
connection_cl_names='utf8mb4_general_ci' 'utf8mb4_general_ci' 'utf8mb4_general_ci'
db_cl_names='utf8mb4_general_ci' 'utf8mb4_general_ci' 'utf8mb4_general_ci'
created=1755091558870110 1755091558880381 1755091558863531
