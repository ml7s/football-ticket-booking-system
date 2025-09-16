CREATE DATABASE ticket_booking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ticket_booking;

CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE matches (
    id INT PRIMARY KEY AUTO_INCREMENT,
    team_home VARCHAR(100) NOT NULL,
    team_away VARCHAR(100) NOT NULL,
    match_date DATETIME NOT NULL,
    stadium VARCHAR(100) NOT NULL,
    ticket_price DECIMAL(10,2) NOT NULL,
    available_tickets INT NOT NULL DEFAULT 1000,
    total_tickets INT NOT NULL DEFAULT 1000,
    match_status ENUM('upcoming', 'ongoing', 'finished', 'cancelled') DEFAULT 'upcoming',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    match_id INT NOT NULL,
    ticket_quantity INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    booking_reference VARCHAR(20) UNIQUE NOT NULL,
    booking_status ENUM('confirmed', 'cancelled', 'pending') DEFAULT 'confirmed',
    booking_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (match_id) REFERENCES matches(id) ON DELETE CASCADE
);

INSERT INTO matches (team_home, team_away, match_date, stadium, ticket_price, available_tickets, total_tickets, description) VALUES
('الأهلي', 'الهلال', '2025-10-15 20:00:00', 'استاد الملك فهد الدولي', 150.00, 45000, 45000, 'مباراة قوية بين فريقي القمة'),
('النصر', 'الاتحاد', '2025-10-20 18:00:00', 'استاد مرسول بارك', 120.00, 30000, 30000, 'ديربي جدة المثير'),
('الشباب', 'الفيصلي', '2025-10-25 21:00:00', 'استاد الأمير فيصل بن فهد', 100.00, 25000, 25000, 'مباراة مهمة في الدوري'),
('الرائد', 'الفتح', '2025-11-01 19:30:00', 'استاد الملك سلمان', 80.00, 20000, 20000, 'مباراة حاسمة في ترتيب الدوري'),
('الاتفاق', 'أبها', '2025-11-05 17:00:00', 'استاد الأمير محمد بن فهد', 75.00, 18000, 18000, 'مواجهة مثيرة بين الفريقين');

CREATE INDEX idx_bookings_user_id ON bookings(user_id);
CREATE INDEX idx_bookings_match_id ON bookings(match_id);
CREATE INDEX idx_matches_date ON matches(match_date);
CREATE INDEX idx_users_email ON users(email);

DELIMITER //
CREATE TRIGGER update_available_tickets_after_booking
AFTER INSERT ON bookings
FOR EACH ROW
BEGIN
    UPDATE matches 
    SET available_tickets = available_tickets - NEW.ticket_quantity
    WHERE id = NEW.match_id;
END//

CREATE TRIGGER update_available_tickets_after_cancel
AFTER UPDATE ON bookings
FOR EACH ROW
BEGIN
    IF OLD.booking_status != 'cancelled' AND NEW.booking_status = 'cancelled' THEN
        UPDATE matches 
        SET available_tickets = available_tickets + OLD.ticket_quantity
        WHERE id = OLD.match_id;
    END IF;
END//
DELIMITER ;