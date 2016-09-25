CREATE USER 'webapp'@'%' IDENTIFIED BY 'jiD7mkaS72';
CREATE DATABASE monelytics DEFAULT CHARACTER SET utf8mb4;
GRANT ALL ON monelytics.* TO 'webapp'@'%';
