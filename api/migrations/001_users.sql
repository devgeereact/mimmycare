-- 001: people who have signed in with Clerk. One row per Clerk user (the session token's "sub").
-- email and name are copied from verified token claims and refreshed on each GET /api/me.
CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  clerk_user_id VARCHAR(64) NOT NULL,
  email VARCHAR(190) NULL,
  name VARCHAR(190) NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  last_seen_at DATETIME NULL,
  UNIQUE KEY uq_users_clerk_user_id (clerk_user_id),
  KEY idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
