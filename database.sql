-- --------------------------------------------------------
-- Database: ecomm
-- System: Sylhet IT Shop
-- --------------------------------------------------------

CREATE DATABASE IF NOT EXISTS ecomm;
USE ecomm;

-- --------------------------------------------------------
-- Table: bank_details
-- Description: Stores account information for the partner banking system ("SUSTainable Bank").
-- This serves as the 'users' table for the bank portal.
-- --------------------------------------------------------
CREATE TABLE bank_details(
    email VARCHAR(200) PRIMARY KEY COMMENT 'Unique identifier for bank login',
    name VARCHAR(200) COMMENT 'Account holder name',
    password VARCHAR(300) COMMENT 'Hashed password. NULL if registered via Google',
    account_number VARCHAR(300) COMMENT 'Unique bank account ID used for internal transfers',
    current_balance INT COMMENT 'Current funds in BDT',
    google_id VARCHAR(255) COMMENT 'Google OAuth ID if applicable'
);

-- --------------------------------------------------------
-- Table: customer_details
-- Description: Stores profile and authentication data for e-commerce customers.
-- --------------------------------------------------------
CREATE TABLE customer_details(
    email VARCHAR(200) PRIMARY KEY COMMENT 'Unique identifier for shop login',
    name VARCHAR(200) COMMENT 'Customer full name',
    password VARCHAR(300) COMMENT 'Hashed password. NULL if registered via Google',
    account_number VARCHAR(300) DEFAULT "NOT SET" COMMENT 'Linked SUSTainable Bank account number',
    secret VARCHAR(300) DEFAULT "NOT SET" COMMENT 'Transaction PIN for the linked bank account',
    google_id VARCHAR(255) COMMENT 'Google OAuth ID if applicable'
);

-- --------------------------------------------------------
-- Table: product_details
-- Description: The inventory catalog. Tracks stock levels and pricing.
-- --------------------------------------------------------
CREATE TABLE product_details(
    product_id VARCHAR(200) PRIMARY KEY COMMENT 'Unique SKU (e.g., 111)',
    name VARCHAR(200) COMMENT 'Product display name',
    total_pieces INT COMMENT 'Current available stock quantity',
    current_price INT COMMENT 'Unit price in BDT'
);

-- Seed Data for Products
INSERT INTO product_details VALUES("111","Laptop",10,15000);
INSERT INTO product_details VALUES("222","Mobile",9,10000);
INSERT INTO product_details VALUES("333","Calculator",5,1000);

-- --------------------------------------------------------
-- Table: payment_details
-- Description: The primary financial ledger for the E-Commerce system.
-- Records both Bank transfers and Stripe payments.
-- --------------------------------------------------------
CREATE TABLE payment_details(
    payment_id VARCHAR(200) PRIMARY KEY COMMENT 'Unique Transaction ID (e.g., PID-123 or stripe-xyz)',
    sender_account VARCHAR(200) COMMENT 'Source of funds. Can be Bank Acc No. or "Stripe | email"',
    receiver_account VARCHAR(200) COMMENT 'Destination account (Admin/Supplier)',
    amount VARCHAR(200) COMMENT 'Transaction amount',
    payment_time VARCHAR(200) COMMENT 'Timestamp string of transaction',
    status VARCHAR(200) DEFAULT "CANCELLED" COMMENT 'State: PENDING, SUCCESSFUL, FAILED'
);

-- --------------------------------------------------------
-- Table: bank_payment_details
-- Description: Internal ledger specific to the Bank system.
-- Used to show transaction history within the Bank Portal.
-- --------------------------------------------------------
CREATE TABLE bank_payment_details(
    payment_id VARCHAR(200) PRIMARY KEY,
    sender_account VARCHAR(200),
    receiver_account VARCHAR(200),
    amount VARCHAR(200),
    payment_time VARCHAR(200),
    status VARCHAR(200) DEFAULT "CANCELLED"
);

-- --------------------------------------------------------
-- Table: order_details
-- Description: Fulfillment information linking a payment to specific items.
-- --------------------------------------------------------
CREATE TABLE order_details(
    payment_id VARCHAR(200) PRIMARY KEY COMMENT 'Foreign Key -> payment_details.payment_id',
    laptop INT COMMENT 'Qty of laptops',
    mobile INT COMMENT 'Qty of mobiles',
    calculator INT COMMENT 'Qty of calculators',
    payment_time VARCHAR(200) COMMENT 'Order placement time',
    delivery_time VARCHAR(200) COMMENT 'Time of delivery (or TBA)',
    destination  VARCHAR(500) COMMENT 'Shipping address',
    status VARCHAR(200) DEFAULT "ORDER CONFIRMED" COMMENT 'Logistics state: PENDING, CONFIRMED, DELIVERED'
);

-- --------------------------------------------------------
-- Table: otp_verification
-- Description: Temporary OTPs for E-Commerce password resets and bank linking.
-- --------------------------------------------------------
CREATE TABLE otp_verification (
    email VARCHAR(200) PRIMARY KEY COMMENT 'User email requesting OTP',
    otp VARCHAR(10) NOT NULL COMMENT '6-digit code',
    expires BIGINT NOT NULL COMMENT 'UNIX timestamp for expiration'
);

-- --------------------------------------------------------
-- Table: otp_verification_bank
-- Description: Temporary OTPs specifically for Bank Account password resets.
-- --------------------------------------------------------
CREATE TABLE otp_verification_bank (
    email VARCHAR(200) PRIMARY KEY COMMENT 'Bank user email requesting OTP',
    otp VARCHAR(10) NOT NULL COMMENT '6-digit code',
    expires BIGINT NOT NULL COMMENT 'UNIX timestamp for expiration'
);
