CREATE DATABASE IF NOT EXISTS lostnation;
USE lostnation;

CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    color VARCHAR(100),
    image_url TEXT,
    stock INT DEFAULT 0,
    is_featured TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_email VARCHAR(255) NOT NULL,
    payment_reference VARCHAR(100) NOT NULL UNIQUE,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('paid', 'pending', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO products (name, slug, description, price, color, image_url, stock, is_featured)
VALUES
('Drifter graphic tee', 'drifter-graphic-tee', 'A relaxed heavyweight tee with a clean graphic finish. Cut for everyday movement.', 42000, 'White', 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?auto=format&fit=crop&w=700&q=85', 12, 1),
('Coordinates tee', 'coordinates-tee', 'A soft relaxed-fit tee with a bold screen print and an easy everyday silhouette.', 45000, 'Black', 'https://images.unsplash.com/photo-1576566588028-4147f3842f27?auto=format&fit=crop&w=700&q=85', 9, 1),
('Signal tee', 'signal-tee', 'An understated natural cotton tee with a roomy fit and a soft hand feel.', 40000, 'Natural', 'https://images.unsplash.com/photo-1562157873-818bc0726f68?auto=format&fit=crop&w=700&q=85', 15, 1);
