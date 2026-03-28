-- ================================================================
-- EventHub – database_schema.sql
-- Smart College Event Management Portal
-- Run this in phpMyAdmin or MySQL CLI to set up the database
-- ================================================================

CREATE DATABASE IF NOT EXISTS eventhub_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE eventhub_db;

-- ===== USERS TABLE =====
CREATE TABLE IF NOT EXISTS users (
  id            INT AUTO_INCREMENT PRIMARY KEY,
  first_name    VARCHAR(80)  NOT NULL,
  last_name     VARCHAR(80)  NOT NULL,
  email         VARCHAR(160) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role          ENUM('student','organizer','admin') NOT NULL DEFAULT 'student',
  department    VARCHAR(100) DEFAULT NULL,
  profile_pic   VARCHAR(255) DEFAULT NULL,
  created_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_email (email),
  INDEX idx_role  (role)
) ENGINE=InnoDB;

-- ===== EVENTS TABLE =====
CREATE TABLE IF NOT EXISTS events (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  name             VARCHAR(200) NOT NULL,
  category         ENUM('technology','cultural','sports','academic','workshop') NOT NULL,
  event_date       DATE         NOT NULL,
  event_time       TIME         DEFAULT NULL,
  venue            VARCHAR(200) NOT NULL,
  description      TEXT         NOT NULL,
  max_capacity     INT UNSIGNED NOT NULL DEFAULT 100,
  registered_count INT UNSIGNED NOT NULL DEFAULT 0,
  organizer_id     INT          NOT NULL,
  organizer_name   VARCHAR(160) NOT NULL,
  status           ENUM('active','full','completed','cancelled') NOT NULL DEFAULT 'active',
  emoji            VARCHAR(10)  DEFAULT '🎓',
  image_url        VARCHAR(500) DEFAULT NULL,
  created_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (organizer_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX idx_category   (category),
  INDEX idx_event_date (event_date),
  INDEX idx_status     (status)
) ENGINE=InnoDB;

-- ===== REGISTRATIONS TABLE =====
CREATE TABLE IF NOT EXISTS registrations (
  id               INT AUTO_INCREMENT PRIMARY KEY,
  user_id          INT NOT NULL,
  event_id         INT NOT NULL,
  registration_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status           ENUM('confirmed','pending','cancelled') NOT NULL DEFAULT 'confirmed',
  UNIQUE KEY unique_reg (user_id, event_id),
  FOREIGN KEY (user_id)  REFERENCES users(id)  ON DELETE CASCADE,
  FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
  INDEX idx_user_id  (user_id),
  INDEX idx_event_id (event_id)
) ENGINE=InnoDB;

-- ===== TRIGGER: Auto-update registered_count =====
DELIMITER $$

CREATE TRIGGER after_registration_insert
  AFTER INSERT ON registrations
  FOR EACH ROW
BEGIN
  UPDATE events
  SET registered_count = registered_count + 1,
      status = IF(registered_count + 1 >= max_capacity, 'full', 'active')
  WHERE id = NEW.event_id;
END $$

CREATE TRIGGER after_registration_cancel
  AFTER UPDATE ON registrations
  FOR EACH ROW
BEGIN
  IF NEW.status = 'cancelled' AND OLD.status != 'cancelled' THEN
    UPDATE events
    SET registered_count = GREATEST(registered_count - 1, 0),
        status = IF(status = 'full', 'active', status)
    WHERE id = NEW.event_id;
  END IF;
END $$

DELIMITER ;

-- ===== SAMPLE DATA =====
INSERT INTO users (first_name, last_name, email, password_hash, role, department) VALUES
  ('Admin',     'User',     'admin@eventhub.edu',     '$2y$12$dummyhashfordemopurposes001', 'admin',     'Administration'),
  ('Tech',      'Organizer','techclub@eventhub.edu',  '$2y$12$dummyhashfordemopurposes002', 'organizer', 'Computer Science'),
  ('Cultural',  'Organizer','cultural@eventhub.edu',  '$2y$12$dummyhashfordemopurposes003', 'organizer', 'Arts & Culture'),
  ('John',      'Student',  'john.doe@geu.edu',       '$2y$12$dummyhashfordemopurposes004', 'student',   'B.Tech CSE'),
  ('Priya',     'Sharma',   'priya.sharma@geu.edu',   '$2y$12$dummyhashfordemopurposes005', 'student',   'B.Tech ECE');

INSERT INTO events (name, category, event_date, event_time, venue, description, max_capacity, registered_count, organizer_id, organizer_name, status, emoji) VALUES
  ('AI & Machine Learning Workshop', 'technology', '2026-02-19', '10:00:00', 'Seminar Hall A',   'An intensive workshop covering neural networks, deep learning, and real-world AI applications.', 200, 150, 2, 'Tech Club',         'active',    '🤖'),
  ('Annual Cultural Festival 2026',  'cultural',   '2026-03-20', '09:00:00', 'Main Auditorium',  'A grand celebration of art, music, dance, and culture from across India.', 500, 500, 3, 'Cultural Committee', 'full',      '🎭'),
  ('Cricket Tournament 2026',        'sports',     '2026-03-22', '08:00:00', 'College Ground',   'Inter-department cricket tournament. All teams welcome!', 12,  6,   2, 'Sports Department',  'active',    '🏏'),
  ('Hackathon 2026: Build the Future','technology', '2026-03-28', '08:00:00', 'Computer Lab',     '24-hour coding hackathon. Build innovative solutions for real-world problems.', 100, 85,  2, 'Tech Club',         'active',    '💻'),
  ('AI Masterclass – Deep Learning', 'workshop',   '2026-02-22', '14:00:00', 'Convention Center','Expert-led masterclass on PyTorch, TensorFlow, and practical deep learning.', 150, 60,  2, 'Tech Club',         'active',    '🎓'),
  ('Cyber Security Awareness Seminar','technology', '2026-02-28', '11:00:00', 'Seminar Hall B',   'Learn about ethical hacking, data protection, and modern cybersecurity practices.', 200, 120, 2, 'Tech Club',        'active',    '🔐'),
  ('Football Championship Finals',   'sports',     '2026-03-15', '15:00:00', 'Main Ground',      'The thrilling finals of the inter-college football championship!', 500, 450, 3, 'Sports Department',  'active',    '🏆'),
  ('Research Paper Presentation',    'academic',   '2026-02-25', '10:00:00', 'Conference Hall',  'Students present their research papers to faculty and industry experts.', 100, 30,  3, 'Student Council',    'active',    '📝');

-- ===== VIEW: Event with available seats =====
CREATE OR REPLACE VIEW events_available AS
  SELECT *, (max_capacity - registered_count) AS seats_available
  FROM events
  WHERE status IN ('active','full')
  ORDER BY event_date ASC;
