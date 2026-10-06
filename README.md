# Lostnation

A clean storefront app for the Lostnation streetwear brand.

## Quick start

1. Start XAMPP and make sure Apache is running.
2. Place this project in your XAMPP web folder, for example:
   C:\xampp\htdocs\lostnation
3. Open the app in your browser:
   http://localhost/lostnation/public/index.html

## Features
- Product grid and product detail modal
- Quick-add bag interactions
- Checkout form and payment flow
- Responsive storefront layout
- Lightweight PHP backend for local demo use

## Project structure

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
├── index.php
├── README.md
└── SETUP_GUIDE.md

## Local run

Use the PHP built-in server from the project root:

C:\xampp\php\php.exe -S localhost:8000

Then open:

http://localhost:8000/public/index.html

## Notes
- This app is a storefront for Lostnation, not a school portal.
- The payment endpoint is configured for local storefront testing.
- If you want a real database-backed catalog later, the schema in mysql/database.sql can be expanded.
