# 🌿 Herbarium Specimen Database
> **ระบบฐานข้อมูลและสืบค้นพรรณไม้แห้งออนไลน์**  
> พัฒนาขึ้นสำหรับโครงการรายวิชาการเขียนโปรแกรมบนเว็บ (Web Programming Course Project)

---

## 🌟 ฟีเจอร์หลักของระบบ (Features)

### 1. ระบบค้นหาขั้นสูง (Advanced Search)
- ค้นหาตัวอย่างพรรณไม้ด้วยรหัสบาร์โค้ด (Barcode / Specimen ID) หรือชื่อวิทยาศาสตร์ (Scientific Name)
- ระบบตัวกรองข้อมูลความต้องการสูง (Dynamic Filter) เช่น วงศ์พืช (Family), สกุลพืช (Genus), ประเทศที่พบ (Country), เกาะ/พื้นที่เก็บ (Island), และผู้เก็บรวบรวม (Collector)
- แบ่งหมวดหมู่พืชอัตโนมัติ: พืชใบเลี้ยงคู่ (Dicot), พืชใบเลี้ยงเดี่ยว (Monocot) และเฟิร์น (Pteridophyte)

### 2. หน้าแสดงข้อมูลโดยละเอียด (Specimen Detail View)
- แสดงรูปภาพแบบสมบูรณ์ รองรับการขยายภาพใหญ่ (Modal Zoom) 
- แสดงข้อมูลพฤกษศาสตร์พื้นบ้าน (Ethnobotanical), บันทึกทางอนุกรมวิธาน (Taxonomical), และบันทึกสรรพคุณทางยา (Medicinal Use Notes)
- **ระบบ Fallback ภาพ:** รองรับการดึงรูปภาพแบบออฟไลน์บนเซิร์ฟเวอร์ และสลับไปดึงภาพแบบลิงก์ออนไลน์ (NTBG URL) โดยอัตโนมัติหากไม่มีไฟล์อยู่ในเครื่อง

### 3. ระบบผู้ดูแลระบบ (Admin Control Panel)
- **ระบบจัดการข้อมูลพรรณไม้ (Specimen CRUD):** เพิ่ม, แก้ไข, ลบข้อมูล พร้อมระบบสร้างบาร์โค้ดอัตโนมัติ
- **ระบบจัดการบัญชีผู้ใช้งาน (User Management):** สมัครสมาชิก, เข้าสู่ระบบ, กำหนดสิทธิ์การเข้าถึง (Admin / User), แก้ไข และลบผู้ใช้ในระบบ

### 4. ระบบเพิ่มประสิทธิภาพและความปลอดภัย (Performance & Robustness)
- **บีบอัดภาพอัตโนมัติ:** เมื่ออัปโหลดภาพผ่านระบบ ภาพจะถูกแปลงเป็นสกุล `.webp` ความชัด 80% เพื่อช่วยประหยัดพื้นที่เซิร์ฟเวอร์และโหลดหน้าเว็บได้รวดเร็วขึ้น
- **ระบบสำรองความปลอดภัย (GD Fallback):** หากเครื่องเซิร์ฟเวอร์ไม่ได้เปิดการทำงานของ GD Extension ใน PHP ระบบจะปรับไปอัปโหลดไฟล์ภาพดั้งเดิมโดยตรงเพื่อป้องกันโปรแกรม Fatal Error
- **รองรับ PHP 8.1+:** ป้องกันการเกิดข้อผิดพลาดแจ้งเตือน (`Passing null to htmlspecialchars`) บน PHP เวอร์ชันใหม่ทั้งหมด 100%

---

## 🛠️ โครงสร้างเทคโนโลยี (Tech Stack)
- **Backend:** PHP (PDO Extension)
- **Database:** MySQL / MariaDB (ใช้ ENGINE=InnoDB คีย์ต่าง ๆ เชื่อมโยงแบบ Cascade)
- **Frontend CSS:** Tailwind CSS (ผ่าน CDN)
- **Typography:** Google Fonts (Sarabun / Monospace สำหรับรหัส)

---

## 📂 โครงสร้างไฟล์ในระบบ (File Structure)
* `db.php` - ไฟล์ตั้งค่าเชื่อมต่อฐานข้อมูล MySQL และสร้างตารางข้อมูลเริ่มต้น
* `header.php` / `footer.php` - เทมเพลตส่วนหัวและส่วนท้ายของหน้าเว็บ
* `index.php` - หน้าหลักแสดงรายการพรรณไม้แห้งและการค้นหาขั้นสูง
* `detail.php` - หน้าแสดงข้อมูลพรรณไม้แต่ละรายการโดยละเอียด
* `login.php` / `logout.php` - ระบบเข้าสู่ระบบ สมัครสมาชิก และออกจากระบบ
* `dashboard.php` - หน้าแดชบอร์ดจัดการข้อมูลพรรณไม้ (สำหรับผู้ดูแลระบบ)
* `create.php` / `edit.php` - หน้าสำหรับเพิ่มและแก้ไขข้อมูลพรรณไม้
* `users.php` - หน้าจัดการบัญชีผู้ใช้งานระบบ (สำหรับผู้ดูแลระบบ)
* `create_user.php` / `edit_user.php` - หน้าเพิ่มและแก้ไขข้อมูลผู้ใช้งาน
* `import_ntbg.php` - สคริปต์เสริมสำหรับนำเข้าข้อมูลพรรณไม้จากไฟล์ CSV ของระบบฐานข้อมูล NTBG

