CREATE TABLE `psss_seasons_h` (
  `season_h` int unsigned NOT NULL default '1900',
  PRIMARY KEY  (`season_h`),
  UNIQUE KEY `season_h` (`season_h`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_config_sources` (
  `id` int unsigned NOT NULL,
  `type` varchar(64) NOT NULL default 'html',
  `source` varchar(191) NOT NULL,
  `league_name` varchar(128) NOT NULL default '',
  `delete` tinyint unsigned default NULL,
  `enabled` tinyint unsigned NOT NULL,
  `idx` int NOT NULL default '0',
  `date` int unsigned default NULL,
  PRIMARY KEY  (`id`),
  KEY `idx` (`idx`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_config` (
  `id` int unsigned NOT NULL default '0',
  `conftype` varchar(20) NOT NULL default 'main',
  `section` varchar(70) default NULL,
  `var` varchar(100) default NULL,
  `value` text NOT NULL,
  `label` varchar(128) default NULL,
  `type` enum('none','text','textarea','checkbox','select','boolean') NOT NULL default 'text',
  `locked` tinyint unsigned NOT NULL default '0',
  `verifycodes` varchar(64) default NULL,
  `options` text,
  `help` text,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `conftype` (`conftype`,`section`,`var`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_config_awards` (
  `id` int unsigned NOT NULL default '0',
  `enabled` tinyint unsigned NOT NULL default '1',
  `idx` int NOT NULL default '0',
  `negative` tinyint unsigned NOT NULL default '0',
  `name` varchar(128) NOT NULL default '',
  `groupname` varchar(128) NOT NULL default '',
  `phrase` varchar(191) NOT NULL,
  `expr` varchar(191) NOT NULL default '',
  `order` enum('desc','asc') NOT NULL default 'desc',
  `where` varchar(191) NOT NULL default '',
  `limit` smallint unsigned NOT NULL default '5',
  `format` varchar(64) NOT NULL default '',
  `description` text NOT NULL,
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_config_themes` (
  `name` varchar(128) NOT NULL,
  `parent` varchar(128) default NULL,
  `enabled` tinyint unsigned NOT NULL,
  `version` varchar(32) NOT NULL default '1.0',
  `title` varchar(128) NOT NULL,
  `author` varchar(128) default NULL,
  `website` varchar(128) default NULL,
  `source` varchar(191) default NULL,
  `image` varchar(191) default NULL,
  `description` text,
  PRIMARY KEY  (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_errlog` (
  `id` int unsigned NOT NULL default '0',
  `timestamp` int unsigned NOT NULL default '0',
  `severity` enum('info','warning','fatal') NOT NULL default 'info',
  `userid` int unsigned default NULL,
  `msg` text NOT NULL,
  PRIMARY KEY  (`id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_team` (
  `team_id` int unsigned NOT NULL default '0',
  `rank` mediumint unsigned NOT NULL default '0',
  `prevrank` mediumint unsigned NOT NULL default '0',
  `allowrank` tinyint unsigned NOT NULL default '1',
  PRIMARY KEY  (`team_id`),
  UNIQUE KEY `team_id` (`team_id`),
  KEY `allowrank` (`allowrank`),
  KEY `rank` (`rank`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_team_aliases` (
  `id` int unsigned NOT NULL default '0',
  `team_id` varchar(128) NOT NULL default '',
  `alias` varchar(128) NOT NULL default '',
  PRIMARY KEY  (`id`),
  KEY `team_id` (`team_id`),
  KEY `alias` (`alias`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_team_data` (
  `season` smallint unsigned default NULL,
  `team_id` int unsigned NOT NULL default '0',
  `wins_streak` smallint unsigned NOT NULL default '0',
  `losses_streak` smallint unsigned NOT NULL default '0',
  `games` smallint unsigned NOT NULL default '0',
  PRIMARY KEY  (`season`,`team_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_team_ids_name` (
  `team_id` int unsigned NOT NULL default '0',
  `team_name` varchar(128) NOT NULL default '',
  `totaluses` int unsigned NOT NULL default '1',
  `firstseen` datetime NOT NULL,
  `lastseen` datetime NOT NULL,
  PRIMARY KEY  (`team_id`,`team_name`),
  UNIQUE KEY `team_name` (`team_id`,`team_name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_team_profile` (
  `team_id` varchar(128) NOT NULL default '',
  `userid` int unsigned default NULL,
  `name` varchar(128) NOT NULL default '',
  `email` varchar(128) default NULL,
  `discord` varchar(191) default NULL,
  `twitch` varchar(191) default NULL,
  `youtube` varchar(191) default NULL,
  `website` varchar(191) default NULL,
  `icon` varchar(64) default NULL,
  `cc` varchar(2) default NULL,
  `logo` text,
  `namelocked` tinyint unsigned NOT NULL default '1',
  PRIMARY KEY  (`team_id`),
  KEY `cc` (`cc`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_plugins` (
  `plugin` varchar(64) NOT NULL,
  `version` varchar(32) NOT NULL,
  `enabled` tinyint unsigned NOT NULL default '0',
  `idx` smallint NOT NULL default '0',
  `installdate` int unsigned NOT NULL default '0',
  `description` text NOT NULL,
  PRIMARY KEY  (`plugin`),
  KEY `enabled` (`enabled`,`idx`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_search_results` (
  `search_id` char(32) NOT NULL,
  `session_id` char(32) NOT NULL,
  `phrase` varchar(191) NOT NULL,
  `result_total` int unsigned NOT NULL default '0',
  `abs_total` int unsigned NOT NULL default '0',
  `results` text,
  `query` text,
  `updated` datetime NOT NULL,
  PRIMARY KEY  (`search_id`),
  KEY `session_id` (`session_id`),
  KEY `updated` (`updated`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_sessions` (
  `session_id` char(32) NOT NULL default '',
  `session_userid` int unsigned NOT NULL default '0',
  `session_start` int unsigned NOT NULL default '0',
  `session_last` int unsigned NOT NULL default '0',
  `session_ip` int unsigned NOT NULL default '0',
  `session_logged_in` tinyint NOT NULL default '0',
  `session_is_admin` tinyint unsigned NOT NULL default '0',
  `session_is_bot` tinyint unsigned NOT NULL default '0',
  `session_key` char(32) default NULL,
  `session_key_time` int unsigned default NULL,
  PRIMARY KEY  (`session_id`),
  KEY `session_userid` (`session_userid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_state` (
  `source` int unsigned NOT NULL,
  `lastupdate` int unsigned NOT NULL default '0',
  `season_c` int unsigned NOT NULL default '1900',
  PRIMARY KEY  (`source`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
CREATE TABLE `psss_user` (
  `userid` int unsigned NOT NULL default '0',
  `username` varchar(64) NOT NULL default '',
  `password` varchar(32) NOT NULL default '',
  `session_last` int unsigned NOT NULL default '0',
  `session_login_key` varchar(8) default NULL,
  `lastvisit` int unsigned NOT NULL default '0',
  `accesslevel` tinyint NOT NULL default '1',
  `confirmed` tinyint unsigned NOT NULL default '0',
  PRIMARY KEY  (`userid`),
  UNIQUE KEY `username` (`username`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;
