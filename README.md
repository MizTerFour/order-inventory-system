# Web-based Order & Inventory Management System

A full-stack web application built with **PHP, MySQL, JavaScript (AJAX), and Bootstrap** to streamline retail store operations, manage product inventory, track customer orders, and generate automated PDF invoices/reports.

## Key Features
- **Order & Inventory Tracking:** Real-time stock calculation and supplier tracking.
- **Asynchronous Status Updates:** Interactive order status changes using JavaScript & AJAX without page refreshes.
- **Automated Document Generation:** Dynamic creation of official receipts and monthly reports using **mPDF** with custom Thai typography (THSarabunNew).
- **Payment Slip Handling:** File upload management for proof of transfer verification.
- **Database Migrations:** Automated schema versioning and setup powered by **Phinx**.

## Tech Stack
- **Backend:** PHP (Native / Procedural & OOP)
- **Database & Tooling:** MySQL, Phinx (Database Migrations), Composer
- **Frontend:** HTML5, CSS3, JavaScript (AJAX), Bootstrap
- **Libraries:** mPDF

## Installation & Setup
1. Clone the repository:
   ```bash
    git clone https://github.com/MizTerFour/order-inventory-system.git

    cd order-inventory-system

    composer install

    vendor/bin/phinx migrate