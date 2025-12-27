CREATE TABLE computers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    processor VARCHAR(255),
    ram VARCHAR(50),
    purchase_date DATE,
    status ENUM('available', 'maintenance', 'retired') DEFAULT 'available'
);

CREATE TABLE borrowers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255),
    site VARCHAR(100),
    referrer VARCHAR(255)
);

CREATE TABLE loans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    computer_id INT,
    borrower_id INT,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    is_returned BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (computer_id) REFERENCES computers(id),
    FOREIGN KEY (borrower_id) REFERENCES borrowers(id)
);

INSERT INTO computers (name, processor, ram, purchase_date) VALUES 
('RetroPC-01', 'Intel 486', '16MB', '1995-05-20'),
('RetroPC-02', 'Pentium III', '256MB', '2001-08-15'),
('Modern-01', 'i7-12700K', '32GB', '2023-01-10');

INSERT INTO borrowers (name, email, site, referrer) VALUES
('John Doe', 'john@retro.lan', 'Engineering', 'Manager Bob'),
('Jane Smith', 'jane@retro.lan', 'Design', 'Lead Alice');

INSERT INTO loans (computer_id, borrower_id, start_date, end_date, is_returned) VALUES
(1, 1, '2023-10-01', '2023-10-07', TRUE),
(2, 2, CURDATE() - INTERVAL 10 DAY, CURDATE() - INTERVAL 2 DAY, FALSE);
