CREATE USER 'webapp'@'%' IDENTIFIED BY '***REMOVED***';
CREATE DATABASE monelytics DEFAULT CHARACTER SET utf8mb4;
GRANT ALL ON monelytics.* TO 'webapp'@'%';
