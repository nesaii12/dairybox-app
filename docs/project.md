# DairyBox Web-Based Ordering System and Sales & Inventory Management System

## Project Overview

Develop a modern, responsive, and secure web-based Ordering, Sales, Inventory, and Delivery Management System named **DairyBox** for a dairy business. The system will automate product ordering, inventory monitoring, sales tracking, delivery management, forecasting, and reporting.

### Technology Stack

* Framework: Laravel 12 (PHP)
* Frontend: Bootstrap 5, HTML5, CSS3, JavaScript
* Backend: Laravel MVC Architecture
* Database: MySQL
* Authentication: Laravel Authentication & Middleware
* Charts & Analytics: Chart.js
* Notifications: Laravel Notifications
* QR Code Generation: Simple QRCode Package
* Image Uploads: Laravel Storage
* Reports Export: PDF and Excel

---

# User Roles

## 1. Administrator

Full system management access.

### Features

#### Dashboard

* Total Sales
* Total Orders
* Total Customers
* Total Products
* Low Stock Alerts
* Near Expiration Alerts
* Monthly Sales Graph
* Best Selling Products
* Recent Orders

#### Product Management

* Add Product
* Edit Product
* Delete Product
* Upload Product Images
* Product Categories

  * Flavored Milk
  * Ice Cream
* Product Sizes

  * Small
  * Medium
  * Large
* Milk Type

  * Carabao Milk
  * Cow Milk

#### Inventory Management

* Current Stock Monitoring
* Stock In
* Stock Out
* Inventory Adjustment
* Expiration Date Monitoring
* Automatic Stock Updates
* FIFO Inventory Logic
* Low Stock Notifications

#### Order Management

* View Orders
* Approve Orders
* Reject Orders
* Update Order Status:

  * Pending
  * Confirmed
  * Preparing
  * Ready for Pickup
  * Out for Delivery
  * Delivered
  * Cancelled

#### Customer Management

* View Customers
* Manage Accounts
* View Customer Purchase History
* View Ratings and Feedback

#### Delivery Management

* Assign Rider
* Monitor Delivery Status
* Handle Cancellation Requests
* Restrict Customer for 3 Days if Abuse is Verified

#### Sales Management

* Daily Sales
* Weekly Sales
* Monthly Sales
* Annual Sales
* Best-Selling Products
* Revenue Tracking

#### Reports

* Sales Reports
* Inventory Reports
* Delivery Reports
* Customer Reports
* Export PDF
* Export Excel

#### Forecasting Module

* Analyze Historical Sales
* Monthly Demand Forecast
* Seasonal Sales Forecast
* Product Demand Prediction

---

## 2. Customer

### Features

#### Authentication

* Register
* Login
* Forgot Password
* Profile Management

#### Product Browsing

* Product Catalog
* Search Products
* Filter Products
* Product Details

#### Shopping Cart

* Add to Cart
* Update Quantity
* Remove Item
* View Total Amount

#### Ordering System

Customers can:

* Select Product
* Choose Milk Type

  * Carabao
  * Cow
* Choose Size

  * Small
  * Medium
  * Large
* Select Quantity
* Choose:

  * Delivery
  * Pickup
* Select Schedule Date
* Select Pickup Time

#### Order Tracking

* View Order Status
* Track Current Orders
* Order History

#### Notifications

* Order Confirmed
* Order Preparing
* Out for Delivery
* Delivered

#### Product Ratings

* 1 to 5 Star Rating
* Customer Reviews
* Feedback Submission

#### QR Code Access

* Scan QR Code
* Redirect to Product Catalog

---

## 3. Delivery Rider

### Rider Dashboard

#### Features

* Assigned Deliveries
* Delivery Queue
* Update Delivery Status
* Delivery History

#### View Only

* Customer Name
* Contact Number
* Delivery Address
* Order Amount
* Delivery Status

#### Delivery Status

* Out for Delivery
* Delivered
* Failed Delivery
* Cancelled

#### Notifications

* New Delivery Assignment
* Delivery Updates

---

# Database Design

## users

* id
* role
* name
* email
* phone
* password
* status
* created_at

## categories

* id
* category_name

## products

* id
* category_id
* product_name
* description
* milk_type
* size
* price
* image
* stock
* expiration_date
* status

## inventories

* id
* product_id
* stock_in
* stock_out
* remaining_stock
* remarks

## carts

* id
* customer_id

## cart_items

* id
* cart_id
* product_id
* quantity
* subtotal

## orders

* id
* order_number
* customer_id
* rider_id
* order_type
* delivery_address
* schedule_date
* schedule_time
* subtotal
* delivery_fee
* total_amount
* order_status

## order_items

* id
* order_id
* product_id
* quantity
* price
* subtotal

## deliveries

* id
* order_id
* rider_id
* delivery_status
* delivered_at

## sales

* id
* order_id
* total_amount
* sales_date

## ratings

* id
* customer_id
* product_id
* rating
* review

## notifications

* id
* user_id
* title
* message
* is_read

## forecasts

* id
* product_id
* forecast_month
* predicted_sales

---

# Laravel Modules Structure

## Admin Module

* Dashboard
* Products
* Categories
* Inventory
* Orders
* Deliveries
* Customers
* Reports
* Forecasting
* Settings

## Customer Module

* Home
* Products
* Cart
* Checkout
* Orders
* Notifications
* Ratings
* Profile

## Rider Module

* Dashboard
* Deliveries
* Notifications
* Delivery History

---

# User Interface Design

### Theme

* White
* Cream
* Light Blue

### Design Style

* Clean
* Modern
* Mobile Responsive
* Dairy Business Branding

### Bootstrap Components

* Cards
* DataTables
* Modals
* Toast Notifications
* Offcanvas Sidebar
* Responsive Navbar
* Charts

---

# Security Requirements

* Role-Based Access Control
* Password Hashing
* CSRF Protection
* XSS Protection
* SQL Injection Prevention
* Laravel Middleware Authorization
* Activity Logs
* Secure File Upload Validation

---

# Additional Features

* QR Code Product Access
* Automatic Inventory Deduction
* Low Stock Alert
* Expiration Alert
* Sales Forecasting
* Audit Trail Logs
* Mobile Responsive Design
* PDF Report Generation
* Excel Report Export
* Real-Time Dashboard Statistics

Build the system following Laravel MVC architecture, using Bootstrap 5 for the frontend and MySQL for database management. The application must be responsive, secure, scalable, and suitable for deployment on shared hosting or cloud hosting environments.
