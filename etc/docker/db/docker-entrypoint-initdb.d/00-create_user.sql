CREATE USER 'webapp'@'%' IDENTIFIED BY 'jiD7mkaS72';
CREATE DATABASE monelytics DEFAULT CHARACTER SET utf8mb4;
CREATE DATABASE monelytics_testing DEFAULT CHARACTER SET utf8mb4;
GRANT ALL ON monelytics.* TO 'webapp'@'%';
GRANT ALL ON monelytics_testing.* TO 'webapp'@'%';
