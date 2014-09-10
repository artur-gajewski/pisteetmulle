CREATE TABLE entries (
     id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
     shorturl VARCHAR(50),
     author VARCHAR(100),
     story VARCHAR(255),
     created TIMESTAMP DEFAULT NOW()
);