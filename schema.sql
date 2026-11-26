CREATE TABLE IF NOT EXISTS payments (
  id CHAR(32) PRIMARY KEY,
  request_id CHAR(36) NOT NULL UNIQUE,
  service TEXT NOT NULL CHECK (service IN ('mobile', 'electricity', 'internet')),
  reference VARCHAR(32) NOT NULL,
  amount INTEGER NOT NULL CHECK (amount BETWEEN 100 AND 50000),
  created_at TIMESTAMPTZ NOT NULL DEFAULT CURRENT_TIMESTAMP
);
