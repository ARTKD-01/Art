# Gang Desk TODO

## โครงระบบและความปลอดภัย
- [ ] เว็บไซต์ภาษา PHP 8.3 มี front controller, route manifest, health endpoint และ Docker runtime โดยไม่สร้างข้อมูลตัวอย่าง
- [ ] สมาชิกล็อกอินด้วย Discord OAuth2 เท่านั้น พร้อม state/nonce, session, CSRF และตรวจสอบ guild membership
- [ ] หัวแก๊งเข้าหน้าเฉพาะด้วย Discord session + PIN ที่ไม่แสดง/ไม่เก็บ plaintext; รองรับบทบาท หัวแก๊ง, รองหัวแก๊ง, ที่ปรึกษา, สมาชิก
- [ ] จำกัดการตั้งค่า การแก้ชื่อ/สิทธิ์ และการนำสมาชิกออกไว้เฉพาะหัวแก๊งและรองหัวแก๊ง พร้อม audit event

## หน้าหลักและโมดูล
- [ ] หน้าเข้าสู่ระบบ Discord และหน้า PIN หัวแก๊ง
- [ ] หลังล็อกอินครั้งแรกบังคับตั้งชื่อ IC
- [ ] Dashboard, เช็คชื่อรายวัน, รายชื่อสมาชิก, ส่งของรายสัปดาห์, รายชื่อส่งของ, หลักฐานส่งของ
- [ ] เบิกของ, แจ้งลา, รายงานคนไม่เช็คชื่อกี่วัน, สถิติรวม, เบอร์ในเกม, โปรไฟล์ของฉัน
- [ ] Settings เปลี่ยนชื่อแก๊ง โลโก้ พื้นหลัง รอบงาน ช่อง Discord และการแจ้งเตือนผ่านเว็บ

## Discord data plane
- [ ] Discord API service สำหรับ OAuth, guild membership, อ่าน/เขียน event JSON และไฟล์แนบ
- [ ] เก็บ event/schema version/audit trail ใน Discord โดยไม่ใช้ฐานข้อมูลแอปเป็น source of truth
- [ ] ส่งแจ้งเตือนทั้งหมดไป Discord channel ที่ตั้งค่าไว้เท่านั้น

## ประสบการณ์ใช้งาน
- [ ] ธีม Midnight Glass Ledger + Cyber Control Room รองรับมือถือ เอฟเฟกต์แบบลดได้ และไม่มีข้อมูลสมมติ
- [ ] ใช้โปรไฟล์ Discord เป็นค่าเริ่มต้น และผู้ใช้แก้โปรไฟล์ของตนเองได้

## ตรวจสอบและส่งมอบ
- [ ] ตรวจ PHP syntax, policy/aggregation tests, health endpoint และ route manifest
- [ ] บันทึก checkpoint; ยังไม่ publish จนกว่าจะได้รับคำสั่ง
