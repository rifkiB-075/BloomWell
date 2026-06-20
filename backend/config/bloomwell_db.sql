-- ============================================================
--  BloomWell Database Schema
--  Platform Kesehatan Mental
--  Versi: 1.0
-- ============================================================

CREATE DATABASE IF NOT EXISTS bloomwell_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE bloomwell_db;

-- ============================================================
-- 1. USERS — Akun pengguna
-- ============================================================
CREATE TABLE users (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username      VARCHAR(80)  NOT NULL UNIQUE,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,
    full_name     VARCHAR(150),
    phone         VARCHAR(20),
    about_me      TEXT,
    avatar_url    VARCHAR(500),
    role          ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    email_verified_at DATETIME,
    last_login_at DATETIME,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 2. MOOD TRACKER — Pelacak suasana hati
-- ============================================================
CREATE TABLE mood_entries (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    mood          ENUM('very_bad','bad','neutral','good','very_good') NOT NULL,
    mood_score    TINYINT UNSIGNED NOT NULL COMMENT '1–5',
    note          TEXT,
    ai_analysis   TEXT         COMMENT 'Hasil analisis AI terhadap catatan',
    entry_date    DATE         NOT NULL,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_date (user_id, entry_date)
) ENGINE=InnoDB;

-- ============================================================
-- 3. MOOD METER — Penilaian cepat mood harian (InteractiveTools)
-- ============================================================
CREATE TABLE mood_meter_logs (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    mood_value    TINYINT UNSIGNED NOT NULL COMMENT '0–100 skala mood meter',
    logged_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_time (user_id, logged_at)
) ENGINE=InnoDB;

-- ============================================================
-- 4. JURNAL HARIAN — Jurnal pribadi pengguna
-- ============================================================
CREATE TABLE journal_entries (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    title         VARCHAR(255),
    content       TEXT         NOT NULL,
    mood_id       INT UNSIGNED COMMENT 'Referensi ke mood_entries jika terkait',
    entry_date    DATE         NOT NULL,
    is_private    TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (mood_id)  REFERENCES mood_entries(id) ON DELETE SET NULL,
    INDEX idx_user_date (user_id, entry_date)
) ENGINE=InnoDB;

-- ============================================================
-- 5. CHAT AI — Riwayat percakapan dengan AI
-- ============================================================
CREATE TABLE chat_sessions (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    title         VARCHAR(255) DEFAULT 'Sesi Chat AI',
    started_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at      DATETIME,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE chat_messages (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id    INT UNSIGNED NOT NULL,
    role          ENUM('user','assistant') NOT NULL,
    content       TEXT         NOT NULL,
    sent_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES chat_sessions(id) ON DELETE CASCADE,
    INDEX idx_session (session_id)
) ENGINE=InnoDB;

-- ============================================================
-- 6. KUIS / QUIZ — Kuis prediksi mood & kesehatan mental
-- ============================================================
CREATE TABLE quizzes (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(255) NOT NULL,
    description   TEXT,
    category      VARCHAR(100) COMMENT 'Contoh: mood, anxiety, stress',
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE quiz_questions (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quiz_id       INT UNSIGNED NOT NULL,
    question_text TEXT         NOT NULL,
    sort_order    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_options (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    question_id   INT UNSIGNED NOT NULL,
    option_text   VARCHAR(255) NOT NULL,
    score_value   TINYINT      NOT NULL DEFAULT 0,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE quiz_results (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    quiz_id       INT UNSIGNED NOT NULL,
    total_score   SMALLINT     NOT NULL,
    result_label  VARCHAR(150) COMMENT 'Contoh: Stres Ringan, Depresi Sedang',
    result_detail TEXT,
    taken_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (quiz_id)  REFERENCES quizzes(id) ON DELETE CASCADE,
    INDEX idx_user_quiz (user_id, quiz_id)
) ENGINE=InnoDB;

CREATE TABLE quiz_result_answers (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    result_id     INT UNSIGNED NOT NULL,
    question_id   INT UNSIGNED NOT NULL,
    option_id     INT UNSIGNED NOT NULL,
    FOREIGN KEY (result_id)   REFERENCES quiz_results(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES quiz_questions(id),
    FOREIGN KEY (option_id)   REFERENCES quiz_options(id)
) ENGINE=InnoDB;

-- ============================================================
-- 7. RESOURCE LIBRARY — Artikel, Video, Podcast
-- ============================================================
CREATE TABLE resource_categories (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    slug          VARCHAR(100) NOT NULL UNIQUE,
    description   TEXT,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE resources (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    category_id   INT UNSIGNED NOT NULL,
    type          ENUM('article','video','podcast') NOT NULL,
    title         VARCHAR(255) NOT NULL,
    slug          VARCHAR(255) NOT NULL UNIQUE,
    description   TEXT,
    content       LONGTEXT     COMMENT 'Konten lengkap untuk artikel',
    thumbnail_url VARCHAR(500),
    media_url     VARCHAR(500) COMMENT 'URL video/podcast/audio',
    duration_sec  INT UNSIGNED COMMENT 'Durasi video/podcast dalam detik',
    author        VARCHAR(150),
    tags          VARCHAR(500) COMMENT 'Comma-separated tags',
    view_count    INT UNSIGNED NOT NULL DEFAULT 0,
    is_published  TINYINT(1)   NOT NULL DEFAULT 1,
    published_at  DATETIME,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES resource_categories(id),
    INDEX idx_type (type),
    INDEX idx_category (category_id)
) ENGINE=InnoDB;

CREATE TABLE resource_bookmarks (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    resource_id   INT UNSIGNED NOT NULL,
    bookmarked_at DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_resource (user_id, resource_id),
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE resource_likes (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    resource_id   INT UNSIGNED NOT NULL,
    liked_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_like (user_id, resource_id),
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (resource_id) REFERENCES resources(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- 8. PROFESSIONAL SERVICE — Direktori terapis/psikolog
-- ============================================================
CREATE TABLE therapists (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name       VARCHAR(150) NOT NULL,
    title           VARCHAR(100) COMMENT 'Contoh: M.Psi., Psikolog',
    specialization  VARCHAR(255),
    bio             TEXT,
    photo_url       VARCHAR(500),
    education       TEXT,
    experience_years TINYINT UNSIGNED,
    languages       VARCHAR(200) COMMENT 'Bahasa yang dikuasai',
    location        VARCHAR(200),
    consultation_fee DECIMAL(10,2),
    is_available    TINYINT(1)   NOT NULL DEFAULT 1,
    contact_email   VARCHAR(150),
    contact_phone   VARCHAR(30),
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE therapist_reviews (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    therapist_id  INT UNSIGNED NOT NULL,
    user_id       INT UNSIGNED NOT NULL,
    rating        TINYINT UNSIGNED NOT NULL COMMENT '1–5',
    review_text   TEXT,
    reviewed_at   DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_therapist (user_id, therapist_id),
    FOREIGN KEY (therapist_id) REFERENCES therapists(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id)      REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE appointments (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id         INT UNSIGNED NOT NULL,
    therapist_id    INT UNSIGNED NOT NULL,
    appointment_date DATE        NOT NULL,
    appointment_time TIME        NOT NULL,
    duration_min    TINYINT UNSIGNED NOT NULL DEFAULT 60,
    method          ENUM('online','offline') NOT NULL DEFAULT 'online',
    status          ENUM('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'pending',
    notes           TEXT,
    created_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)      REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (therapist_id) REFERENCES therapists(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_therapist_date (therapist_id, appointment_date)
) ENGINE=InnoDB;

-- ============================================================
-- 9. EMERGENCY CONTACTS — Layanan darurat kesehatan mental
-- ============================================================
CREATE TABLE emergency_contacts (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(150) NOT NULL,
    description   TEXT,
    phone         VARCHAR(50),
    website_url   VARCHAR(500),
    available_24h TINYINT(1)   NOT NULL DEFAULT 0,
    is_active     TINYINT(1)   NOT NULL DEFAULT 1,
    sort_order    TINYINT UNSIGNED NOT NULL DEFAULT 0,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
-- 10. NOTIFICATIONS — Notifikasi untuk pengguna
-- ============================================================
CREATE TABLE notifications (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id       INT UNSIGNED NOT NULL,
    type          VARCHAR(80)  NOT NULL COMMENT 'Contoh: appointment_reminder, mood_reminder',
    title         VARCHAR(255) NOT NULL,
    message       TEXT,
    is_read       TINYINT(1)   NOT NULL DEFAULT 0,
    read_at       DATETIME,
    created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_unread (user_id, is_read)
) ENGINE=InnoDB;

-- ============================================================
-- 11. USER SETTINGS — Preferensi pengguna
-- ============================================================
CREATE TABLE user_settings (
    user_id             INT UNSIGNED PRIMARY KEY,
    theme               ENUM('light','dark','system') NOT NULL DEFAULT 'system',
    language            VARCHAR(10) NOT NULL DEFAULT 'id',
    mood_reminder_time  TIME        COMMENT 'Waktu pengingat mood harian',
    email_notifications TINYINT(1)  NOT NULL DEFAULT 1,
    push_notifications  TINYINT(1)  NOT NULL DEFAULT 1,
    updated_at          DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
-- SEED DATA
-- ============================================================

-- Kategori resource
INSERT INTO resource_categories (name, slug, description) VALUES
('Kecemasan',     'kecemasan',    'Artikel & media seputar manajemen kecemasan'),
('Depresi',       'depresi',      'Informasi dan tips mengatasi depresi'),
('Mindfulness',   'mindfulness',  'Teknik kesadaran penuh dan meditasi'),
('Stres Kerja',   'stres-kerja',  'Mengelola tekanan dan burnout di tempat kerja'),
('Hubungan',      'hubungan',     'Kesehatan mental dalam relasi dan keluarga');

-- Kuis default
INSERT INTO quizzes (title, description, category) VALUES
('Kuis Prediksi Mood',       'Prediksi suasana hatimu hari ini berdasarkan serangkaian pertanyaan singkat.', 'mood'),
('Tingkat Stres',            'Ukur seberapa besar stres yang kamu alami minggu ini.',                        'stress'),
('Skala Kecemasan (GAD-7)',  'Alat skrining kecemasan umum berbasis 7 pertanyaan standar.',                  'anxiety');

-- Kontak darurat
INSERT INTO emergency_contacts (name, description, phone, available_24h, sort_order) VALUES
('Into The Light Indonesia',  'Hotline kesehatan mental 24 jam untuk konsultasi cepat.',          '119 ext 8', 1, 1),
('Yayasan Pulih',             'Layanan konseling psikologis profesional.',                         '021-7884 5555', 0, 2),
('LSM Jangan Bunuh Diri',     'Layanan pencegahan bunuh diri dan dukungan krisis.',                '021-7884 5555', 1, 3),
('Hotline Kemkes RI',         'Layanan informasi kesehatan Kementerian Kesehatan RI.',             '1500-567', 1, 4);

-- ============================================================
-- END OF SCHEMA
-- ============================================================
