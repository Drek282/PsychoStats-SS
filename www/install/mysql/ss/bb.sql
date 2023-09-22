CREATE TABLE `psss_team_adv` (
  `season` smallint unsigned default NULL,
  `team_id` smallint unsigned NOT NULL default '0',
  `divisionname` varchar(128) NOT NULL default '',
  `games_played` smallint unsigned NOT NULL default '0',
  `wins` smallint unsigned NOT NULL default '0',
  `losses` smallint unsigned NOT NULL default '0',
  `win_percent` decimal(4,3) NOT NULL default '0.000',
  `games_back` varchar(64) NOT NULL default '-',
  `team_rdiff` decimal(4,2) NOT NULL default '0.00',
  `pythag` decimal(4,3) NOT NULL default '0.000',
  `pythag_plus` decimal(4,3) NOT NULL default '0.000',
  PRIMARY KEY  (`season`,`team_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE `psss_team_def` (
  `season` smallint unsigned default NULL,
  `team_id` smallint unsigned NOT NULL default '0',
  `team_era` decimal(4,2) NOT NULL default '0.00',
  `team_ra` decimal(4,2) NOT NULL default '0.00',
  `complete_games` smallint unsigned NOT NULL default '0',
  `shutouts` smallint unsigned NOT NULL default '0',
  `team_saves` smallint unsigned NOT NULL default '0',
  `innings_pitched` decimal(5,1) NOT NULL default '0.0',
  `total_runs_against` smallint unsigned NOT NULL default '0',
  `total_earned_runs_against` smallint unsigned NOT NULL default '0',
  `hits_surrendered` smallint unsigned NOT NULL default '0',
  `opp_batting_average` decimal(4,3) NOT NULL default '0.000',
  `opp_walks` smallint unsigned NOT NULL default '0',
  `team_whip` decimal(4,2) NOT NULL default '0.00',
  `opp_strikeouts` smallint unsigned NOT NULL default '0',
  `outstanding_plays` smallint unsigned NOT NULL default '0',
  `double_plays_turned` smallint unsigned NOT NULL default '0',
  `fielding_errors` smallint unsigned NOT NULL default '0',
  `team_wild_pitches` smallint unsigned NOT NULL default '0',
  `passed_balls` smallint unsigned NOT NULL default '0',
  `opp_stolen_bases` smallint unsigned NOT NULL default '0',
  `opp_caught_stealing` smallint unsigned NOT NULL default '0',
  `team_drat` decimal(4,2) NOT NULL default '0.00',
  PRIMARY KEY  (`season`,`team_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE `psss_team_off` (
  `season` smallint unsigned default NULL,
  `team_id` smallint unsigned NOT NULL default '0',
  `run_support` decimal(3,1) unsigned NOT NULL default '0.0',
  `at_bats` smallint unsigned NOT NULL default '0',
  `runs` smallint unsigned NOT NULL default '0',
  `hits` smallint unsigned NOT NULL default '0',
  `doubles` smallint unsigned NOT NULL default '0',
  `triples` smallint unsigned NOT NULL default '0',
  `home_runs` smallint unsigned NOT NULL default '0',
  `team_rbis` smallint unsigned NOT NULL default '0',
  `walks` smallint unsigned NOT NULL default '0',
  `strikeouts` smallint unsigned NOT NULL default '0',
  `batting_average` decimal(4,3) NOT NULL default '0.000',
  `on_base_average` decimal(4,3) NOT NULL default '0.000',
  `slugging_average` decimal(4,3) NOT NULL default '0.000',
  `ops` decimal(4,3) NOT NULL default '0.000',
  `woba` decimal(4,3) NOT NULL default '0.000',
  `sacrifice_hits` smallint unsigned NOT NULL default '0',
  `sacrifice_fails` smallint unsigned NOT NULL default '0',
  `sacrifice_flies` smallint unsigned NOT NULL default '0',
  `gidps` smallint unsigned NOT NULL default '0',
  `stolen_bases` smallint unsigned NOT NULL,
  `caught_stealing` smallint unsigned NOT NULL default '0',
  `left_on_base` smallint unsigned NOT NULL default '0',
  `left_on_base_percent` decimal(3,2) NOT NULL default '0.00',
  `team_srat` decimal(4,2) NOT NULL default '0.00',
  PRIMARY KEY  (`season`,`team_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE `psss_team_wc` (
  `season` smallint unsigned default NULL,
  `team_id` smallint unsigned NOT NULL default '0',
  `games_back_wc` varchar(64) default NULL,
  PRIMARY KEY  (`season`,`team_id`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE `psss_team_rpi` (
  `season` smallint unsigned default NULL,
  `team_id` smallint unsigned NOT NULL default '0',
  `player_name` varchar(128) NOT NULL default '',
  `pi_wins` smallint unsigned NOT NULL default '0',
  `pi_losses` smallint unsigned NOT NULL default '0',
  `pi_win_percent` decimal(4,3) NOT NULL default '0.000',
  `pi_era` decimal(10,2) NOT NULL default '0.00',
  `pi_games_played` smallint unsigned NOT NULL default '0',
  `pi_games_started` smallint unsigned NOT NULL default '0',
  `pi_complete_games` smallint unsigned NOT NULL default '0',
  `pi_shutouts` smallint unsigned NOT NULL default '0',
  `pi_run_support` decimal(3,1) unsigned NOT NULL default '0.0',
  `pi_saves` smallint unsigned NOT NULL default '0',
  `pi_innings_pitched` decimal(5,1) NOT NULL default '0.0',
  `pi_runs_against` smallint unsigned NOT NULL default '0',
  `pi_earned_runs_against` smallint unsigned NOT NULL default '0',
  `pi_hits_surrendered` smallint unsigned NOT NULL default '0',
  `pi_opp_batting_average` decimal(4,3) NOT NULL default '0.000',
  `pi_opp_walks` smallint unsigned NOT NULL default '0',
  `pi_whip` decimal(4,2) NOT NULL default '0.00',
  `pi_strikeouts` smallint unsigned NOT NULL default '0',
  `pi_wild_pitches` smallint unsigned NOT NULL default '0',
  `pi_v` decimal(3,2) NOT NULL default '0.00',
  PRIMARY KEY  (`season`,`team_id`,`player_name`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE `psss_team_rpo` (
  `season` smallint unsigned default NULL,
  `team_id` smallint unsigned NOT NULL default '0',
  `player_name` varchar(128) NOT NULL default '',
  `po_games_played` smallint unsigned NOT NULL default '0',
  `po_at_bats` smallint unsigned NOT NULL default '0',
  `po_runs` smallint unsigned NOT NULL default '0',
  `po_hits` smallint unsigned NOT NULL default '0',
  `po_doubles` smallint unsigned NOT NULL default '0',
  `po_triples` smallint unsigned NOT NULL default '0',
  `po_home_runs` smallint unsigned NOT NULL default '0',
  `po_rbis` smallint unsigned NOT NULL default '0',
  `po_walks` smallint unsigned NOT NULL default '0',
  `po_strikeouts` smallint unsigned NOT NULL default '0',
  `po_batting_average` decimal(4,3) NOT NULL default '0.000',
  `po_on_base_average` decimal(4,3) NOT NULL default '0.000',
  `po_slugging_average` decimal(4,3) NOT NULL default '0.000',
  `po_ops` decimal(4,3) NOT NULL default '0.000',
  `po_woba` decimal(4,3) NOT NULL default '0.000',
  `po_sacrifice_hits` smallint unsigned NOT NULL default '0',
  `po_sacrifice_fails` smallint unsigned NOT NULL default '0',
  `po_sacrifice_flies` smallint unsigned NOT NULL default '0',
  `po_gidps` smallint unsigned NOT NULL default '0',
  `po_stolen_bases` smallint unsigned NOT NULL,
  `po_caught_stealing` smallint unsigned NOT NULL default '0',
  `po_outstanding_plays` smallint unsigned NOT NULL default '0',
  `po_fielding_errors` smallint unsigned NOT NULL default '0',
  `po_passed_balls` smallint unsigned NOT NULL default '0',
  `po_v` decimal(3,2) NOT NULL default '0.00',
  PRIMARY KEY  (`season`,`team_id`,`player_name`)
) ENGINE=INNODB DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

-- Moved from defaults.sql.
INSERT INTO `psss_config` (`id`, `conftype`, `section`, `var`, `value`, `label`, `type`, `locked`, `verifycodes`, `options`, `help`) 
  VALUES 
    (1,'main',NULL,'meta_keywords','PsychoStats Team Statistics Scoresheet Baseball','Site HTML Meta Key Words','text',0,'','','These are the HTML meta key words for your PsychoStats.  They are used by search engines to provide search results.');

INSERT INTO `psss_config_awards` (`id`, `enabled`, `idx`, `negative`, `award_name`, `groupname`, `phrase`, `expr`, `order`, `where`, `limit`, `format`, `description`)
  VALUES
    (1, 1, 10, 0, 'Highest Single Season Team Win %', '', '{$team.link} has the highest historical single season win % at {$award.topteamvalue} in {$award.awardseason}', '{$win_percent}', 'desc', '{$games_played} > 81', 5, 'remzerodecimal', 'This Hall of Fame award is for the team with the highest single season winning percentage in league history.'),
    (2, 1, 20, 0, 'Highest Single Season Average Runs Scored per Game', '', '{$team.link} has the highest historical single season average runs scored per game at {$award.topteamvalue} in {$award.awardseason}', '{$run_support}', 'desc', '{$games_played} > 81', 5, '%.1f', 'This Hall of Fame award is for the team that has scored the highest average runs per game over a single season in league history.'),
    (3, 1, 30, 0, 'Lowest Single Season Runs Allowed per 9 Innings', '', '{$team.link} has allowed the fewest runs per 9 innings in a single season at {$award.topteamvalue} in {$award.awardseason}', '{$team_ra}', 'asc', '{$games_played} > 81', 5, '%.2f', 'This Hall of Fame award is for the team that has allowed the fewest runs per 9 innings in a single season in league history.'),
    (4, 1, 40, 0, 'Highest Single Season Team Run Differential', '', '{$team.link} has the highest historical single season team run differential per 9 innings at {$award.topteamvalue} in {$award.awardseason}', '{$team_rdiff}', 'desc', '{$games_played} > 81', 5, '%.2f', 'This Hall of Fame award is for the team with the highest single season run differential per 9 innings in league history.'),
    (5, 1, 50, 0, 'Highest Single Season Team Defensive Rating', '', '{$team.link} has the highest historical single season team defensive rating at {$award.topteamvalue} in {$award.awardseason}', '{$team_drat}', 'desc', '{$games_played} > 81', 5, '%.2f', 'This Hall of Fame award is for the team with the highest single season team defensive rating in league history.'),
    (6, 1, 60, 0, 'Highest Single Season Team Speed Rating', '', '{$team.link} has the highest historical single season team speed rating at {$award.topteamvalue} in {$award.awardseason}', '{$team_srat}', 'desc', '{$games_played} > 81', 5, '%.2f', 'This Hall of Fame award is for the team with the highest single season team speed rating in league history.');
