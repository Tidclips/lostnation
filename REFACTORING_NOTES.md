# Project Refactoring Summary

## What changed

This project was cleaned up into a single Lostnation storefront app.

### Current stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Database: MySQL for catalog and orders
- Local server: XAMPP or PHP built-in server

### Current project structure

lostnation/
├── public/
│   ├── index.html
│   ├── dashboard.html
│   ├── css/
│   └── js/
├── php/
│   ├── config.php
│   ├── payments/
│   └── ...
├── mysql/
│   └── database.sql
├── README.md
├── SETUP_GUIDE.md
└── REFACTORING_NOTES.md

## Quick start

1. Start Apache in XAMPP, or run the PHP built-in server.
2. Open the storefront in a browser:
   http://localhost/lostnation/public/index.html
3. Use the checkout flow to test the purchase simulation.

## Notes

- The app is intentionally a storefront.
- Legacy portal artifacts were retired from the active project.
- The MySQL schema is aligned with product and order data for the Lostnation brand.
