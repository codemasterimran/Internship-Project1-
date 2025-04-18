# 📂 SQL File Uploader & Executor – PHP Web App

This PHP web application allows you to upload `.sql` files and automatically execute them to create tables or insert data into your MySQL database. Perfect for quickly importing database structures and data without manually using phpMyAdmin.

## 💡 Features

- Upload `.sql` files through a simple UI
- Automatically execute and import SQL commands to your MySQL DB
- Built with PHP & runs seamlessly on **XAMPP**
- Easy-to-use and lightweight setup

---

## ⚙️ Requirements

- PHP 7.x or higher
- MySQL (via XAMPP)
- XAMPP (for local server environment)
- Web browser

---

## 🚀 Getting Started with XAMPP

### 1. 🧰 Install XAMPP

If you don’t have XAMPP installed:
- [Download XAMPP](https://www.apachefriends.org/index.html)
- Install and launch the **XAMPP Control Panel**

### 2. 🔥 Start Apache & MySQL

In the XAMPP Control Panel:
- Click **Start** for both **Apache** and **MySQL**

### 3. 📁 Move Project to `htdocs`

1. Copy or clone this project into the `htdocs` folder:

2. (Optional) Create a new database in MySQL:
- Go to [http://localhost/phpmyadmin](http://localhost/phpmyadmin)
- Click **New** > Enter database name (e.g., `mydatabase`) > Click **Create**

### 4. 🔧 Update DB Config

Inside your PHP project (e.g., `config.php` or `db.php`), update your DB credentials:

```php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'mydatabase';


📤 How to Use the App
Open your browser and go to:

arduino
Copy
Edit
http://localhost/sql-uploader
Use the upload form to select a .sql file

Click Upload & Execute

The SQL commands will be run on your configured database, and you’ll see success/failure messages
📎 Example SQL File
Here’s a sample .sql file you can try:

sql
Copy
Edit
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100)
);

INSERT INTO users (name, email) VALUES ('Alice', 'alice@example.com');
📦 Folder Structure
pgsql
Copy
Edit
sql-uploader/
├── index.php
├── upload.php
├── db.php
└── uploads/
index.php – Upload form

upload.php – Handles file upload & SQL execution

db.php – Database connection

uploads/ – Stores uploaded files temporarily

🛡️ Security Tips (for production)
Restrict file types (only allow .sql)

Add authentication to prevent unauthorized access

Use prepared statements if parsing input-based SQL

Delete uploaded files after execution



🙌 Credits
Developed by Imran Wani
