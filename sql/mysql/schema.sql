DROP TABLE IF EXISTS `responsys_log`;
CREATE TABLE `responsys_log` (
  `id` int(11) unsigned NOT NULL auto_increment,
  `request_uri` TEXT DEFAULT NULL,
  `request` TEXT DEFAULT NULL,
  `request_headers` TEXT DEFAULT NULL,
  `response_status` INT(3) UNSIGNED NOT NULL DEFAULT 0,
  `response_headers` TEXT DEFAULT NULL,
  `response_time` FLOAT(7,4) UNSIGNED NOT NULL DEFAULT 0,
  `response_error` VARCHAR(255) DEFAULT NULL,
  `response` text DEFAULT NULL,
  `date` int(11) UNSIGNED NOT NULL,
  `backtrace` text default NULL,
  PRIMARY KEY  (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
