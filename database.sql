-- Create database
CREATE DATABASE IF NOT EXISTS noor_auto_scrap;
USE noor_auto_scrap;

-- Admin table
CREATE TABLE tbl_admin (
    admin_id INT AUTO_INCREMENT PRIMARY KEY,
    admin_fname VARCHAR(50) NOT NULL,
    admin_lname VARCHAR(50) NOT NULL,
    admin_email VARCHAR(100) NOT NULL UNIQUE,
    admin_phone VARCHAR(20) NOT NULL,
    admin_password VARCHAR(255) NOT NULL,
    admin_status ENUM('active', 'inactive') DEFAULT 'active',
    admin_created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    admin_last_login_date DATETIME
);

-- Brand table
CREATE TABLE tbl_brand (
    brand_id INT AUTO_INCREMENT PRIMARY KEY,
    brand_name VARCHAR(100) NOT NULL,
    brand_image VARCHAR(255),
    brand_status ENUM('active', 'inactive') DEFAULT 'active',
    brand_created_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Model table
CREATE TABLE tbl_model (
    model_id INT AUTO_INCREMENT PRIMARY KEY,
    model_brand_id INT NOT NULL,
    model_name VARCHAR(100) NOT NULL,
    model_image VARCHAR(255),
    model_status ENUM('active', 'inactive') DEFAULT 'active',
    model_created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (model_brand_id) REFERENCES tbl_brand(brand_id)
);

-- Category table
CREATE TABLE tbl_category (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    category_image VARCHAR(255),
    category_status ENUM('active', 'inactive') DEFAULT 'active',
    category_created_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Warehouse table
CREATE TABLE tbl_warehouse (
    warehouse_id INT AUTO_INCREMENT PRIMARY KEY,
    warehouse_name VARCHAR(100) NOT NULL,
    warehouse_address TEXT NOT NULL,
    warehouse_status ENUM('active', 'inactive') DEFAULT 'active',
    warehouse_created_date DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Parts table
CREATE TABLE tbl_part (
    part_id INT AUTO_INCREMENT PRIMARY KEY,
    part_category_id INT NOT NULL,
    part_name VARCHAR(100) NOT NULL,
    part_image VARCHAR(255),
    part_status ENUM('active', 'inactive') DEFAULT 'active',
    part_created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (part_category_id) REFERENCES tbl_category(category_id)
);

-- Cars table
CREATE TABLE tbl_car (
    car_id INT AUTO_INCREMENT PRIMARY KEY,
    car_warehouse_id INT NOT NULL,
    car_brand_id INT NOT NULL,
    car_model_id INT NOT NULL,
    car_year YEAR NOT NULL,
    car_vin VARCHAR(17) NOT NULL UNIQUE,
    car_reg_number VARCHAR(20) NOT NULL,
    car_image VARCHAR(255),
    car_created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (car_warehouse_id) REFERENCES tbl_warehouse(warehouse_id),
    FOREIGN KEY (car_brand_id) REFERENCES tbl_brand(brand_id),
    FOREIGN KEY (car_model_id) REFERENCES tbl_model(model_id)
);

-- Stock/Inventory table
CREATE TABLE tbl_stock (
    stock_id INT AUTO_INCREMENT PRIMARY KEY,
    stock_car_id INT NOT NULL,
    stock_part_id INT NOT NULL,
    stock_status ENUM('in_stock', 'sold') DEFAULT 'in_stock',
    stock_exchange_received ENUM('yes', 'no') DEFAULT 'no',
    stock_created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    stock_customer VARCHAR(100),
    stock_customer_mobileno VARCHAR(20),
    stock_sold_date DATETIME,
    FOREIGN KEY (stock_car_id) REFERENCES tbl_car(car_id),
    FOREIGN KEY (stock_part_id) REFERENCES tbl_part(part_id)
);
