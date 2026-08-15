-- ================================================================
-- ELMS Seed Data
-- Default admin, a sample product, and an API key for testing.
--
-- Default admin login:
--   username: admin
--   password: Admin@12345   <-- CHANGE THIS AFTER FIRST LOGIN
--
-- NOTE: password_hash below is a bcrypt of "Admin@12345".
--       Prefer running `php scripts/install.php` which generates
--       a fresh hash and random API secret instead of this file.
-- ================================================================

INSERT INTO `admin_users` (`name`, `email`, `username`, `password_hash`, `role`, `status`)
VALUES ('Administrator', 'admin@example.com', 'admin',
        '$2y$10$P5NlPMDfoG36HOphmbohyuCQN3EEmb65oKBe86ok.jOnvpC9iY1Ia',
        'admin', 'active');

INSERT INTO `products` (`product_name`, `product_key`, `description`, `latest_version`, `status`)
VALUES ('WHMCS OTP Module', 'WHMCS-OTP', 'Email-based OTP 2FA module for WHMCS.', '1.0.0', 'active');

INSERT INTO `api_keys` (`name`, `api_key`, `secret_key`, `status`)
VALUES ('Default WHMCS Integration',
        'elms_pk_2b7c1f9a4d6e8032a1c5b9e7f3d20481',
        'elms_sk_9f83a1c04e7b2d6598301fbca5e47d29b8460af1c2d3e5f7',
        'active');
