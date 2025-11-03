@echo off
echo Running Database Update...
cd /d C:\xampp\mysql\bin
type "C:\xampp\htdocs\mov\database\update-products-table.sql" | mysql.exe -u root sasto_hub
echo Database updated successfully!
pause
