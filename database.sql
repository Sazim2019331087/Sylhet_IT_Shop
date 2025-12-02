CREATE DATABASE ecomm;

USE ecomm;

CREATE TABLE bank_details(
    email VARCHAR(200) PRIMARY KEY,
    name VARCHAR(200),
    password VARCHAR(300),
    account_number VARCHAR(300),
    current_balance INT
);


CREATE TABLE customer_details(
    email VARCHAR(200) PRIMARY KEY,
    name VARCHAR(200),
    password VARCHAR(300),
    account_number VARCHAR(300) DEFAULT "NOT SET",
    secret VARCHAR(300) DEFAULT "NOT SET"
);


CREATE TABLE product_details(
    product_id VARCHAR(200) PRIMARY KEY,
    name VARCHAR(200),
    total_pieces INT,
    current_price INT
);

INSERT INTO product_details VALUES("111","Laptop",10,15000);
INSERT INTO product_details VALUES("222","Mobile",9,10000);
INSERT INTO product_details VALUES("333","Calculator",5,1000);

CREATE TABLE payment_details(
    payment_id VARCHAR(200) PRIMARY KEY,
    sender_account VARCHAR(200),
    receiver_account VARCHAR(200),
    amount VARCHAR(200),
    payment_time VARCHAR(200),
    status VARCHAR(200) DEFAULT "CANCELLED"
);

CREATE TABLE bank_payment_details(
    payment_id VARCHAR(200) PRIMARY KEY,
    sender_account VARCHAR(200),
    receiver_account VARCHAR(200),
    amount VARCHAR(200),
    payment_time VARCHAR(200),
    status VARCHAR(200) DEFAULT "CANCELLED"
);

CREATE TABLE order_details(
    payment_id VARCHAR(200) PRIMARY KEY,
    laptop INT,
    mobile INT,
    calculator INT,
    payment_time VARCHAR(200),
    delivery_time VARCHAR(200),
    destination  VARCHAR(500),
    status VARCHAR(200) DEFAULT "ORDER CONFIRMED"
);

CREATE TABLE otp_verification (
    email VARCHAR(200) PRIMARY KEY,
    otp VARCHAR(10) NOT NULL,
    expires BIGINT NOT NULL
);

CREATE TABLE otp_verification_bank (
    email VARCHAR(200) PRIMARY KEY,
    otp VARCHAR(10) NOT NULL,
    expires BIGINT NOT NULL
);

