📖 Përshkrimi i Projektit
Ky është një sistem menaxhimi biblioteke i zhvilluar në PHP me ndërfaqe moderne dhe funksionalitete të plota. Projekti mbështet autentikim të përdoruesve, menaxhim katalogu, huazime, kthime, dhe role të ndryshme përdoruesish (User, Librarian, Admin).
Projekti është ndërtuar me një strukturë të pastër, modulare dhe të shkallëzueshme, duke përdorur praktika të mira të programimit (OOP, ndarje të qartë të shtresave, AJAX për përvojë të mirë përdoruesi).

✨ Funksionalitetet Kryesore
🔐 Autentikim dhe Autorizim

Regjistrim dhe hyrje për përdorues
Menaxhim i sesioneve (session)
Logout i sigurt
Role-based access control:
User — shfleton katalogun, huazon libra, shikon historikun
Librarian — menaxhon huazimet dhe kthimet
Admin — menaxhim i plotë (përdorues, libra, raporte, aktivitet)


📚 Menaxhim Katalogu

Katalog interaktiv me grid layout
Kërkim AJAX në kohë reale
Filtrimi i librave
Detaje të librit në modal
Integrim me Open Library API (për të shtuar libra automatikisht)

📖 Huazime dhe Menaxhim

Huazim librash nga përdoruesit
Menaxhim kërkesash huazimi (approve/reject)
Regjistrim kthimesh
Llogaritje gjobash për vonesa
Historik personal i huazimeve

👤 Menaxhim Përdoruesish

Profile përdoruesi
Menaxhim përdoruesish nga admin
Log aktivizmi

🎨 UI/UX

Dizajn modern dhe responsive
Hero section atraktiv
Modal windows
Animacione dhe efekte të buta
Navigacion intuitiv


🗂️ Struktura e Projektit
BashProjekti_WEB2/
├── admin/                      # Paneli i Administratorit
│   ├── dashboard.php
│   ├── manage_books.php
│   ├── manage_users.php
│   ├── borrow_requests.php
│   ├── activity_log.php
│   ├── reports.php
│   ├── openlibrary_api.php
│   └── ...
├── librarian/                  # Paneli i Bibliotekarit
│   ├── borrow_requests.php
│   └── returns.php
├── user/                       # Faqet e Përdoruesit
│   ├── catalog.php
│   ├── book_detail.php
│   ├── borrow.php
│   ├── my_borrows.php
│   ├── reservations.php
│   ├── profile.php
│   └── contact.php
├── auth/                       # Autentikimi
│   ├── login.php
│   ├── register.php
│   └── logout.php
├── config/                     # Konfigurime
│   ├── database.php
│   └── auth_helper.php
├── includes/                   # Komponente të përbashkëta
│   ├── header.php
│   ├── navbar.php
│   ├── footer.php
│   ├── auth_helper.php
│   └── search_ajax.php
├── assets/
│   ├── css/
│   │   ├── style.css
│   │   ├── auth.css
│   │   └── catalog.css
│   └── js/
│       └── catalog.js
├── uploads/                    # Dosje për foto librash
├── index.php                   # Faqja kryesore
├── database.sql                # Skema e bazës së të dhënave
└── README.md

🛠️ Teknologjitë e Përdorura

Backend: PHP 8+
Frontend: HTML5, CSS3, JavaScript (Vanilla)
Database: MySQL / MariaDB
Styling: CSS Modular + Responsive Design
Arkitekturë: MVC-like me OOP
Tools: Git, XAMPP/WAMP/LAMP


🗄️ Baza e të Dhënave
Skedari database.sql përmban të gjitha tabelat e nevojshme:

users (me role: user, librarian, admin)
books
borrow_requests
borrows
activity_log
etj.

Për të instaluar:

Krijo një bazë të re të dhënash
Importo database.sql


🚀 Instalimi dhe Ekzekutimi
Hapat për të nisur projektin:

Klononi repositorinë:Bashgit clone https://github.com/Omujaj/Projekti_WEB2.git
Vendoseni në server:
Kopjoni folderin në htdocs (XAMPP) ose /var/www/html (Linux)

Konfiguroni bazën e të dhënave:
Krijo bazën e të dhënave
Importo database.sql
Përditëso kredencialet në config/database.php

Nisni serverin:
Startoni Apache dhe MySQL nga XAMPP Control Panel

Hyni në aplikacion:
Shkoni në http://localhost/Projekti_WEB2


Llogari të paracaktuara (pas importimit të DB):

Admin: admin@library.com / admin123
Librarian: librarian@library.com / lib123
User: user@library.com / user123


🔧 Konfigurime të Rëndësishme

config/database.php — lidhja me DB
includes/ — funksione ndihmëse
assets/ — të gjitha stilistikat dhe scriptet


📌 Konceptet e Programimit të Demonstruara

Programim i Orientuar në Objekte (OOP)
Klasa dhe Enkapsulim
Sessions dhe Menaxhim Sesionesh
AJAX për kërkime dinamike
Validim inputesh
Siguri bazë (prepared statements)
Ndarje e qartë e logjikës (frontend vs backend)


🛣️ Roadmap i Mundshëm për Zgjerim

Shtimi i sistemit të rezervimeve
Notifikime email
Advanced search + filters (autor, kategori, vit)
Dashboard statistikor me charts
Mobile app (PWA)
Export raportesh (PDF/Excel)


🤝 Kontribut
Kontributet janë të mirëpritura! Ju lutemi:

Bëni fork
Krijo një branch (feature/AmazingFeature)
Commit ndryshimet
Hap një Pull Request