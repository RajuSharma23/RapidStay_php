-- Create database
CREATE DATABASE IF NOT EXISTS rapidstay;
USE rapidstay;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone VARCHAR(20) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('tenant', 'owner', 'admin') NOT NULL DEFAULT 'tenant',
    profile_image VARCHAR(255) DEFAULT NULL,
    bio TEXT,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Verification tokens table
CREATE TABLE verification_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Remember me tokens table
CREATE TABLE remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Listings table
CREATE TABLE listings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT NOT NULL,
    type ENUM('room', 'roommate', 'pg') NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    security_deposit DECIMAL(10, 2) NOT NULL,
    address VARCHAR(255) NOT NULL,
    locality VARCHAR(100) NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    zipcode VARCHAR(20) NOT NULL,
    latitude DECIMAL(10, 8) DEFAULT NULL,
    longitude DECIMAL(11, 8) DEFAULT NULL,
    available_from DATE NOT NULL,
    min_duration INT NOT NULL DEFAULT 1,
    max_occupants INT NOT NULL DEFAULT 1,
    furnishing_type ENUM('unfurnished', 'semi-furnished', 'fully-furnished') NOT NULL,
    property_size DECIMAL(10, 2) DEFAULT NULL,
    bathroom_count INT NOT NULL DEFAULT 1,
    is_shared_bathroom BOOLEAN DEFAULT FALSE,
    is_premium BOOLEAN DEFAULT FALSE,
    is_featured BOOLEAN DEFAULT FALSE,
    is_verified BOOLEAN DEFAULT FALSE,
    is_active BOOLEAN DEFAULT TRUE,
    rating DECIMAL(3, 2) DEFAULT 0,
    reviews_count INT DEFAULT 0,
    views_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Listing images table
CREATE TABLE listing_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE
);

-- Amenities table
CREATE TABLE amenities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    icon VARCHAR(50) NOT NULL,
    category ENUM('basic', 'kitchen', 'bathroom', 'bedroom', 'entertainment', 'safety', 'other') NOT NULL DEFAULT 'other',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Listing amenities table (many-to-many)
CREATE TABLE listing_amenities (
    listing_id INT NOT NULL,
    amenity_id INT NOT NULL,
    PRIMARY KEY (listing_id, amenity_id),
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (amenity_id) REFERENCES amenities(id) ON DELETE CASCADE
);

-- Bookings table
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    user_id INT NOT NULL,
    move_in_date DATE NOT NULL,
    duration INT NOT NULL,
    occupants INT NOT NULL DEFAULT 1,
    total_amount DECIMAL(10, 2) NOT NULL,
    message TEXT,
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending',
    payment_status ENUM('pending', 'partial', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10, 2) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(100) NOT NULL,
    status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Reviews table
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    listing_id INT NOT NULL,
    user_id INT NOT NULL,
    booking_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NOT NULL,
    is_approved BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
);

-- Wishlist table
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    listing_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    UNIQUE KEY (user_id, listing_id)
);

-- Messages table
CREATE TABLE messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    listing_id INT,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE SET NULL
);

-- Cities table
CREATE TABLE cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_popular BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Staff table
CREATE TABLE IF NOT EXISTS staff (
    id INT AUTO_INCREMENT PRIMARY KEY,
    owner_id INT NOT NULL,
    property_id INT,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    role VARCHAR(50) NOT NULL,
    salary DECIMAL(10,2) NOT NULL,
    status ENUM('active', 'inactive', 'on_leave') DEFAULT 'active',
    join_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES listings(id) ON DELETE SET NULL
);

-- Insert sample amenities
INSERT INTO amenities (name, icon, category) VALUES
('WiFi', 'wifi', 'basic'),
('Air Conditioning', 'fan', 'basic'),
('TV', 'tv', 'entertainment'),
('Refrigerator', 'snowflake', 'kitchen'),
('Washing Machine', 'tshirt', 'basic'),
('Microwave', 'utensils', 'kitchen'),
('Study Table', 'book', 'bedroom'),
('Parking', 'car', 'basic'),
('Gym', 'dumbbell', 'other'),
('Swimming Pool', 'water', 'other'),
('Security', 'shield', 'safety'),
('Power Backup', 'bolt', 'basic'),
('Attached Bathroom', 'bath', 'bathroom'),
('Balcony', 'home', 'basic'),
('Kitchen', 'utensils', 'kitchen'),
('CCTV', 'video', 'safety'),
('Geyser/Hot Water', 'fire', 'bathroom'),
('Lift/Elevator', 'arrow-up', 'basic');

-- Insert sample cities
INSERT INTO cities (name, state, image_url, is_popular) VALUES
('Delhi', 'Delhi', 'assets/images/cities/delhi.jpg', TRUE),
('Mumbai', 'Maharashtra', 'assets/images/cities/mumbai.jpg', TRUE),
('Bangalore', 'Karnataka', 'assets/images/cities/bangalore.jpg', TRUE),
('Hyderabad', 'Telangana', 'assets/images/cities/hyderabad.jpg', TRUE),
('Chennai', 'Tamil Nadu', 'assets/images/cities/chennai.jpg', TRUE),
('Kolkata', 'West Bengal', 'assets/images/cities/kolkata.jpg', TRUE),
('Pune', 'Maharashtra', 'assets/images/cities/pune.jpg', TRUE),
('Chandigarh', 'Chandigarh', 'assets/images/cities/chandigarh.jpg', TRUE),
('Gurgaon', 'Haryana', 'assets/images/cities/gurgaon.jpg', TRUE),
('Noida', 'Uttar Pradesh', 'assets/images/cities/noida.jpg', FALSE),
('Jaipur', 'Rajasthan', 'assets/images/cities/jaipur.jpg', FALSE),
('Ahmedabad', 'Gujarat', 'assets/images/cities/ahmedabad.jpg', FALSE);

-- Insert sample admin user
INSERT INTO users (name, email, phone, password, user_type, is_verified, is_active) VALUES
('Admin User', 'admin@rapidstay.com', '9876543210', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE, TRUE);

