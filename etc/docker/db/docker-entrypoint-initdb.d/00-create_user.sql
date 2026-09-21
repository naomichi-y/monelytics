CREATE USER 'webapp'@'%' IDENTIFIED BY '***REMOVED***';
CREATE DATABASE monelytics DEFAULT CHARACTER SET utf8mb4;
CREATE DATABASE monelytics_testing DEFAULT CHARACTER SET utf8mb4;
CREATE DATABASE monelytics_e2e DEFAULT CHARACTER SET utf8mb4;
GRANT ALL ON monelytics.* TO 'webapp'@'%';
GRANT ALL ON monelytics_testing.* TO 'webapp'@'%';
GRANT ALL ON monelytics_e2e.* TO 'webapp'@'%';
