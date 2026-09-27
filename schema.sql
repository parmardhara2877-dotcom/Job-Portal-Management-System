-- =====================================================================
-- Job Portal - Database Schema
-- Target: MySQL 5.7+ / MariaDB 10.3+
-- =====================================================================
-- Run:  mysql -u root -p < database/schema.sql
-- =====================================================================

-- CREATE DATABASE IF NOT EXISTS job_portal
--     CHARACTER SET utf8mb4
--     COLLATE utf8mb4_unicode_ci;

-- USE job_portal;

-- -- ---------------------------------------------------------------------
-- -- Table: users
-- -- ---------------------------------------------------------------------
-- DROP TABLE IF EXISTS applications;
-- DROP TABLE IF EXISTS jobs;
-- DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name            VARCHAR(100) NOT NULL,
    email           VARCHAR(190) NOT NULL,
    password_hash   VARCHAR(255) NOT NULL,
    role            ENUM('seeker','employer') NOT NULL DEFAULT 'seeker',
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: jobs
-- ---------------------------------------------------------------------
CREATE TABLE jobs (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    employer_id INT UNSIGNED NOT NULL,
    title       VARCHAR(150) NOT NULL,
    company     VARCHAR(150) NOT NULL,
    location    VARCHAR(150) NOT NULL,
    type         ENUM('Full-time','Part-time','Internship') NOT NULL DEFAULT 'Full-time',
    description  TEXT NOT NULL,
    salary       VARCHAR(100) DEFAULT NULL,
    created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_jobs_employer (employer_id),
    KEY idx_jobs_type (type),
    CONSTRAINT fk_jobs_employer
        FOREIGN KEY (employer_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Table: applications
-- ---------------------------------------------------------------------
CREATE TABLE applications (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    job_id       INT UNSIGNED NOT NULL,
    seeker_id    INT UNSIGNED NOT NULL,
    resume_path  VARCHAR(255) NOT NULL,
    status       ENUM('pending','reviewed','accepted','rejected')
                 NOT NULL DEFAULT 'pending',
    applied_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_applications_job_seeker (job_id, seeker_id),
    KEY idx_applications_seeker (seeker_id),
    KEY idx_applications_status (status),
    CONSTRAINT fk_applications_job
        FOREIGN KEY (job_id) REFERENCES jobs(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_applications_seeker
        FOREIGN KEY (seeker_id) REFERENCES users(id)
        ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
