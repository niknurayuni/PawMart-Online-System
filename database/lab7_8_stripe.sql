ALTER TABLE orders
  ADD COLUMN payment_status ENUM('legacy','unpaid','paid','expired') NOT NULL DEFAULT 'legacy',
  ADD COLUMN stripe_session_id VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin NULL,
  ADD UNIQUE INDEX uq_orders_stripe_session (stripe_session_id);
