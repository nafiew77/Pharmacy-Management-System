TYPE=TRIGGERS
triggers='CREATE DEFINER=`root`@`localhost` TRIGGER `SALE_ID_DELETE` BEFORE DELETE ON `sales` FOR EACH ROW BEGIN\nDELETE from sales_items WHERE sales_items.SALE_ID=old.SALE_ID;\nEND'
sql_modes=524288
definers='root@localhost'
client_cs_names='utf8mb4'
connection_cl_names='utf8mb4_general_ci'
db_cl_names='utf8mb4_general_ci'
created=1755091558901766
