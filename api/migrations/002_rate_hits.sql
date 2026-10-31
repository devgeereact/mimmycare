-- 002: rate-limit strikes (lib/rate_limit.php). k is a sha256 of the limit key, so no IP is stored in clear.
-- Rows older than a day are pruned by the API itself.
CREATE TABLE IF NOT EXISTS rate_hits (
  id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  k CHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_rate_hits_k_created (k, created_at),
  KEY idx_rate_hits_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=ascii COLLATE=ascii_bin;
