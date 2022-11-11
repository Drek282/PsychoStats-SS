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
  PRIMARY KEY  (`season`,`team_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

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
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

CREATE TABLE `psss_team_wc` (
  `season` smallint unsigned default NULL,
  `team_id` smallint unsigned NOT NULL default '0',
  `games_back_wc` varchar(64) default NULL,
  PRIMARY KEY  (`season`,`team_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE utf8mb4_general_ci;

-- Moved from defaults.sql.
INSERT INTO `psss_config` (`id`, `conftype`, `section`, `var`, `value`, `label`, `type`, `locked`, `verifycodes`, `options`, `help`) 
    VALUES 
        (1,'main',NULL,'meta_keywords','PsychoStats Team Statistics Scoresheet Baseball','Site HTML Meta Key Words','text',0,'','','These are the HTML meta key words for your PsychoStats.  They are used by search engines to provide search results.'),
        (10001,'theme','gloss_main','wper','<strong>Win \%</strong>:  Team wins divided by total games.','Win Percentage','textarea',0,'','','Glossary entry for Win \%.  It uses html formatting.'),
        (10002,'theme','gloss_main','rdiff','<strong>Run Differential</strong>:  Total team runs scored minus runs score against per 9 innings.','Run Differential','textarea',0,'','','Glossary entry for Run Differential.  It uses html formatting.'),
        (10100,'theme','gloss_def',NULL,'Glossary of abbreviations and acronyms used in the defensive team stats.','Glossary for Defensive Stats','none',1,NULL,NULL,NULL),
        (10101,'theme','gloss_def','era','<strong>ERA</strong>:  Team average earned runs against per 9 innings.','Earned Run Average','textarea',0,'','','Glossary entry for ERA.  It uses html formatting.'),
        (10102,'theme','gloss_def','ra','<strong>RA</strong>:  Team average total runs against per 9 innings.','Run Average','textarea',0,'','','Glossary entry for RA.  It uses html formatting.'),
        (10103,'theme','gloss_def','cg','<strong>Complete Games</strong>:  Number of complete games your pitchers have pitched.  This is not a meaningful stat because it includes complete games pitched by Pitcher AAA.','Complete Games','textarea',0,'','','Glossary entry for Complete Games.  It uses html formatting.'),
        (10104,'theme','gloss_def','so','<strong>Shutouts</strong>:  Number of total team shutout games.','Shutouts','textarea',0,'','','Glossary entry for Shutouts.  It uses html formatting.'),
        (10105,'theme','gloss_def','sv','<strong>Team Saves</strong>:  Number of total team saves.','Saves','textarea',0,'','','Glossary entry for Team Saves.  It uses html formatting.'),
        (10106,'theme','gloss_def','ip','<strong>IP</strong>:  Team total number of innings pitched. \".1\" indicates 1/3, one out, in an inning.','Innings Pitched','textarea',0,'','','Glossary entry for IP.  It uses html formatting.'),
        (10107,'theme','gloss_def','tra','<strong>TRA</strong>:  Total runs scored against.','Total Runs Against','textarea',0,'','','Glossary entry for TRA.  It uses html formatting.'),
        (10108,'theme','gloss_def','tera','<strong>TERA</strong>:  Total earned runs scored against.','Total Earned Runs Against','textarea',0,'','','Glossary entry for TERA.  It uses html formatting.'),
        (10109,'theme','gloss_def','ths','<strong>Hits</strong>','Total Hits Surrendered','textarea',0,'','','Glossary entry for Hits.  It uses html formatting.'),
        (10110,'theme','gloss_def','baa','<strong>BAA</strong>','Batting Average Against','textarea',0,'','','Glossary entry for BAA.  It uses html formatting.'),
        (10111,'theme','gloss_def','bbs','<strong>BBA</strong>','Walks Surrendered','textarea',0,'','','Glossary entry for BBA.  It uses html formatting.'),
        (10112,'theme','gloss_def','oso','<strong>K</strong>','Opponent Strikeouts','textarea',0,'','','Glossary entry for K.  It uses html formatting.'),
        (10113,'theme','gloss_def','op','<strong>OP</strong>:  This is a stat specific to Scoresheet Baseball and represents the number of total team outstanding defensive plays.  It is based on the total team range rating for all fielders.  Outstanding plays are the ying to the yang of errors.','Outstanding Plays','textarea',0,'','','Glossary entry for OP.  It uses html formatting.'),
        (10114,'theme','gloss_def','dp','<strong>DP</strong>:  The total number of team double plays turned.  This is significant in Scoresheet baseball because half of the range rating of your second base and shortstop teams is a rating that determines how many double plays they will turn.','Double Plays Turned','textarea',0,'','','Glossary entry for DP.  It uses html formatting.'),
        (10115,'theme','gloss_def','e','<strong>E</strong>:  The total number of team fielding errors.','Fielding Errors','textarea',0,'','','Glossary entry for E.  It uses html formatting.'),
        (10116,'theme','gloss_def','wp','<strong>WP</strong>:  The total number of wild pitches committed by pitchers on the given team.','Wild Pitches','textarea',0,'','','Glossary entry for WP.  It uses html formatting.'),
        (10117,'theme','gloss_def','pb','<strong>PB</strong>:  The total number of passed ball errors committed by catchers on the given team.','Passed Balls','textarea',0,'','','Glossary entry for PB.  It uses html formatting.'),
        (10118,'theme','gloss_def','osb','<strong>OSB</strong>:  The team total number of stolen bases allowed.  In Scoresheet Baseball this is a product of both the base stealing ability of the runner and the catcher\'s defensive ratings.','Opponent Stolen Bases','textarea',0,'','','Glossary entry for OSB.  It uses html formatting.'),
        (10119,'theme','gloss_def','ocs','<strong>OCS</strong>:  The team total number of runners thrown out while attempting to steal a base.  In Scoresheet Baseball this is a product of both the base stealing ability of the runner and the catcher\'s defensive ratings.','Opponent Caught Stealing','textarea',0,'','','Glossary entry for OCS.  It uses html formatting.'),
        (10120,'theme','gloss_def','drat','<strong>DRAT</strong>:  This is a rating that combines all the team defensive statistics into a single number intended to roughly represent the number of defensive runs saved per 9 innings.  There is likely no way to test it\'s accuracy, but it should still be useful for comparison purposes.  This rating does not include wild pitches.','Team Defensive Rating','textarea',0,'','','Glossary entry for DRAT.  It uses html formatting.'),
        (10200,'theme','gloss_off',NULL,'Glossary of abbreviations and acronyms used in the offensive team stats.','Glossary for Offensive Stats','none',1,NULL,NULL,NULL),
        (10201,'theme','gloss_off','rs','<strong>Run Support</strong>:  Team runs scored per 9 innings.','Run Support','textarea',0,'','','Glossary entry for RS.  It uses html formatting.'),
        (10202,'theme','gloss_off','ab','<strong>AB</strong>:  Total team at bats.','At Bats','textarea',0,'','','Glossary entry for AB.  It uses html formatting.'),
        (10203,'theme','gloss_off','r','<strong>R</strong>:  Total team runs.','Runs','textarea',0,'','','Glossary entry for R.  It uses html formatting.'),
        (10204,'theme','gloss_off','h','<strong>H</strong>:  Total team hits.','Hits','textarea',0,'','','Glossary entry for H.  It uses html formatting.'),
        (10205,'theme','gloss_off','d','<strong>Doubles</strong>:  Total team doubles.','Doubles','textarea',0,'','','Glossary entry for Doubles.  It uses html formatting.'),
        (10206,'theme','gloss_off','t','<strong>Triples</strong>:  Total team triples.','Triples','textarea',0,'','','Glossary entry for Triples.  It uses html formatting.'),
        (10207,'theme','gloss_off','hr','<strong>HR</strong>:  Total team home runs.','Home Runs','textarea',0,'','','Glossary entry for HR.  It uses html formatting.'),
        (10208,'theme','gloss_off','rbi','<strong>RBI</strong>:  Total team rbi.','Runs Batted In','textarea',0,'','','Glossary entry for RBI.  It uses html formatting.'),
        (10209,'theme','gloss_off','bb','<strong>BB</strong>:  Total team walks.','Base on Balls','textarea',0,'','','Glossary entry for BB.  It uses html formatting.'),
        (10210,'theme','gloss_off','k','<strong>K</strong>:  Total team strikeouts.','Strikeouts','textarea',0,'','','Glossary entry for K.  It uses html formatting.'),
        (10211,'theme','gloss_off','ba','<strong>BA</strong>:  Team batting average.','Batting Average','textarea',0,'','','Glossary entry for BA.  It uses html formatting.'),
        (10212,'theme','gloss_off','oba','<strong>OBA</strong>:  Team on base average.','On Base Average','textarea',0,'','','Glossary entry for OBA.  It uses html formatting.'),
        (10213,'theme','gloss_off','slg','<strong>SLG</strong>:  Team slugging average.','Slugging Average','textarea',0,'','','Glossary entry for SLG.  It uses html formatting.'),
        (10214,'theme','gloss_off','ops','<strong>OPS</strong>:  Team on base average plus slugging average.','On Base Plus Slugging','textarea',0,'','','Glossary entry for OPS.  It uses html formatting.'),
        (10215,'theme','gloss_off','sh','<strong>SH</strong>:  Team total sacrifice hits.','Sacrifice Hits','textarea',0,'','','Glossary entry for SH.  It uses html formatting.'),
        (10216,'theme','gloss_off','fsa','<strong>F</strong>:  Total total failed sacrifice attempts.','Failed Sacrifice Attempts','textarea',0,'','','Glossary entry for F.  It uses html formatting.'),
        (10217,'theme','gloss_off','sf','<strong>SF</strong>:  Team total sacrifice flies.','Sacrifice Flies','textarea',0,'','','Glossary entry for SF.  It uses html formatting.'),
        (10218,'theme','gloss_off','gidp','<strong>GIDP</strong>:  Team total grounded into double plays.','Grounded into Double Plays','textarea',0,'','','Glossary entry for GDP.  It uses html formatting.'),
        (10219,'theme','gloss_off','sb','<strong>SB</strong>:  Team total stolen bases.','Stolen Bases','textarea',0,'','','Glossary entry for SB.  It uses html formatting.'),
        (10220,'theme','gloss_off','cs','<strong>CS</strong>:  Total team team thrown out while attempting to steal a base.','Caught Stealing','textarea',0,'','','Glossary entry for CS.  It uses html formatting.'),
        (10221,'theme','gloss_off','lob','<strong>LOB</strong>:  Team total base runners stranded.','Left On Base','textarea',0,'','','Glossary entry for LOB.  It uses html formatting.'),
        (10222,'theme','gloss_off','lobper','<strong>LOB \%</strong>:  Runs scored divided by total base runners, HR are subtracted from total base runners and runs scored.  This number roughly represents the percentage of total team base runners which did not score.  This is not an exact number because it does not include base runners resulting from errors and HBP.','Left On Base Percentage','textarea',0,'','','Glossary entry for LOB\%.  It uses html formatting.'),
        (10223,'theme','gloss_off','srat','<strong>SRAT</strong>:  This is a rating that combines all the team statistics that are affected by base running into a single number intended to roughly represent the number of runs scored per 9 innings directly attributable to baserunning speed on the base paths.  There is likely no way to test it\'s accuracy, but it should still be useful for comparison purposes.','Team Speed Rating','textarea',0,'','','Glossary entry for SRAT.  It uses html formatting.');

INSERT INTO `psss_config_awards` (`id`, `enabled`, `idx`, `negative`, `name`, `groupname`, `phrase`, `expr`, `order`, `where`, `limit`, `format`, `description`)
  VALUES
    (1, 1, 10, 0, 'Highest Single Season Team Win %', '', '{$team.link} has the highest historical single season win % at {$award.topteamvalue} in {$award.awardseason}', '{$win_percent}', 'desc', '', 5, '', 'This Hall of Fame award is for the team with the highest single season winning percentage in league history.'),
    (2, 1, 20, 0, 'Highest Single Season Average Runs Scored per Game', '', '{$team.link} has the highest historical single season average runs scored per game at {$award.value} in {$award.awardseason}', '{$run_support}', 'desc', '', 5, '%.1f', 'This Hall of Fame award is for the team that has scored the highest average runs per game over a single season in league history.'),
    (3, 1, 30, 0, 'Lowest Single Season Runs Allowed per 9 Innings', '', '{$team.link} has allowed the fewest runs per 9 innings in a single season at {$award.value} in {$award.awardseason}', '{$team_ra}', 'asc', '', 5, '%.2f', 'This Hall of Fame award is for the team that has allowed the fewest runs per 9 innings in a single season in league history.'),
    (4, 1, 40, 0, 'Highest Single Season Team Run Differential', '', '{$team.link} has the highest historical single season team run differential per 9 innings at {$award.topteamvalue} in {$award.awardseason}', '{$team_rdiff}', 'desc', '', 5, '%.2f', 'This Hall of Fame award is for the team with the highest single season run differential per 9 innings in league history.'),
    (5, 1, 50, 0, 'Highest Single Season Team Defensive Rating', '', '{$team.link} has the highest historical single season team defensive rating at {$award.topteamvalue} in {$award.awardseason}', '{$team_drat}', 'desc', '', 5, '%.2f', 'This Hall of Fame award is for the team with the highest single season team defensive rating in league history.');
        
