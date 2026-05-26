<?php
session_start();
$host = 'localhost'; $dbname = 'DataWeb'; $username = 'root'; $password = '';      
try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(55) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL, email VARCHAR(100) UNIQUE NOT NULL,
        full_name VARCHAR(255) NOT NULL, role VARCHAR(20) DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // อัปเดตโครงสร้างใหม่ ถอดของเก่าออก และใส่ฟิลด์ NTBG เข้ามา
    $pdo->exec("CREATE TABLE IF NOT EXISTS herbariums (
        id INT AUTO_INCREMENT PRIMARY KEY, user_id INT,
        barcode VARCHAR(50) UNIQUE, herbarium_name VARCHAR(100), specimen_id VARCHAR(50),
        plant_category VARCHAR(100) DEFAULT 'พรรณไม้ทั่วไป',
        common_name_th VARCHAR(255), common_name_en VARCHAR(255),
        scientific_name VARCHAR(255) NOT NULL, family_name VARCHAR(255), genus VARCHAR(255),
        collector_name VARCHAR(255), collection_date DATE,
        country VARCHAR(100), province VARCHAR(100), island VARCHAR(100),
        elevation INT, locality TEXT,
        
        description TEXT, habit VARCHAR(100), habitat TEXT,
        
        -- ฟิลด์ใหม่ที่ดึงมาจาก NTBG CSV แทนที่ของเก่า
        associated_species TEXT,
        taxonomical_notes TEXT,
        ethnobotanical_notes TEXT,
        medical_notes TEXT,
        
        image_path VARCHAR(255), created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
} catch(PDOException $e) { die("Error: " . $e->getMessage()); }

$upload_dir = __DIR__ . '/uploads/'; if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }
?>