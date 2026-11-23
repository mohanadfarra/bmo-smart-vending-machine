# 🥤 BMO — Smart Vending Machine

![Raspberry Pi](https://img.shields.io/badge/Raspberry%20Pi-A22846?style=flat-square&logo=raspberrypi&logoColor=white)
![ESP32](https://img.shields.io/badge/ESP32-E7352C?style=flat-square&logo=espressif&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-777BB4?style=flat-square&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-4479A1?style=flat-square&logo=mysql&logoColor=white)

> Sophomore engineering project (ISS) · SMU MedTech · Jan – May 2025

An **IoT-enabled vending machine** with cashless payment, real-time stock monitoring, sensors and a
remote management platform.

## 🔩 Hardware

Raspberry Pi · ESP32 · RFID reader · stepper motors (dispensing) · keypad · LCD · temperature & motion sensors

## 💻 Software (this repo)

| Part | Files | What it does |
|---|---|---|
| **Customer web app** | `index.php`, `payment.php`, `check_stock.php`, `script.js`, `style.css` | Customer scans the machine's QR code, picks a product, pays |
| **Manager dashboard** | `manager/` | Machines, products, stock, customers, transactions, reports, settings |
| **Database** | `vending_machines.sql` | MySQL schema + sample data |

## ▶️ Run it locally

1. Install [XAMPP](https://www.apachefriends.org/) (Apache + PHP + MySQL).
2. Copy this folder into `htdocs/`.
3. Import `vending_machines.sql` in phpMyAdmin.
4. Set your MySQL credentials in `manager/config/db.php`.
5. Open `http://localhost/bmo-smart-vending-machine/?machine=<QR code from the database>`.

## 📄 Documents

- [Final report](docs/BMO_FinalReport.pdf)
- [Poster](docs/BMO-poster-B.pdf)

## 👥 Team

**Mohanad Alfarra** · Zainab Almagbari · Louay Arfa · Ibrahim Fourati · Asma Ouni<br>
Advisor: Yassine Chaouch
