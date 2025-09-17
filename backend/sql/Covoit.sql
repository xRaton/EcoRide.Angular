CREATE DATABASE IF NOT EXISTS covoiturage CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE covoiturage;

CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  pseudo VARCHAR(100) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('user','employee','admin') NOT NULL DEFAULT 'user',
  credits INT NOT NULL DEFAULT 20,
  suspended TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE vehicles (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  immatriculation VARCHAR(50),
  marque VARCHAR(100),
  modele VARCHAR(100),
  couleur VARCHAR(50),
  energie VARCHAR(50),
  seats INT DEFAULT 4,
  date_premiere_immat DATE DEFAULT NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE rides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  driver_id INT NOT NULL,
  vehicle_id INT NOT NULL,
  from_city VARCHAR(150) NOT NULL,
  to_city VARCHAR(150) NOT NULL,
  departure_time DATETIME NOT NULL,
  arrival_time DATETIME DEFAULT NULL,
  seats INT NOT NULL,
  seats_available INT NOT NULL,
  price DECIMAL(8,2) NOT NULL,
  is_ecolo TINYINT(1) NOT NULL DEFAULT 0,
  status ENUM('scheduled','started','finished','cancelled') NOT NULL DEFAULT 'scheduled',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (driver_id) REFERENCES users(id),
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id)
);

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ride_id INT NOT NULL,
  passenger_id INT NOT NULL,
  seats_booked INT NOT NULL,
  total_price DECIMAL(8,2) NOT NULL,
  platform_fee INT NOT NULL DEFAULT 2,
  status ENUM('pending','confirmed','completed','cancelled') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ride_id) REFERENCES rides(id),
  FOREIGN KEY (passenger_id) REFERENCES users(id)
);

CREATE TABLE reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ride_id INT NOT NULL,
  reviewer_id INT NOT NULL,
  target_user_id INT NOT NULL,
  note INT NOT NULL,
  commentaire TEXT,
  status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ride_id) REFERENCES rides(id),
  FOREIGN KEY (reviewer_id) REFERENCES users(id),
  FOREIGN KEY (target_user_id) REFERENCES users(id)
);

CREATE TABLE transactions (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NOT NULL,
  amount INT NOT NULL,
  reason VARCHAR(255),
  related_booking INT DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE incidents (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ride_id INT NOT NULL,
  conducteur_id INT NOT NULL,
  passager_id INT NOT NULL,
  description TEXT,
  status ENUM('open','closed') NOT NULL DEFAULT 'open',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (ride_id) REFERENCES rides(id)
);
