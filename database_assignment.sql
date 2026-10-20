-- EE 470 - Database Assignment
-- Miguel Valentin
-- Part 1: MySQL Database and Sensor Tables

-- ========================================
-- 1. CREATE BOOK TABLE
-- ========================================

CREATE TABLE BOOK (
    id INT NOT NULL AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    PRIMARY KEY (id)
);

-- Insert book information
INSERT INTO BOOK (name)
VALUES
('MariaDB Book'),
('SQLDB Book');


-- ========================================
-- 2. CREATE PRICE TABLE
-- ========================================

CREATE TABLE Price (
    id INT NOT NULL,
    price DECIMAL(6,2) NOT NULL,
    PRIMARY KEY (id),
    FOREIGN KEY (id) REFERENCES BOOK(id)
);

-- Insert book prices
INSERT INTO Price (id, price)
VALUES
(1, 250.00),
(2, 223.00);


-- ========================================
-- 3. CREATE SENSOR REGISTER TABLE
-- ========================================

CREATE TABLE sensor_register (
    node_name VARCHAR(10) NOT NULL,
    manufacturer VARCHAR(10) NOT NULL,
    longitude DECIMAL(15,10) NOT NULL,
    latitude DECIMAL(15,10) NOT NULL,
    PRIMARY KEY (node_name)
);

-- Register five sensors
INSERT INTO sensor_register
(node_name, manufacturer, longitude, latitude)
VALUES
('node_1', 'Bosch', -122.4580, 38.2919),
('node_2', 'Siemens', -122.7141, 38.4404),
('node_3', 'Honeywell', -122.2869, 38.2975),
('node_4', 'TexasInst', -122.5311, 38.1074),
('node_5', 'Sensirion', -122.0408, 37.3688);


-- ========================================
-- 4. CREATE SENSOR DATA TABLE
-- ========================================

CREATE TABLE sensor_data (
    node_name VARCHAR(10) NOT NULL,

    time_received DATETIME NOT NULL
        DEFAULT CURRENT_TIMESTAMP,

    temperature DECIMAL(6,2) NOT NULL,
    humidity DECIMAL(6,2) NOT NULL,

    PRIMARY KEY (node_name, time_received),

    FOREIGN KEY (node_name)
        REFERENCES sensor_register(node_name),

    CONSTRAINT chk_temperature
        CHECK (temperature BETWEEN -10 AND 100),

    CONSTRAINT chk_humidity
        CHECK (humidity BETWEEN 0 AND 100)
);


-- ========================================
-- 5. INSERT SENSOR DATA
-- ========================================

-- Each sensor sends four readings,
-- spaced 30 minutes apart.

INSERT INTO sensor_data
(node_name, time_received, temperature, humidity)
VALUES

-- Node 1
('node_1', '2022-10-01 11:00:00', 22.50, 45.00),
('node_1', '2022-10-01 11:30:00', 23.10, 46.50),
('node_1', '2022-10-01 12:00:00', 24.00, 44.20),
('node_1', '2022-10-01 12:30:00', 23.80, 43.50),

-- Node 2
('node_2', '2022-10-01 11:00:00', 25.20, 50.00),
('node_2', '2022-10-01 11:30:00', 25.80, 51.20),
('node_2', '2022-10-01 12:00:00', 26.10, 49.80),
('node_2', '2022-10-01 12:30:00', 26.50, 48.50),

-- Node 3
('node_3', '2022-10-01 11:00:00', 20.10, 60.00),
('node_3', '2022-10-01 11:30:00', 20.80, 59.50),
('node_3', '2022-10-01 12:00:00', 21.40, 58.20),
('node_3', '2022-10-01 12:30:00', 22.00, 57.00),

-- Node 4
('node_4', '2022-10-01 11:00:00', 28.00, 40.00),
('node_4', '2022-10-01 11:30:00', 28.50, 41.20),
('node_4', '2022-10-01 12:00:00', 29.10, 39.80),
('node_4', '2022-10-01 12:30:00', 29.50, 38.50),

-- Node 5
('node_5', '2022-10-01 11:00:00', 19.50, 65.00),
('node_5', '2022-10-01 11:30:00', 20.00, 64.20),
('node_5', '2022-10-01 12:00:00', 20.60, 63.50),
('node_5', '2022-10-01 12:30:00', 21.10, 62.80);


-- ========================================
-- 6. CREATE COMBINED SENSOR VIEW
-- ========================================

CREATE VIEW sensor_combined AS
SELECT
    d.node_name,
    r.manufacturer,
    r.longitude,
    r.latitude,
    d.time_received,
    d.temperature,
    d.humidity
FROM sensor_data AS d
INNER JOIN sensor_register AS r
    ON d.node_name = r.node_name;


-- ========================================
-- 7. DISPLAY COMBINED SENSOR DATA
-- ========================================

SELECT *
FROM sensor_combined
ORDER BY node_name, time_received;