---

## ⚙️ วิธีการติดตั้งและตั้งค่า (Installation & Setup)

1. **คัดลอกไฟล์โครงการ** 
   นำโฟลเดอร์โครงการไปวางไว้ในโฟลเดอร์เว็บเซิร์ฟเวอร์ของคุณ เช่น `C:\xampp\htdocs\DataWeb`
   
2. **สร้างฐานข้อมูล MySQL**
   - เปิด phpMyAdmin หรือ Command Line ของ MySQL
   - สร้างฐานข้อมูลใหม่ชื่อ `DataWeb` คอลเลชันเลือกเป็น `utf8mb4_general_ci`
   
3. **กำหนดค่าการเชื่อมต่อ**
   - ตรวจสอบไฟล์ `db.php` เพื่อตั้งค่า Host, Username, Password และ Database Name ให้ถูกต้อง (ค่าเริ่มต้นสำหรับ XAMPP ถูกตั้งค่าไว้แล้วเป็น `root` และไม่มีรหัสผ่าน)
   - เมื่อเข้าหน้าแรกของโปรเจกต์ ตัวระบบจะทำการรันคำสั่ง `CREATE TABLE` เพื่อสร้างตาราง `users` และ `herbariums` ให้โดยอัตโนมัติ

4. **การเข้าใช้งาน**
   - เข้าใช้งานทางเว็บบราวเซอร์ผ่านที่อยู่: `http://localhost/DataWeb`
   - ทำการสมัครสมาชิกผู้ใช้ใหม่ผ่านปุ่ม **เข้าสู่ระบบ -> สมัครสมาชิกใหม่** 
   - *หมายเหตุ:* บัญชีแรกที่สมัครเข้ามาในฐานข้อมูลจะได้รับบทบาทสิทธิ์ (Role) เริ่มต้นเป็น `user` หากต้องการเปลี่ยนสิทธิ์เป็นผู้ดูแลระบบ (Admin) เพื่อจัดการหลังบ้าน ให้เข้าไปแก้ไขฟิลด์ `role` ในตาราง `users` ให้เป็น `admin` ผ่านทาง phpMyAdmin

---

## 📝 โครงสร้างข้อมูลตาราง (Database Schema)

### ตาราง `users`
เก็บข้อมูลบัญชีผู้ใช้งานในระบบ
- `id` (INT, Primary Key, Auto Increment)
- `username` (VARCHAR(55), Unique)
- `password` (VARCHAR(255), เข้ารหัสความปลอดภัยด้วย Bcrypt)
- `email` (VARCHAR(100), Unique)
- `full_name` (VARCHAR(255))
- `role` (VARCHAR(20), Default: 'user')
- `created_at` (TIMESTAMP)

### ตาราง `herbariums`
เก็บข้อมูลตัวอย่างพรรณไม้แห้ง
- `id` (INT, Primary Key, Auto Increment)
- `user_id` (INT, Foreign Key เชื่อมไปยัง `users.id` แบบ ON DELETE CASCADE)
- `barcode` (VARCHAR(50), Unique)
- `specimen_id` (VARCHAR(50))
- `herbarium_name` (VARCHAR(100))
- `plant_category` (VARCHAR(100), Default: 'พรรณไม้ทั่วไป')
- `common_name_th` (VARCHAR(255))
- `common_name_en` (VARCHAR(255))
- `scientific_name` (VARCHAR(255))
- `family_name` (VARCHAR(255))
- `genus` (VARCHAR(255))
- `collector_name` (VARCHAR(255))
- `collection_date` (DATE)
- `country` (VARCHAR(100))
- `province` (VARCHAR(100))
- `island` (VARCHAR(100))
- `elevation` (INT)
- `locality` (TEXT)
- `description` (TEXT)
- `habit` (VARCHAR(100))
- `habitat` (TEXT)
- `associated_species` (TEXT)
- `taxonomical_notes` (TEXT)
- `ethnobotanical_notes` (TEXT)
- `medical_notes` (TEXT)
- `image_path` (VARCHAR(255))
- `thumbnail_path` (VARCHAR(255))
- `created_at` (TIMESTAMP)
