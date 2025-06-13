-- Add receiptNumber column to consultation table
ALTER TABLE consultation ADD COLUMN receipt_number VARCHAR(50) DEFAULT NULL;

-- Create receipt_counter table
CREATE TABLE merchant.receipt_counter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    counter_name VARCHAR(50) UNIQUE NOT NULL DEFAULT 'main',
    current_number INT NOT NULL DEFAULT 773300,
    last_updated DATETIME NOT NULL
);


-- Insert initial counter starting from 773300
INSERT INTO receipt_counter (counter_name, current_number, last_updated) 
VALUES ('main', 773299, NOW()); 