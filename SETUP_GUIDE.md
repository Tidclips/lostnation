# Lostnation Storefront Setup

A clean storefront app for the Lostnation streetwear brand, built with PHP, HTML, CSS, and JavaScript.

## Quick start

1. Start XAMPP and make sure Apache is running.
2. Place the project inside your XAMPP web folder, for example:
   C:\xampp\htdocs\lostnation
3. Open the app in your browser:
   http://localhost/lostnation/public/index.html

## Tech stack

- Frontend: HTML, CSS, JavaScript
- Backend: PHP
- Local server: XAMPP or PHP built-in server
- Database: MySQL for catalog and order tracking

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
├── SETUP_GUIDE.md
└── REFACTORING_NOTES.md

## Local run

Use the PHP built-in server from the project root:

C:\xampp\php\php.exe -S localhost:8000

Then open:

http://localhost:8000/public/index.html

## Notes

- This project is a storefront app for Lostnation.
- The payment endpoint is configured for local demo testing.
- The database schema in mysql/database.sql supports products and orders.

## Troubleshooting

- If the page does not load, confirm Apache is running.
- If the payment endpoint fails, verify the project is being served from the correct folder.
- If you want a richer catalog, expand the schema in mysql/database.sql and connect the PHP layer.



