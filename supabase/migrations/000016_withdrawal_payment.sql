-- Creator withdrawal / payout requests (admin: Dreamland → Withdrawal Requests)

CREATE TABLE IF NOT EXISTS withdrawal_payment (
  id SERIAL PRIMARY KEY,
  user_id INTEGER NOT NULL,
  transaction_id VARCHAR(256),
  amount DOUBLE PRECISION NOT NULL DEFAULT 0,
  description VARCHAR(256),
  created_at INTEGER,
  created_by INTEGER,
  updated_at INTEGER,
  updated_by INTEGER,
  status INTEGER NOT NULL DEFAULT 1
);

CREATE INDEX IF NOT EXISTS idx_withdrawal_payment_user ON withdrawal_payment (user_id);
CREATE INDEX IF NOT EXISTS idx_withdrawal_payment_status ON withdrawal_payment (status);
