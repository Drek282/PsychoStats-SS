#### Load libraries.
from datetime import datetime,timezone
import time
from urllib.request import urlopen
import configparser
import os
import re
import sys
import pandas as pd
import numpy as np
import pymysql
import requests
import html



############################################################################
#### #### #### ####    *** Functions start here. ***    #### #### #### #####
############################################################################

# Pandas trunc function.
def trunc(col: pd.Series) -> pd.Series:
    return math.trunc(col)

# Sleep in minutes.
def sleep_m (m):
    time.sleep(int(m) * 60)

# Check to see if game results have been published.
def grp_check (check_loop, league_url):

    # Globals
    global error_no
    global error_log
    global raw_lp_dump

    # Get the check string from the database.
    cursor.execute("SELECT value FROM psss_config WHERE conftype='main' AND var='check_string'")
    data = cursor.fetchone()
    if data['value']:
        check_string = data['value']
    else:
        print(
            '''
            FATAL:  You must configure an check string so that the script can tell if the weekly
                    game results have been released.  This should have been done for you when you
                    installed PsychoStats for Scoresheet Baseball.

                    There is a good chance your PsychoStats installation is seriously broken.
                    Please consult the README.md file and try again.

                    This script will exit.
            '''
            )
        print()

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",fatal,DEFAULT,Check string to see if weekly game results have been published not configured."

        # Generate the error log and exit.
        generate_psss_error_log()
        sys.exit()

    # Setup the regex for the check string.
    my_regex = r"^.+(" + check_string + r").+$"

    # Check to see if the game results have been published.
    if re.search(my_regex, raw_lp_dump, re.MULTILINE):

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",info,DEFAULT,Game results have been published for URL:  " + league_url + "\n"

        return

    # Loop to check the url to see if it has been updated.
    for cl in range(int(check_loop)):
    
        sleep_m(10)

        # Load the league page into a variable.
        with urlopen(league_url) as f:
            raw_lp_dump = f.read().decode()

        # Check to see if the game results have been published.
        if re.search(my_regex, raw_lp_dump):

            # Log entry.
            error_no += 1
            error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",info,DEFAULT,Game results have been published for URL:  " + league_url + "\n"

            return

    # Log entry.
    error_no += 1
    error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",fatal,DEFAULT,Configured limit for checks to determine if game results have been publish exceeded.  The script will exit."
    
    # Generate the error log.
    generate_psss_error_log()
    sys.exit()

# Output to error log.
def generate_psss_error_log ():

    # Globals
    global error_log
    global error_log_fname

    # Output error_log variable to error log file.
    f = open(error_log_fname, 'w')
    f.write(error_log)
    f.close()

    # Read the csv file into a DataFrame object.
    error_log_dfo = pd.read_csv(error_log_fname, header=None)

    ## Output error_log_dfo to psss_errlog data table.
    # Iterate through error_log_dfo.
    for index, row in error_log_dfo.iterrows():
        # Get the last error log id from the database.
        query = "SELECT id FROM psss_errlog ORDER BY id DESC LIMIT 1;"
        cursor.execute(query)
        data = cursor.fetchone()
        # Is the table empty?
        if data:
            # id = id + 1.
            query = "INSERT INTO psss_errlog VALUES ('" + str(data['id'] + 1) + "', '" + str(row[1]) + "', '" + str(row[2]) + "', " + str(row[3]) + ", '" + str(row[4]) + "')"
            cursor.execute(query)
        else:
            # id = 1.
            query = "INSERT INTO psss_errlog VALUES (1, '" + str(row[1]) + "', '" + str(row[2]) + "', " + str(row[3]) + ", '" + str(row[4]) + "')"
            cursor.execute(query)

# Set the GB league championship status.
def get_league_c (season_url, raw_lp_dump):

    # Globals
    global error_no
    global error_log

    # Get the scoresheets url name (doesn't always match league name in current url).
    my_regex = r"^<a href='(.+?_G.htm)?' target=_blank>"
    surl_name = re.search(my_regex, raw_lp_dump, re.MULTILINE).group(1)

    # Split the league url.
    lu_list = season_url.split("/")

    # Build the scoresheets url.
    lu_list[6] = surl_name
    season_url_g = "/".join(lu_list)

    # Check to see if scoresheets page exists.
    request = requests.get(season_url_g)
    if not request.status_code == 200:
        print(
            '''
            WARNING:  The scoresheets page for this season does not exist.

                    There will be no league champion designated for this season.
            '''
            )

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",warning,DEFAULT,The season scoresheets page at URL:  " + season_url_g + "  does not exist.\n"
        
        return 0

    # Load the scoresheets page into a variable.
    with urlopen(season_url_g) as f:
        raw_sp_dump = f.read().decode()
    
    # Remove empty lines.
    raw_sp_dump = re.sub(r'^ *?\n', '', raw_sp_dump, 10000, re.MULTILINE)

    # Get second to last line of raw_sp_dump
    rsp_lines = raw_sp_dump.splitlines()
    c_line = rsp_lines[-6]
    
    # Get the team number from the championship line.
    league_c = re.search(r'([1-2][0-9]|[1-9])', c_line, 1).group()

    return league_c

# Set the GB division title status.
def set_divt_status (working_stats_adv_dfo):
    # Get list of divisions.
    divisions = working_stats_adv_dfo.Division.unique()

    # Set top selection from each division as title.
    for division in divisions:
        for index, row in working_stats_adv_dfo.iterrows():
            if division == str(row['Division']):
                working_stats_adv_dfo.loc[index, 'GB'] = 'dt'
                break

# Generate the psss_team_ids_names table.
def generate_psss_team_ids_names (season, league_name, working_stats_com_dfo):

    # Globals
    global pagedate
    
    # Get page time in the appropriate format.
    tdtd = time.strftime('%Y-%m-%d', time.localtime(pagedate))
    
    # Generate the team ids names table.
    team_ids_names_dfo = working_stats_com_dfo.loc[:, ['Team','Team_Name']]
    team_ids_names_dfo[['firstseen', 'lastseen']] = pd.DataFrame([[tdtd, tdtd]], index=team_ids_names_dfo.index)
    
    ## Column Headers:
    #  1 Team ID, 2 Team Name, 3 Owner Name, 4 First Seen, 5 Last Seen
    
    # Iterate through ids names dataframe.
    for index, row in team_ids_names_dfo.iterrows():
        # Build and execute the SQL.
        query = "SELECT * FROM psss_team_ids_names WHERE team_id='" + str(row['Team']) + "' AND team_name=\"" + row['Team_Name'] + "\""
        cursor.execute(query)
        data = cursor.fetchone()
        # Does team id and team name exist in the db?
        if data:
            # Update firstseen if it is older.
            query = "UPDATE psss_team_ids_names i SET i.firstseen= IF('" + str(row['firstseen']) + "' < i.firstseen, '" + str(row['firstseen']) + "', i.firstseen) WHERE i.team_id='" + str(row['Team']) + "' AND i.team_name=\"" + row['Team_Name'] + "\""
            cursor.execute(query)
            # Update lastseen if it is newer.
            query = "UPDATE psss_team_ids_names i SET i.lastseen= IF('" + str(row['lastseen']) + "' > i.lastseen, '" + str(row['lastseen']) + "', i.lastseen) WHERE i.team_id='" + str(row['Team']) + "' AND i.team_name=\"" + row['Team_Name'] + "\""
            cursor.execute(query)
        else:
            # Get MAX(id)
            query = "SELECT MAX(id) id FROM psss_team_ids_names"
            cursor.execute(query)
            data = cursor.fetchone()
            if data['id']:
                id = int(data['id']) + 1
            else:
                id = 1
            # Add new name to db.
            query = "INSERT IGNORE INTO psss_team_ids_names VALUES ('" + str(id) + "', '" + str(row['Team']) + "', \"" + row['Team_Name'] + "\", '', '" + str(row['firstseen']) + "', '" + str(row['lastseen']) + "')"
            cursor.execute(query)

# Generate additional PSSS tables.
def generate_psss_tables (league_name, working_stats_com_dfo):
    # Sort working_stats_com_dfo by winning percentage.
    working_stats_com_dfo = working_stats_com_dfo.sort_values(by=['pct.', 'Pythag'], ascending=False)

    ## Create or modify the psss_team data table.
    # Create line number, simpler than using enumerate.
    ln = 1
    # Iterate through working_stats_com_dfo.
    for index, row in working_stats_com_dfo.iterrows():
        # Get the previous rank from the database.
        query = "SELECT * FROM psss_team WHERE team_id='" + str(row['Team']) + "'"
        cursor.execute(query)
        data = cursor.fetchone()
        # Is the table empty?
        if data:
            # Add team to database.
            query = "UPDATE psss_team t SET t.prevrank='" + str(data['rank']) + "', t.rank='" + str(ln) + "' WHERE t.team_id='" + str(row['Team']) + "'"
            cursor.execute(query)
        else:
            # Add new name to db.
            query = "INSERT INTO psss_team VALUES ('" + str(row['Team']) + "', '" + str(ln) + "', '" + str(ln) + "', 1)"
            cursor.execute(query)
        # Incremement line number.
        ln += 1
    
    ## Create or modify the team profile table.
    # Iterate through working_stats_com_dfo.
    for index, row in working_stats_com_dfo.iterrows():
        # Check to see if team entry already exists.
        query = "SELECT * FROM psss_team_profile WHERE team_id='" + str(row['Team']) + "'"
        cursor.execute(query)
        data = cursor.fetchone()
        # Add new team to db if it isn't already there.
        if not data:
            query = "INSERT INTO psss_team_profile VALUES ('" + str(row['Team']) + "', DEFAULT, DEFAULT, DEFAULT, DEFAULT, DEFAULT, DEFAULT, DEFAULT, 1)"
            cursor.execute(query)
    
    ## Column Headers:
    #  1 Team ID, 2 userid, 3 Owner Name, 4 email, 5 youtube, 6 website, 7 icon, 8 country code, 9 logo, 10 name locked



#############################################################################
#### #### #### ####    ***  Process team rosters. ***    #### #### #### #####
################################################@############################
def generate_psss_team_rosters (season, season_url, season_dir):

    # debug
    #print(season)

    # Globals
    global season_c
    global pagedate
    global error_no
    global error_log
    
    # Get page time in the appropriate format.
    tdtd = time.strftime('%Y-%m-%d', time.localtime(pagedate))

    # Get the player stats url name (doesn't always match league name in current url).
    surl_name = ''
    my_regex = r"^<a href='(.+?_S.htm)?' target=_blank>"
    surl_name = re.search(my_regex, raw_lp_dump, re.MULTILINE).group(1)

    # Split the league url.
    lu_list = season_url.split("/")

    # Build the scoresheets url.
    i = len(lu_list)-1
    if surl_name != '':
        lu_list[i] = surl_name
        season_url_s = "/".join(lu_list)
    else:
        print(
            '''
            WARNING:  The player stats page for this season does not exist.

                    There will be no team rosters available for this season.
            '''
            )

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",warning,DEFAULT,The player stats page does not exist.\n"
        
        return

    # Check to see if player stats page exists.
    request = requests.get(season_url_s)
    if not request.status_code == 200:
        print(
            '''
            WARNING:  The player stats page for this season does not exist.

                    There will be no team rosters available for this season.
            '''
            )

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",warning,DEFAULT,The player stats page at URL:  " + season_url_s + "  does not exist.\n"
        
        return
    
    # Load the player stats page into a variable.
    with urlopen(season_url_s) as f:
        raw_ps_dump = f.read().decode()

    # Get the list of teams from the player stats page.
    my_regex = r"(<a href='#(:?.|\n)+)(:?\n\n</div>\n\n<div class='wrapper'>\n\n<pre>\n\n<a name=')"
    team_list = re.search(my_regex, raw_ps_dump, re.MULTILINE).group(1)
    team_list = list(filter(None, team_list.split("\n")))

    # Get the number of teams in the league.
    nteams = len(team_list)
        
    # Declare cg_fix dict
    cg_fix = {}

    ## Process rosters.
    for team in range (1, nteams + 1):

        # debug
        #print(team)

        # Get owner name.
        my_regex = r"^<a name='sst" + str(team) + "' id='sst" + str(team) + "'></a><u><span class='heading'>(:? |)" + str(team) + "(:?.|\n)+?\n\n<u><span class='heading'>(.+?) +W  L.+$"
        owner_name = re.search(my_regex, raw_ps_dump, re.MULTILINE).group(3)
        # For teams with multiple owners.
        owner_name = owner_name.replace("&amp;", "&")

        # Add owner names to psss_team_ids_names.
        add_name = True
        query = "SELECT * FROM psss_team_ids_names WHERE team_id='" + str(team) + "' AND owner_name LIKE \"" + owner_name + "%\""
        cursor.execute(query)
        data = cursor.fetchone()
        # Does team id and owner name exist in the db?
        if data:
            # Check that the owner_name match is for the MAX(lastseen)
            query = "SELECT owner_name, MAX(lastseen) lastseen FROM psss_team_ids_names WHERE team_id='" + str(team) + "'"
            cursor.execute(query)
            owner_check = cursor.fetchone()
            if (data['owner_name'] != owner_check['owner_name']):
                # Update firstseen if it is older.
                query = "UPDATE psss_team_ids_names i SET i.firstseen= IF('" + str(tdtd) + "' < i.firstseen, '" + str(tdtd) + "', i.firstseen) WHERE i.team_id='" + str(team) + "' AND i.owner_name LIKE \"" + owner_name + "%\""
                cursor.execute(query)
                # Update lastseen if it is newer.
                query = "UPDATE psss_team_ids_names i SET i.lastseen= IF('" + str(tdtd) + "' > i.lastseen, '" + str(tdtd) + "', i.lastseen) WHERE i.team_id='" + str(team) + "' AND i.owner_name LIKE \"" + owner_name + "%\""
                cursor.execute(query)
                add_name = False
            
            owner_check = {}
            
            # Update firstseen if it is older.
            query = "UPDATE psss_team_ids_names i SET i.firstseen= IF('" + str(tdtd) + "' < i.firstseen, '" + str(tdtd) + "', i.firstseen) WHERE i.team_id='" + str(team) + "' AND i.owner_name LIKE \"" + owner_name + "%\""
            cursor.execute(query)
            # Update lastseen if it is newer.
            query = "UPDATE psss_team_ids_names i SET i.lastseen= IF('" + str(tdtd) + "' > i.lastseen, '" + str(tdtd) + "', i.lastseen) WHERE i.team_id='" + str(team) + "' AND i.owner_name LIKE \"" + owner_name + "%\""
            cursor.execute(query)
        else:
            ## Delete any user account associated with the team_id
            # Get any userid associated with the team_id
            query = "SELECT userid FROM psss_team_profile WHERE team_id='" + str(team) + "'"
            cursor.execute(query)
            data = cursor.fetchone()
            if data:
                # If the user is an admin don't delte the user.
                query = "SELECT accesslevel FROM psss_user WHERE userid='" + str(data['userid']) + "'"
                cursor.execute(query)
                admin_check = cursor.fetchone()
                if (admin_check and admin_check['accesslevel'] != 99):
                    query = "DELETE FROM psss_user WHERE userid='" + str(data['userid']) + "'"
                    cursor.execute(query)
                admin_check = {}
                # Reset psss_team_profile to defaults for the team_id.
                query = "UPDATE psss_team_profile SET userid=DEFAULT, email=DEFAULT, youtube=DEFAULT, website=DEFAULT, icon=DEFAULT, cc=DEFAULT, logo=DEFAULT, namelocked=1 WHERE team_id='" + str(team) + "'"
                cursor.execute(query)

            # Add new name to db.
            if add_name:
                # Get MAX(id)
                query = "SELECT MAX(id) id FROM psss_team_ids_names"
                cursor.execute(query)
                data = cursor.fetchone()
                if data['id']:
                    id = int(data['id']) + 1
                else:
                    id = 1
                query = "INSERT IGNORE INTO psss_team_ids_names VALUES ('" + str(id) + "', '" + str(team) + "', '', \"" + owner_name + "\", '" + str(tdtd) + "', '" + str(tdtd) + "')"
                cursor.execute(query)

        # If team_name and owner_name are empty, delete row.
        query = "DELETE FROM psss_team_ids_names WHERE team_name='' AND owner_name=''"
        cursor.execute(query)

        ## Process position player stats.
        # Remove raw_ps_dump prefix to search string and create the raw_rpo variable.
        my_regex = r"^<a name='sst" + str(team) + "' id='sst" + str(team) + "'></a><u><span class='heading'>.+?</span></u>$"
        header_line = re.search(my_regex, raw_ps_dump, re.MULTILINE).group()
        my_list = re.split(my_regex, raw_ps_dump, 1, re.MULTILINE)
        raw_rpo = my_list[1]

        # Set Player_Name label in header line and remove html.
        my_regex =  r"^<a name='sst" + str(team) + "' id='sst" + str(team) + "'></a><u><span class='heading'>(:? *)(:?[1-9]|[1-2][0-9]) .+? +?(G   AB  R   H   )"
        header_line = re.sub(my_regex, 'Player_Name \g<3>', header_line, 1)
    
        # Remove html at end of header line.
        my_regex =  r"</span></u>$"
        header_line = re.sub(my_regex, '', header_line, 1, re.MULTILINE)
        raw_rpo = header_line + raw_rpo

        # Empty the list.
        my_list = list()
    
        # Remove raw_rpo suffix from search string.
        my_regex =  r"^<u><span class='heading'>.+?</span></u>$"
        my_list = re.split(my_regex, raw_rpo, 1, re.MULTILINE)
        raw_rpo = my_list[0]

        # Empty the list.
        my_list = list()

        # Delete lines that start with "    season".
        raw_rpo = re.sub(r'^  season.+?\n', '', raw_rpo, 200, re.MULTILINE)

        # Delete empty lines.
        raw_rpo = re.sub(r'^ *?\n', '', raw_rpo, 200, re.MULTILINE)

        # Replace commas with # as a placeholder and fix names with brackets.
        raw_rpo = re.sub(r'(\(.+?,|,)', '#', raw_rpo, 200, re.MULTILINE)

        # Delete single quotes.
        raw_rpo = re.sub(r"'", '', raw_rpo, 200, re.MULTILINE)

        # Insert spaces into stats where there is a 1.000+.
        raw_rpo = re.sub(r'(\.[0-9][0-9][0-9])([1-9]\.[0-9][0-9][0-9])', '\g<1> \g<2>', raw_rpo, 2000, re.MULTILINE)
        raw_rpo = re.sub(r'(\.[0-9][0-9][0-9])([1-9]\.[0-9][0-9][0-9])', '\g<1> \g<2>', raw_rpo, 2000, re.MULTILINE)
        raw_rpo = re.sub(r'([0-9])(1\.000)', '\g<1> \g<2>', raw_rpo, 2000, re.MULTILINE)
        raw_rpo = re.sub(r'---(1\.000)', '0 \g<1>', raw_rpo, 2000, re.MULTILINE)
        
        # Replace spaces with commas in raw_rpo.
        raw_rpo = re.sub(r"( +)", ',', raw_rpo, 800)

        # Create the raw_rpo temp stats file name with path.
        rpo_tmp_fname = os.path.join(season_dir, 'rpo_tmp.csv')

        # Output raw_rpo_tmp to CSV file.
        f = open(rpo_tmp_fname, 'w')
        f.write(raw_rpo)
        f.close()

        # Read the csv files into DataFrame objects.
        rpo_dfo = pd.read_csv(rpo_tmp_fname)

        # Remove rpo_tmp file.
        os.remove(rpo_tmp_fname)

        # Fix AAA names.
        rpo_dfo['Player_Name'] = rpo_dfo['Player_Name'].str.replace('#AAA', ' AAA', regex=True)

        # Replace - with 0.
        rpo_dfo = rpo_dfo.replace('-', 0)

        # Replace --- with 0.
        rpo_dfo = rpo_dfo.replace('---', 0)

        # Replace ---- with 0.
        rpo_dfo = rpo_dfo.replace('----', 0)

        # Calculate wOBA.
        rpo_dfo['wOBA'] = round((0.69 * rpo_dfo['BB'] + 0.89 * (rpo_dfo['H'] - rpo_dfo['D'] - rpo_dfo['T'] - rpo_dfo['HR']) + 1.27 * rpo_dfo['D'] + 1.62 * rpo_dfo['T'] + 2.10 * rpo_dfo['HR']) / (rpo_dfo['AB'] + rpo_dfo['BB'] + rpo_dfo['SF']), 3)

        # Calculate V.
        rpo_dfo['V'] = round((rpo_dfo['OPS'].astype(float) * rpo_dfo['AB'] * 0.595 / 1000), 2)

        # If player AAA V = 0, if V < 0, V = 0.
        rpo_dfo.loc[rpo_dfo['Player_Name'].str.contains('AAA'), 'V'] = '0.00'
        rpo_dfo.loc[rpo_dfo['V'].astype(float) < 0, 'V'] = '0.00'

        # Reorder the columns.
        rpo_dfo = rpo_dfo.loc[:, ['Player_Name','G','AB','R','H','D','T','HR','RBI','BB','K','BA','OBA','SlgA','OPS','wOBA','SH','F','SF','GDP','SB','CS','OP','E','PB','V']]

        # Replace NaN with 0.
        rpo_dfo = rpo_dfo.fillna(0)

        ## Create or modify the psss_team_rpo data table.
        # Iterate through rpo_dfo.
        for index, row in rpo_dfo.iterrows():

            # Replace # placeholder in Player_Name with ,.
            row['Player_Name'] = re.sub(r"#", ', ', row['Player_Name'], 4)

            # Get the rpo team data from the database.
            query = "SELECT * FROM psss_team_rpo WHERE season='" + str(season) + "' AND team_id='" + str(team) + "' AND player_name='" + row['Player_Name'] + "'"
            cursor.execute(query)
            data = cursor.fetchone()

            # Is the row empty?
            if data:
                # Update database.
                query = "UPDATE psss_team_rpo SET po_games_played='" + str(row['G']) + "', po_at_bats='" + str(row['AB']) + "', po_runs='" + str(row['R']) + "', po_hits='" + str(row['H']) + "', po_doubles='" + str(row['D']) + "', po_triples='" + str(row['T']) + "', po_home_runs='" + str(row['HR']) + "', po_rbis='" + str(row['RBI']) + "', po_walks='" + str(row['BB']) + "', po_strikeouts='" + str(row['K']) + "', po_batting_average='" + str(row['BA']) + "', po_on_base_average='" + str(row['OBA']) + "', po_slugging_average='" + str(row['SlgA']) + "', po_ops='" + str(row['OPS']) + "', po_woba='" + str(row['wOBA']) + "', po_sacrifice_hits='" + str(row['SH']) + "', po_sacrifice_fails='" + str(row['F']) + "', po_sacrifice_flies='" + str(row['SF']) + "', po_gidps='" + str(row['GDP']) + "', po_stolen_bases='" + str(row['SB']) + "', po_caught_stealing='" + str(row['CS']) + "', po_outstanding_plays='" + str(row['OP']) + "', po_fielding_errors='" + str(row['E']) + "', po_passed_balls='" + str(row['PB']) + "', po_v='" + str(row['V']) + "' WHERE season='" + str(season) + "' AND team_id='" + str(team) + "' AND player_name='" + row['Player_Name'] + "'"
                cursor.execute(query)
            else:
                # Add new entry to db.
                query = "INSERT INTO psss_team_rpo VALUES ('" + str(season) + "', '" + str(team) + "', '" + row['Player_Name'] + "', '" + str(row['G']) + "', '" + str(row['AB']) + "', '" + str(row['R']) + "', '" + str(row['H']) + "', '" + str(row['D']) + "', '" + str(row['T']) + "', '" + str(row['HR']) + "', '" + str(row['RBI']) + "', '" + str(row['BB']) + "', '" + str(row['K']) + "', '" + str(row['BA']) + "', '" + str(row['OBA']) + "', '" + str(row['SlgA']) + "', '" + str(row['OPS']) + "', '" + str(row['wOBA']) + "', '" + str(row['SH']) + "', '" + str(row['F']) + "', '" + str(row['SF']) + "', '" + str(row['GDP']) + "', '" + str(row['SB']) + "', '" + str(row['CS']) + "', '" + str(row['OP']) + "', '" + str(row['E']) + "', '" + str(row['PB']) + "', '" + str(row['V']) + "')"
                cursor.execute(query)
        
        # Clean up.
        rpo_dfo = pd.DataFrame()


        ## Process pitcher stats.
        # Remove raw_ps_dump prefix to search string and create the raw_rpi variable.
        my_regex = r"^<a name='sst" + str(team) + "' id='sst" + str(team) + "'></a><u><span class='heading'>(:?.|\n)+?\n\n(<u><span class='heading'>.+?</span></u>)$"
        header_line = re.search(my_regex, raw_ps_dump, re.MULTILINE).group(2)
        my_list = re.split(my_regex, raw_ps_dump, 1, re.MULTILINE)
        raw_rpi = my_list[3]

        # Set Player_Name label in header line and remove html.
        my_regex =  r"^<u><span class='heading'>.+? +?(W  L  pct.  ERA   )"
        header_line = re.sub(my_regex, 'Player_Name \g<1>', header_line, 1)
    
        # Remove html at end of header line.
        my_regex =  r"</span></u>$"
        header_line = re.sub(my_regex, '', header_line, 1, re.MULTILINE)
        my_list = re.split(my_regex, raw_ps_dump, 1, re.MULTILINE)
        raw_rpi = header_line + raw_rpi

        # Empty the list.
        my_list = list()
    
        # Remove raw_rpi suffix from search string.
        if (team != nteams):
            my_regex =  r"^<a name='.+?</span></u>$"
        else:
            my_regex =  r"^</pre>$"
        my_list = re.split(my_regex, raw_rpi, 1, re.MULTILINE)
        raw_rpi = my_list[0]

        # Empty the list.
        my_list = list()

        # Delete lines that start with "    season".
        raw_rpi = re.sub(r'^  season.+?\n', '', raw_rpi, 200, re.MULTILINE)

        # Delete empty lines.
        raw_rpi = re.sub(r'^ *?\n', '', raw_rpi, 200, re.MULTILINE)

        # Replace commas with # as a placeholder and fix names with brackets.
        raw_rpi = re.sub(r'(\(.+?,|,)', '#', raw_rpi, 200, re.MULTILINE)

        # Delete single quotes.
        raw_rpi = re.sub(r"'", '', raw_rpi, 200, re.MULTILINE)

        # Insert spaces into stats where there is a 1.000+.
        raw_rpi = re.sub(r'([0-9])(1\.000)', '\g<1> \g<2>', raw_rpi, 2000, re.MULTILINE)
        raw_rpi = re.sub(r'-(1\.000)', '0 \g<1>', raw_rpi, 2000, re.MULTILINE)
        
        # Replace spaces with commas in raw_rpi.
        raw_rpi = re.sub(r"( +)", ',', raw_rpi, 800)

        # Create the raw_rpi temp stats file name with path.
        rpi_tmp_fname = os.path.join(season_dir, 'rpi_tmp.csv')

        # Output raw_rpi_tmp to CSV file.
        f = open(rpi_tmp_fname, 'w')
        f.write(raw_rpi)
        f.close()

        # Read the csv files into DataFrame objects.
        rpi_dfo = pd.read_csv(rpi_tmp_fname)

        # Remove rpo_tmp file.
        os.remove(rpi_tmp_fname)

        # Fix AAA names.
        rpi_dfo['Player_Name'] = rpi_dfo['Player_Name'].str.replace('#AAA', ' AAA', regex=True)

        # Replace - with 0.
        rpi_dfo = rpi_dfo.replace('-', 0)

        # Replace --- with 0.
        rpi_dfo = rpi_dfo.replace('---', 0)

        # Replace ---- with 0.
        rpi_dfo = rpi_dfo.replace('----', 0)

        # Convert player IP to decimal.
        rpi_dfo['IP'] = np.trunc(rpi_dfo['IP']) + round((rpi_dfo['IP'] % 1) * 3, 1)

        # Calculate V.
        rpi_dfo['V'] = round(((2 - rpi_dfo['WHIP'].astype(float)) * rpi_dfo['IP'].astype(float) * 1.666 / 1000), 2)

        # Convert decimal player IP to baseball format IP.
        rpi_dfo['IP'] = np.trunc(rpi_dfo['IP']) + round((rpi_dfo['IP'] % 1) / 3, 1)

        # If player AAA V = 0, if V < 0, V = 0.
        rpi_dfo.loc[rpi_dfo['Player_Name'].str.contains('AAA'), 'V'] = '0'
        rpi_dfo.loc[rpi_dfo['V'].astype(float) < 0, 'V'] = '0.00'

        # Replace NaN with 0.
        rpi_dfo = rpi_dfo.fillna(0)


        ## Create or modify the psss_team_rpo data table.
        # Iterate through rpo_dfo.
        for index, row in rpi_dfo.iterrows():

            # Replace # placeholder in Player_Name with ,.
            row['Player_Name'] = re.sub(r"#", ', ', row['Player_Name'], 4)

            # Get CG number for "Pitcher, AAA".
            if (row['Player_Name'] == 'Pitcher AAA'):
                cg_fix[team] = row['CG']

            # Get the rpo team data from the database.
            query = "SELECT * FROM psss_team_rpi WHERE season='" + str(season) + "' AND team_id='" + str(team) + "' AND player_name='" + row['Player_Name'] + "'"
            cursor.execute(query)
            data = cursor.fetchone()

            # Is the row empty?
            if data:
                # Update database.
                query = "UPDATE psss_team_rpi d SET pi_wins='" + str(row['W']) + "', pi_losses='" + str(row['L']) + "', pi_win_percent='" + str(row['pct.']) + "', pi_era='" + str(row['ERA']) + "', pi_games_played='" + str(row['G']) + "', pi_games_started='" + str(row['GS']) + "', pi_complete_games='" + str(row['CG']) + "', pi_shutouts='" + str(row['ShO']) + "', pi_run_support='" + str(row['RS']) + "', pi_saves='" + str(row['Sv']) + "', pi_innings_pitched='" + str(row['IP']) + "', pi_runs_against='" + str(row['R']) + "', pi_earned_runs_against='" + str(row['ER']) + "', pi_hits_surrendered='" + str(row['H']) + "', pi_opp_batting_average='" + str(row['BA']) + "', pi_opp_walks='" + str(row['BB']) + "', pi_whip='" + str(row['WHIP']) + "', pi_strikeouts='" + str(row['K']) + "', pi_wild_pitches='" + str(row['WP']) + "', pi_v='" + str(row['V']) + "' WHERE season='" + str(season) + "' AND team_id='" + str(team) + "' AND player_name='" + row['Player_Name'] + "'"
                cursor.execute(query)
            else:
                # Add new entry to db.
                query = "INSERT INTO psss_team_rpi VALUES ('" + str(season) + "', '" + str(team) + "', '" + row['Player_Name'] + "', '" + str(row['W']) + "', '" + str(row['L']) + "', '" + str(row['pct.']) + "', '" + str(row['ERA']) + "', '" + str(row['G']) + "', '" + str(row['GS']) + "', '" + str(row['CG']) + "', '" + str(row['ShO']) + "', '" + str(row['RS']) + "', '" + str(row['Sv']) + "', '" + str(row['IP']) + "', '" + str(row['R']) + "', '" + str(row['ER']) + "', '" + str(row['H']) + "', '" + str(row['BA']) + "', '" + str(row['BB']) + "', '" + str(row['WHIP']) + "', '" + str(row['K']) + "', '" + str(row['WP']) + "', '" + str(row['V']) + "')"
                cursor.execute(query)
        
        # Clean up.
        rpo_dfi = pd.DataFrame()

    return cg_fix



############################################################################
#### #### #### ####    ***  Process league data. ***    #### #### #### #####
############################################################################
def process_data (season_url, season, league_name, raw_lp_dump):

    # Globals
    global season_c
    global season_c_skipped
    global error_no
    global error_log

    # Create the season path
    season_dir = os.path.join(output_dir, season)

    # Create the output directory if it doesn't exist.
    if not os.path.isdir(season_dir):
        os.makedirs(season_dir)

        # Output.
        print("WARNING:  \"" + season_dir + "\"  does not exist.")
        print("CREATING:  \"" + season_dir + "\"")
        print()

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",warning,DEFAULT,\"" + season_dir + "\"  does not exist.\n"
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",info,DEFAULT,CREATING:  \"" + season_dir + "\"\n"
    

    ### Convert league page into CSV file.
    
    # Check to see if the season has started.
    global season_ns
    my_regex =  r"^<a name='standings' id='standings'>.+?</span></u>$"
    if not re.search(my_regex, raw_lp_dump, re.MULTILINE):
        season_ns = True

        print("WARNING:  There is no data for the current season.  The season has probably not started or the first week results have not yet been published.")
        print()

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",warning,DEFAULT,There is no data for the current season.  The season has probably not started or the first week results have not yet been published.\n"

        return
    else:
        season_ns = False
    
    # Generate the first header line.
    header_line = re.search(my_regex, raw_lp_dump, re.MULTILINE).group()
    header_line = re.sub(r"<a name='standings' id='standings'></a>", '', header_line, 1)
    
    # Remove raw_lp_dump prefix to search string.
    my_list = re.split(my_regex, raw_lp_dump, 1, re.MULTILINE)
    raw_lp_dump_mod = header_line + my_list[1]

    # Empty the list.
    my_list = list()
    
    # Remove raw_lp_dump suffix from search string.
    my_regex =  r"^<a name='scores' id='scores'>.+?$"
    my_list = re.split(my_regex, raw_lp_dump_mod, 1, re.MULTILINE)
    raw_lp_dump_mod = my_list[0]

    # Empty the list.
    my_list = list()
    
    # Remove html and whitespace at start of lines.
    my_regex =  r"<span class='.+?'> *"
    raw_lp_dump_mod = re.sub(my_regex, '', raw_lp_dump_mod, 200, re.MULTILINE)
    
    # Remove html at end of lines.
    my_regex =  r"</span>"
    raw_lp_dump_mod = re.sub(my_regex, '', raw_lp_dump_mod, 200, re.MULTILINE)

    # Convert league_name to lower case.
    league_name = league_name.lower()

    # Get the raw stats file name from the database.
    cursor.execute("SELECT value FROM psss_config WHERE conftype='main' AND var='stats_raw_dname'")
    data = cursor.fetchone()
    if data['value']:
        raw_stats_fname = data['value'] + league_name + '.csv'
    else:
        print(
            '''
            FATAL:  You must configure a raw stats file name.  This should have been done for you
                    when you installed PsychoStats for Scoresheet Baseball.

                    There is a good chance your PsychoStats installation is seriously broken.
                    Please consult the README.md file and try again.

                    This script will exit.
            '''
            )
        print()

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",fatal,DEFAULT,Raw stats file name not configured."

        # Generate the error log and exit.
        generate_psss_error_log()
        sys.exit()

    # Create the raw stats file name with path.
    raw_stats_fname = os.path.join(season_dir, raw_stats_fname)

    # Save the raw stats to file.
    f = open(raw_stats_fname, 'w')
    f.write(raw_lp_dump_mod)
    f.close()
    
    # Create defensive stats variable.
    my_regex =  r"^<u>AB    R    H  .+?</u>$"
    my_list = re.split(my_regex, raw_lp_dump_mod, 1, re.MULTILINE)
    working_stats_def_pre = my_list[0]

    # Empty the list.
    my_list = list()
    
    # Generate the first header line.
    header_line = re.search(my_regex, raw_lp_dump_mod, re.MULTILINE).group()
    
    # Create offensive stats variable.
    my_list = re.split(my_regex, raw_lp_dump_mod, 1, re.MULTILINE)
    working_stats_off = header_line + my_list[1]

    # Delete empty lines.
    working_stats_off = re.sub(r'^ *?\n', '', working_stats_off, 200, re.MULTILINE)

    # Empty the list.
    my_list = list()

    # Split off the wildcard standings if they exist.
    my_regex = r"^<u>Wild Card Race  .+?</u>$"
    if re.search(my_regex, working_stats_def_pre, re.MULTILINE):
        header_line = re.search(my_regex, working_stats_def_pre, re.MULTILINE).group()
        my_list = re.split(my_regex, working_stats_def_pre, 1, re.MULTILINE)
        working_stats_def = my_list[0]
        working_stats_wc = header_line + my_list[1]
    else:
        working_stats_def = working_stats_def_pre
        working_stats_wc = str()
    
    # Clean up.
    my_list = list()
    header_line = str()
    working_stats_def_pre = str()
    
    # Split working_stats_def into division standings.
    #my_regex = r"^<u>.+? Standings, Pitching  +.+?</u>$"
    my_regex = r" *?\n *?\n"
    my_list = re.split(my_regex, working_stats_def, 9, re.MULTILINE)


    # Remove empty keys.
    #my_list = [ek for ek in my_list if ek]
    my_regex = re.compile(r'^ *?$')
    my_list = [i for i in my_list if not my_regex.match(i)]

    # Empty working_stats_def.
    working_stats_def = str()

    # Iterate through division keys and create a division field.
    div = str()
    my_regex = r"^<u>[A-Za-z]+ Standings, Pitching  +.+?</u>\n"
    for i in range(len(my_list)):

        if re.match(my_regex, my_list[i], re.MULTILINE):
            div = my_list[i].split()[0]
            div = div[1:]
            div = re.sub(r'u>', '', div, 1, re.MULTILINE)
        
        # Generate the header line.
        hl_regex = r"^<u>(:?[A-Za-z]+ |)Standings, Pitching  +.+?</u>\n"
        header_line = re.search(hl_regex, my_list[i], re.MULTILINE).group()

        # Delete the header line.
        my_list[i] = re.sub(header_line, '', my_list[i], 1, re.MULTILINE)

        # Append division field to every line.
        if div:
            my_list[i] = my_list[i].replace("\n", " " + div + "\n")
        else:
            my_list[i] = my_list[i].replace("\n", " na\n")

        # Add the last division name to the last field.
        if div:
            my_list[i] = my_list[i] + " " + div
        else:
            my_list[i] = my_list[i] + " na"
    
    # Recreate the working_stats_def string.
    working_stats_def = "\n".join(my_list)
    working_stats_def = header_line + working_stats_def

    # Clean up.
    header_line = str()
    hl_regex = str()
    my_list = list()

    # Set the header line for working_stats_def.
    my_regex =  r"^<u>(:?[A-Za-z]+ |)Standings, Pitching "
    working_stats_def = re.sub(my_regex, 'Team Team_Name ', working_stats_def, 1)
    my_regex =  r"</u>$"
    working_stats_def = re.sub(my_regex, ' Division', working_stats_def, 1, re.MULTILINE)

    # Set the header line for working_stats_off.
    my_regex =  r"^<u>"
    working_stats_off = re.sub(my_regex, 'Team ', working_stats_off, 1)
    my_regex =  r"</u>$"
    working_stats_off = re.sub(my_regex, '', working_stats_off, 1, re.MULTILINE)
    working_stats_off = os.linesep.join([s for s in working_stats_off.splitlines() if s])

    # Add a newline to the last field.
    #working_stats_off = working_stats_off + "\n"

    # Set the header line for working_stats_wc and remove empty lines.
    if working_stats_wc:
        my_regex =  r"^<u>Wild Card Race "
        working_stats_wc = re.sub(my_regex, 'Team ', working_stats_wc, 1)
        my_regex =  r"</u>$"
        working_stats_wc = re.sub(my_regex, '', working_stats_wc, 1, re.MULTILINE)
        working_stats_wc = os.linesep.join([s for s in working_stats_wc.splitlines() if s])
    
    ## Remove team names from working_stats_def and working_stats_wc and create team_names_def variable.
    # working_stats_def
    my_regex =  r"^((?:[1-9]|[1-9][0-9])  )(.+)( +(?:1[0-9][0-9]| [1-9][0-9]|  [0-9]) (?:1[0-9][0-9]| [1-9][0-9]|  [0-9]) (?:1\.000| \.[0-9][0-9][0-9]) .+)$"
    working_stats_def_nr = re.sub(my_regex, r'\g<1>###\g<3>', working_stats_def, 40, re.MULTILINE)
    team_names_def = re.sub(my_regex, r'\g<1>\g<2>', working_stats_def, 40, re.MULTILINE)
    # Remove whitespace at end of lines in team_names_def.
    my_regex =  r"( +$)"
    team_names_def = re.sub(my_regex, '', team_names_def, 40, re.MULTILINE)
    # Fix the header line in team_names_def.
    my_regex =  r"(^.+$)"
    team_names_def = re.sub(my_regex, 'Team Team_Name', team_names_def, 1, re.MULTILINE)
    # Replace html special characters in team_names_def.
    team_names_def = html.unescape(team_names_def)
    # working_stats_wc
    if working_stats_wc:
        my_regex =  r"^(([1-9]|[1-9][0-9])  )(.+)(  +( |1)( |[1-9])[0-9] ( |1)( |[1-9])[0-9]  \.[0-9][0-9][0-9] .+)$"
        working_stats_wc = re.sub(my_regex, r'\g<2> \g<4>', working_stats_wc, 40, re.MULTILINE)
    
    # Replace spaces with commas in working_stats_def, working_stats_wc and working_stats_off.
    my_regex =  r"( +)"
    working_stats_def = re.sub(my_regex, ',', working_stats_def_nr, 800)
    if working_stats_wc:
         working_stats_wc = re.sub(my_regex, ',', working_stats_wc, 800)
    working_stats_off = re.sub(my_regex, ',', working_stats_off, 1600)
    
    ## Double quote the names in team_names_def.
    team_names_def = re.sub(r'(^[1-9]|[1-2][0-9]) +(.+)$', r'\g<1> "\g<2>"', team_names_def, 40, re.MULTILINE )

    ## Replace first set of spaces with commas in team_names_def.
    ## Replace the space in the header line with a comma.
    # team_names_def
    my_regex =  r"^([1-9]|[1-9][0-9])( )(.+)$"
    team_names_def = re.sub(r' ', ',', team_names_def, 1)
    team_names_def = re.sub(my_regex, r'\g<1>,\g<3>', team_names_def, 40, re.MULTILINE)

    
    ## Create the CSV files

    # Get the def stats file name from the database.
    cursor.execute("SELECT value FROM psss_config WHERE conftype='main' AND var='stats_def_fname'")
    data = cursor.fetchone()
    if data['value']:
        def_stats_fname = data['value'] + league_name + '.csv'
    else:
        print(
            '''
            FATAL:  You must configure a def stats file name.  This should have been done for you
                    when you installed PsychoStats for Scoresheet Baseball.

                    There is a good chance your PsychoStats installation is seriously broken.
                    Please consult the README.md file and try again.

                    This script will exit.
            '''
            )
        print()

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",fatal,DEFAULT,def stats file name not configured."

        # Generate the error log and exit.
        generate_psss_error_log()
        sys.exit()

    # Create the def stats file name with path.
    def_stats_fname = os.path.join(season_dir, def_stats_fname)

    # Get the def names file name from the database.
    cursor.execute("SELECT value FROM psss_config WHERE conftype='main' AND var='names_def_fname'")
    data = cursor.fetchone()
    if data['value']:
        def_names_fname = data['value'] + league_name + '.csv'
    else:
        print(
            '''
            FATAL:  You must configure a def names file name.  This should have been done for you
                    when you installed PsychoStats for Scoresheet Baseball.

                    There is a good chance your PsychoStats installation is seriously broken.
                    Please consult the README.md file and try again.

                    This script will exit.
            '''
            )
        print()

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",fatal,DEFAULT,def names file name not configured."

        # Generate the error log and exit.
        generate_psss_error_log()
        sys.exit()

    # Create the def names file name with path.
    def_names_fname = os.path.join(season_dir, def_names_fname)

    # Get the off stats file name from the database.
    cursor.execute("SELECT value FROM psss_config WHERE conftype='main' AND var='stats_off_fname'")
    data = cursor.fetchone()
    if data['value']:
        off_stats_fname = data['value'] + league_name + '.csv'
    else:
        print(
            '''
            FATAL:  You must configure an off stats file name.  This should have been done for you
                    when you installed PsychoStats for Scoresheet Baseball.

                    There is a good chance your PsychoStats installation is seriously broken.
                    Please consult the README.md file and try again.

                    This script will exit.
            '''
            )
        print()

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",fatal,DEFAULT,off stats file name not configured."

        # Generate the error log and exit.
        generate_psss_error_log()
        sys.exit()

    # Create the off stats file name with path.
    off_stats_fname = os.path.join(season_dir, off_stats_fname)

    # Wildcard if it exists.
    if working_stats_wc:

        # Get the raw stats file name from the database.
        cursor.execute("SELECT value FROM psss_config WHERE conftype='main' AND var='stats_wc_fname'")
        data = cursor.fetchone()
        if data['value']:
            wc_stats_fname = data['value'] + league_name + '.csv'
        else:
            print(
                '''
                FATAL:  You must configure a wildcard stats file name.  This should have been done for you
                        when you installed PsychoStats for Scoresheet Baseball.

                        There is a good chance your PsychoStats installation is seriously broken.
                        Please consult the README.md file and try again.

                        This script will exit.
                '''
                )
            print()

            # Log entry.
            error_no += 1
            error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",fatal,DEFAULT,wildcard stats file name not configured."

            # Generate the error log and exit.
            generate_psss_error_log()
            sys.exit()

        # Create the wc stats file name with path.
        wc_stats_fname = os.path.join(season_dir, wc_stats_fname)


    ## Output to CSV files.
    # Def stats CSV.
    f = open(def_stats_fname, 'w')
    f.write(working_stats_def)
    f.close()
    # Def names CSV.
    f = open(def_names_fname, 'w')
    f.write(team_names_def)
    f.close()
    # Off stats CSV.
    f = open(off_stats_fname, 'w')
    f.write(working_stats_off)
    f.close()
    # WC stats CSV.
    if working_stats_wc:
        f = open(wc_stats_fname, 'w')
        f.write(working_stats_wc)
        f.close()


    ### Process the CSV files.

    ## Initial processing, working_stats_def_dfo.
    # Read the csv files into DataFrame objects.
    working_stats_def_dfo = pd.read_csv(def_stats_fname)
    team_names_def_dfo = pd.read_csv(def_names_fname)
    # Cleanup names file.
    os.remove(def_names_fname)
    # Combine team_names_def and working_stats_def.
    working_stats_def_dfo = pd.merge(team_names_def_dfo, working_stats_def_dfo, on='Team', how='left')
    # Delete the Team_Names_y column.
    working_stats_def_dfo = working_stats_def_dfo.drop('Team_Name_y', axis=1)
    # Rename the Team_Names_x column and other columns to make them distinct.
    working_stats_def_dfo = working_stats_def_dfo.rename(columns={'Team_Name_x':'Team_Name','R':'RA','H':'HA','BA':'BAA','BB':'BBA','K':'KA'})
    # Create a Games Played column.
    working_stats_def_dfo['GP'] = working_stats_def_dfo['W'] + working_stats_def_dfo['L']
    # Convert IP to decimal.
    working_stats_def_dfo['IP'] = np.trunc(working_stats_def_dfo['IP']) + round((working_stats_def_dfo['IP'] % 1) * 3, 1)
    # Reorder the columns.
    working_stats_def_dfo = working_stats_def_dfo.loc[:, ["Team","Team_Name","Division","GP","W","L","pct.","GB","ERA","CG","ShO","RS","Sv","IP","RA","ER","HA","BAA","BBA","KA","WP"]]
    
    ## Column Headers:
    #  1 Team Number, 2 Team Name, 3 Team Division, 4 GP, 5 W, 6 L, 7 W%, 8 GB, 9 ERA, 10 CG, 11 ShO, 12 RS, 13 Sv, 14 IP, 15 RA, 16 ER, 17 HA, 18 BAA, 19 WA, 20 KA, 21 WP

    ## Initial processing, working_stats_off_dfo.
    # Read the csv file into a DataFrame objects.
    working_stats_off_dfo = pd.read_csv(off_stats_fname)
    
    ## Column Headers:
    #  1 Team Number, 2 AB, 3 R, 4 H, 5 D, 6 T, 7 HR, 8 RBI, 9 BB, 10 K, 11 BA, 12 OBA, 13 SlgA, 14 SH, 15 F, 16 SF, 17 GDP, 18 SB, 19 CS, 20 LOB, 21 OP, 22 DP, 23 E, 24 OSB, 25 OCS, 26 PB
    
    ## Initial processing, working_stats_wc_dfo if it exists.
    if working_stats_wc:
        # Read the csv file into a DataFrame objects.
        working_stats_wc_dfo = pd.read_csv(wc_stats_fname)
        # Delete redundant columns.
        working_stats_wc_dfo = working_stats_wc_dfo.drop(['W','L','pct.'], axis=1)
    
    ## Column Headers:
    #  1 dataid, 2 Team Number, 3 GB

    # Combine the def and off stats.
    working_stats_com_dfo = pd.merge(working_stats_def_dfo, working_stats_off_dfo, on='Team', how='right')

    # Add a season column and reorder the columns.
    working_stats_com_dfo['Season'] = season
    working_stats_com_dfo = working_stats_com_dfo.loc[:, ['Season','Team','Team_Name','Division','GP','W','L','pct.','GB','ERA','CG','ShO','RS','Sv','IP','RA','ER','HA','BAA','BBA','KA','WP','AB','R','H','D','T','HR','RBI','BB','K','BA','OBA','SlgA','SH','F','SF','GDP','SB','CS','LOB','OP','DP','E','OSB','OCS','PB']]
    
    ## Column Headers:
    #  1 Season, 2 Team Number, 3 Team Name, 4 Team Division, 5 GP, 6 W, 7 L, 8 W%, 9 GB, 10 ERA, 11 CG, 12 ShO, 13 RS, 14 Sv, 15 Innings Pitched, 16 RA, 17 ER, 18 HA, 19 BAA, 20 BBA, 21 KA, 22 WP, 23 AB, 24 Runs For, 25 Hits, 26 Doubles, 27 Triples, 28 Home Runs, 29 RBI, 30 Walks, 31 K, 32 BA, 33 OBA, 34 SLG, 35 SH, 36 F, 37 SF, 38 GDP, 39 SB, 40 CS, 41 LOB, 42 OP, 43 DP, 44 E, 45 OSB, 46 OCS, 47 PB

    # Calculate RDiff.
    working_stats_com_dfo['RDiff'] = round((working_stats_com_dfo['R'] - working_stats_com_dfo['RA']) / working_stats_com_dfo['IP'] * 9, 2)

    # Calculate Pythag.
    working_stats_com_dfo['Pythag'] = round(1 / (1 + pow(working_stats_com_dfo['RA'] / working_stats_com_dfo['R'], 1.83)), 3)

    # Calculate Pythag+.
    working_stats_com_dfo['Pythag+'] = working_stats_com_dfo['pct.'] - working_stats_com_dfo['Pythag']

    # Create the ADV stats table.
    working_stats_adv_dfo = working_stats_com_dfo.drop(['Team_Name','ERA','CG','ShO','RS','Sv','IP','RA','ER','HA','BAA','BBA','KA','WP','AB','R','H','D','T','HR','RBI','BB','K','BA','OBA','SlgA','SH','F','SF','GDP','SB','CS','LOB','OP','DP','E','OSB','OCS','PB'], axis=1)

    # Set division title status for historical seasons.
    if ((season != season_c) or season_c_skipped):
        set_divt_status(working_stats_adv_dfo)

    # Set league championship status for historical seasons.
    league_c = 0
    if ((season != season_c) or season_c_skipped):
        league_c = get_league_c(season_url, raw_lp_dump)

    ## Column Headers:
    #  1 Season, 2 Team Number, 3 Team Division, 4 GP, 5 W, 6 L, 7 W%, 8 GB, 9 RDiff, 10 Pythag, 11 Pythag+

    # Create the DEF stats table.
    working_stats_def_dfo = working_stats_com_dfo.drop(['Team_Name','Division','GP','W','L','pct.','GB','RS','AB','R','H','D','T','HR','RBI','BB','K','BA','OBA','SlgA','SH','F','SF','GDP','SB','CS','LOB'], axis=1)
    # Calculate RA9.
    working_stats_def_dfo['RA9'] = round(working_stats_def_dfo['RA'] / working_stats_def_dfo['IP'] * 9, 2)
    # Calculate WHIP.
    working_stats_def_dfo['WHIP'] = round((working_stats_def_dfo['HA'] + working_stats_def_dfo['BBA']) / working_stats_com_dfo['IP'], 2)
    # Calculate DRat.
    working_stats_def_dfo['DRat'] = round(((working_stats_def_dfo['OP'] - working_stats_def_dfo['E'] - working_stats_def_dfo['PB']) / working_stats_def_dfo['IP'] + working_stats_def_dfo['DP'] / (working_stats_def_dfo['HA'] + working_stats_def_dfo['BBA']) * 0.5 + working_stats_def_dfo['OCS'] / (working_stats_def_dfo['OSB'] + working_stats_def_dfo['OCS']) * 0.05) * 5, 2)
    # Convert decimal IP back to baseball format IP.
    working_stats_def_dfo['IP'] = np.trunc(working_stats_def_dfo['IP']) + round((working_stats_def_dfo['IP'] % 1) / 3, 1)
    # Reorder the columns.
    working_stats_def_dfo = working_stats_def_dfo.loc[:, ["Season","Team","ERA","RA9","CG","ShO","Sv","IP","RA","ER","HA","BAA","BBA","WHIP","KA","OP","DP","E","WP","PB","OSB","OCS","DRat"]]

    # Create the OFF stats table.
    working_stats_off_dfo = working_stats_com_dfo.drop(['Team_Name','Division','GP','W','L','pct.','GB','ERA','CG','ShO','Sv','IP','RA','ER','HA','BAA','BBA','KA','WP','OP','DP','E','OSB','OCS','PB','RDiff','Pythag'], axis=1)
    # Calculate OPS.
    working_stats_off_dfo['OPS'] = round(working_stats_off_dfo['OBA'] + working_stats_off_dfo['SlgA'], 3)
    # Calculate LOB%.
    working_stats_off_dfo['LOB%'] = round(((working_stats_off_dfo['RBI'] - working_stats_off_dfo['HR']) / (working_stats_off_dfo['H'] + working_stats_off_dfo['BB'] - working_stats_off_dfo['HR']) * 100 - 100) * -0.01, 2)
    # Calculate SRAT.
    working_stats_off_dfo['SRAT'] = round((((working_stats_off_dfo['SB'] - working_stats_off_dfo['CS']) * 3 + working_stats_off_dfo['T'] * 6 + working_stats_off_dfo['RS']  - working_stats_off_dfo['GDP'] / 1.5 - working_stats_off_dfo['HR'] * 1.5) / (working_stats_off_dfo['H'] + working_stats_off_dfo['BB'] - working_stats_off_dfo['HR']) * 10 + 10) * 0.05, 2)
    #Calculate wOBA.
    working_stats_off_dfo['wOBA'] = round((0.69 * working_stats_off_dfo['BB'] + 0.89 * (working_stats_off_dfo['H'] - working_stats_off_dfo['D'] - working_stats_off_dfo['T'] - working_stats_off_dfo['HR']) + 1.27 * working_stats_off_dfo['D'] + 1.62 * working_stats_off_dfo['T'] + 2.10 * working_stats_off_dfo['HR']) / (working_stats_off_dfo['AB'] + working_stats_off_dfo['BB'] + working_stats_off_dfo['SF']), 3)
    # Reorder the columns.
    working_stats_off_dfo = working_stats_off_dfo.loc[:, ['Season','Team','RS','AB','R','H','D','T','HR','RBI','BB','K','BA','OBA','SlgA','OPS','wOBA','SH','F','SF','GDP','SB','CS','LOB','LOB%','SRAT']]
    
    ## Column Headers:
    #  1 Season, 2 Team Number, 3 RS, 4 AB, 5 Runs For, 6 Hits, 7 Doubles, 8 Triples, 9 Home Runs, 10 RBI, 11 Walks, 12 K, 13 BA, 14 OBA, 15 SLG, 16 OPS, 17 wOBA, 18 SH, 19 F, 20 SF, 21 GDP, 22 SB, 23 CS, 24 LOB, 25 LOB%, 26 SRAT

    # Log entry.
    error_no += 1
    error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",info,DEFAULT,Outputting data to CSV files and exporting CSV files to database.\n"

    
    
    ## Create the CSV files

    # Get the adv stats file name from the database.
    cursor.execute("SELECT value FROM psss_config WHERE conftype='main' AND var='stats_adv_fname'")
    data = cursor.fetchone()
    if data['value']:
        adv_stats_fname = data['value'] + league_name + '.csv'
    else:
        print(
            '''
            FATAL:  You must configure an adv stats file name.  This should have been done for you
                    when you installed PsychoStats for Scoresheet Baseball.

                    There is a good chance your PsychoStats installation is seriously broken.
                    Please consult the README.md file and try again.

                    This script will exit.
            '''
            )
        print()

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",fatal,DEFAULT,adv stats file name not configured."

        # Generate the error log and exit.
        generate_psss_error_log()
        sys.exit()

    # Create the adv stats file name with path.
    adv_stats_fname = os.path.join(season_dir, adv_stats_fname)
    
    # Output to files.
    working_stats_adv_dfo.to_csv(adv_stats_fname)
    working_stats_def_dfo.to_csv(def_stats_fname)
    working_stats_off_dfo.to_csv(off_stats_fname)
    if working_stats_wc:
        working_stats_wc_dfo.to_csv(wc_stats_fname)
    
    # Generate additional psss tables.
    cursor.execute("SELECT * FROM psss_team")
    data = cursor.fetchone()
    if (season == season_c or (season == seasons_h[0] and not data)):
        generate_psss_tables(league_name, working_stats_com_dfo)
    
    # Generate the team ids names table.
    generate_psss_team_ids_names(season, league_name, working_stats_com_dfo)
    
    # Generate team roster tables.
    cg_fix = {}
    cg_fix = generate_psss_team_rosters(season, season_url, season_dir)


    #### Output to database.

    ## Create or modify the psss_team_adv data table.
    # Iterate through working_stats_adv_dfo.
    for index, row in working_stats_adv_dfo.iterrows():
        # Get the adv team data from the database.
        query = "SELECT * FROM psss_team_adv WHERE season='" + str(row['Season']) + "' AND team_id='" + str(row['Team']) + "'"
        cursor.execute(query)
        data = cursor.fetchone()
        # Is the table empty?
        if data:
            # Update database.
            query = "UPDATE psss_team_adv a SET a.games_played='" + str(row['GP']) + "', a.wins='" + str(row['W']) + "', a.losses='" + str(row['L']) + "', a.win_percent='" + str(row['pct.']) + "', a.games_back='" + str(row['GB']) + "', a.team_rdiff='" + str(row['RDiff']) + "', a.pythag='" + str(row['Pythag']) + "', a.pythag_plus='" + str(row['Pythag+']) + "' WHERE a.season='" + str(row['Season']) + "' AND a.team_id='" + str(row['Team']) + "'"
            cursor.execute(query)
        else:
            # Add new entry to db.
            query = "INSERT INTO psss_team_adv VALUES ('" + str(row['Season']) + "', '" + str(row['Team']) + "', '" + str(row['Division']) + "', '" + str(row['GP']) + "', '" + str(row['W']) + "', '" + str(row['L']) + "', '" + str(row['pct.']) + "', '" + str(row['GB']) + "', '" + str(row['RDiff']) + "', '" + str(row['Pythag']) + "', '" + str(row['Pythag+']) + "')"
            cursor.execute(query)
    
    # Output league championship status to psss_team_adv data table
    if (league_c != 0):
        query = "UPDATE psss_team_adv a SET a.games_back= CASE WHEN games_back='dt' THEN 'dtlc' ELSE 'lc' END WHERE a.season='" + season + "' AND a.team_id='" + league_c + "'"
        cursor.execute(query)

    # Replace NaN with 0.
    working_stats_def_dfo = working_stats_def_dfo.fillna(0)

    ## Create or modify the psss_team_def data table.
    # Iterate through working_stats_def_dfo.
    for index, row in working_stats_def_dfo.iterrows():

        # Fix the CG stat
        if cg_fix is not None:
            if row['Team'] not in cg_fix:
                cg_fix[row['Team']] = 0
            row['CG'] = row['CG'] - cg_fix[row['Team']]
        else:
            row['CG'] = 0

        # Get the adv team data from the database.
        query = "SELECT * FROM psss_team_def WHERE season='" + str(row['Season']) + "' AND team_id='" + str(row['Team']) + "'"
        cursor.execute(query)
        data = cursor.fetchone()
        # Is the row empty?
        if data:
            # Update database.
            query = "UPDATE psss_team_def d SET d.team_era='" + str(row['ERA']) + "', d.team_ra='" + str(row['RA9']) + "', d.complete_games='" + str(row['CG']) + "', d.shutouts='" + str(row['ShO']) + "', d.team_saves='" + str(row['Sv']) + "', d.innings_pitched='" + str(row['IP']) + "', d.total_runs_against='" + str(row['RA']) + "', d.total_earned_runs_against='" + str(row['ER']) + "', d.hits_surrendered='" + str(row['HA']) + "', d.opp_batting_average='" + str(row['BAA']) + "', d.opp_walks='" + str(row['BBA']) + "', d.team_whip='" + str(row['WHIP']) + "', d.opp_strikeouts='" + str(row['KA']) + "', d.outstanding_plays='" + str(row['OP']) + "', d.double_plays_turned='" + str(row['DP']) + "', d.fielding_errors='" + str(row['E']) + "', d.team_wild_pitches='" + str(row['WP']) + "', d.passed_balls='" + str(row['PB']) + "', d.opp_stolen_bases='" + str(row['OSB']) + "', d.opp_caught_stealing='" + str(row['OCS']) + "', d.team_drat='" + str(row['DRat']) + "' WHERE d.season='" + str(row['Season']) + "' AND d.team_id='" + str(row['Team']) + "'"
            cursor.execute(query)
        else:
            # Add new entry to db.
            query = "INSERT INTO psss_team_def VALUES ('" + str(row['Season']) + "', '" + str(row['Team']) + "', '" + str(row['ERA']) + "', '" + str(row['RA9']) + "', '" + str(row['CG']) + "', '" + str(row['ShO']) + "', '" + str(row['Sv']) + "', '" + str(row['IP']) + "', '" + str(row['RA']) + "', '" + str(row['ER']) + "', '" + str(row['HA']) + "', '" + str(row['BAA']) + "', '" + str(row['BBA']) + "', '" + str(row['WHIP']) + "', '" + str(row['KA']) + "', '" + str(row['OP']) + "', '" + str(row['DP']) + "', '" + str(row['E']) + "', '" + str(row['WP']) + "', '" + str(row['PB']) + "', '" + str(row['OSB']) + "', '" + str(row['OCS']) + "', '" + str(row['DRat']) + "')"
            cursor.execute(query)

    # Replace NaN with 0.
    working_stats_off_dfo = working_stats_off_dfo.fillna(0)

    ## Create or modify the psss_team_off data table.
    # Iterate through working_stats_off_dfo.
    for index, row in working_stats_off_dfo.iterrows():
        # Get the off team data from the database.
        query = "SELECT * FROM psss_team_off WHERE season='" + str(row['Season']) + "' AND team_id='" + str(row['Team']) + "'"
        cursor.execute(query)
        data = cursor.fetchone()
        # Is the row empty?
        if data:
            # Update database.
            query = "UPDATE psss_team_off o SET o.run_support='" + str(row['RS']) + "', o.at_bats='" + str(row['AB']) + "', o.runs='" + str(row['R']) + "', o.hits='" + str(row['H']) + "', o.doubles='" + str(row['D']) + "', o.triples='" + str(row['T']) + "', o.home_runs='" + str(row['HR']) + "', o.team_rbis='" + str(row['RBI']) + "', o.walks='" + str(row['BB']) + "', o.strikeouts='" + str(row['K']) + "', o.batting_average='" + str(row['BA']) + "', o.on_base_average='" + str(row['OBA']) + "', o.slugging_average='" + str(row['SlgA']) + "', o.ops='" + str(row['OPS']) + "', o.woba='" + str(row['wOBA']) + "', o.sacrifice_hits='" + str(row['SH']) + "', o.sacrifice_fails='" + str(row['F']) + "', o.sacrifice_flies='" + str(row['SF']) + "', o.gidps='" + str(row['GDP']) + "', o.stolen_bases='" + str(row['SB']) + "', o.caught_stealing='" + str(row['CS']) + "', o.left_on_base='" + str(row['LOB']) + "', o.left_on_base_percent='" + str(row['LOB%']) + "', o.team_srat='" + str(row['SRAT']) + "' WHERE o.season='" + str(row['Season']) + "' AND o.team_id='" + str(row['Team']) + "'"
            cursor.execute(query)
        else:
            # Add new entry to db.
            query = "INSERT INTO psss_team_off VALUES ('" + str(row['Season']) + "', '" + str(row['Team']) + "', '" + str(row['RS']) + "', '" + str(row['AB']) + "', '" + str(row['R']) + "', '" + str(row['H']) + "', '" + str(row['D']) + "', '" + str(row['T']) + "', '" + str(row['HR']) + "', '" + str(row['RBI']) + "', '" + str(row['BB']) + "', '" + str(row['K']) + "', '" + str(row['BA']) + "', '" + str(row['OBA']) + "', '" + str(row['SlgA']) + "', '" + str(row['OPS']) + "', '" + str(row['wOBA']) + "', '" + str(row['SH']) + "', '" + str(row['F']) + "', '" + str(row['SF']) + "', '" + str(row['GDP']) + "', '" + str(row['SB']) + "', '" + str(row['CS']) + "', '" + str(row['LOB']) + "', '" + str(row['LOB%']) + "', '" + str(row['SRAT']) + "')"
            cursor.execute(query)

    ## Create or modify the psss_team_wc data table if wildcard stats have been published.
    # Truncate the table first the table must be rebuilt every time the script is run.
    query = "TRUNCATE TABLE psss_team_wc"
    cursor.execute(query)
    if working_stats_wc:
        # Iterate through working_stats_wc_dfo.
        for index, row in working_stats_wc_dfo.iterrows():
            # Add new entry to db.
            query = "INSERT INTO psss_team_wc VALUES ('" + str(row['Season']) + "', '" + str(row['Team']) + "', '" + str(row['GB']) + "')"
            cursor.execute(query)


############################################################################
#### #### #### ####    ***  Functions end here.  ***    #### #### #### #####
############################################################################




# Clear first output line.
print()

# Initialize global variables.
error_no = 0
error_log = str()
league_url = str()
league_name = str()

# Return UTC time and timestamp.
now_utc = datetime.now(timezone.utc)
now_utc_ts = round(now_utc.timestamp())

# Return the current year as a string.
season_c = str(now_utc.strftime("%Y"))

# Setup dirname
dirname = os.path.dirname(__file__)

# Setup the conf file.
config_file = os.path.join(dirname, 'Config/psss.conf')

# Does the config file exist?
config_exists = os.path.exists(config_file)
if not config_exists:
    print(
            '''
            PsychoStats for Scoresheet is not properly installed.
            The configuration file either does not exist,
            or is not located in the correct folder.
            Please consult the README.md file and try again.
            '''
        )
    print()

# Read the config file.
config = configparser.ConfigParser()
config.read(config_file)
config.sections()
['Database']

# Read database config.
dbhost = config['Database']['DBHost']
dbuser = config['Database']['DBUser']
dbpsswd = config['Database']['DBPsswd']
dbname = config['Database']['DBName']
dbport = config['Database']['DBPort']

# Connect to the database
psss_db = pymysql.connect(
    host=dbhost,
    port=dbport,
    user=dbuser,
    password=dbpsswd,
    database=dbname,
    charset='utf8mb4',
    cursorclass=pymysql.cursors.DictCursor,
    autocommit=True
)
cursor = psss_db.cursor()

# Get the output directory
cursor.execute("SELECT value FROM psss_config WHERE conftype='main' AND var='output_folder'")
data = cursor.fetchone()
if data:
    output_dir = str(data['value'])
else:
    output_dir = str()

# Check if the output directory exists.
if not bool(output_dir):
    output_dir = os.path.join(dirname, 'Output')

# Create the output directory if it doesn't exist.
if not os.path.isdir(output_dir):
    os.makedirs(output_dir)

    # Output.
    print("WARNING:  \"" + output_dir + "\"  does not exist.")
    print("CREATING:  \"" + output_dir + "\"")
    print()

    # Log entry.
    error_no += 1
    error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",warning,DEFAULT,\"" + output_dir + "\"  does not exist.\n"
    error_no += 1
    error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",info,DEFAULT,CREATING:  \"" + output_dir + "\"\n"

# Get the error log file name.
cursor.execute("SELECT value FROM psss_config WHERE conftype='main' AND var='error_log_fname'")
data = cursor.fetchone()
if data['value']:
    error_log_fname = str(data['value']) + '.csv'
    error_log_fname = os.path.join(output_dir, error_log_fname)
else:
    print(
        '''
        FATAL:  You must configure an error log file name prefix in the main configuration menu
                of the admin control panel in the PsychoStats web front end before running this script.

                There is a good chance your PsychoStats installation is seriously broken.
                Please consult the README.md file and try again.

                This script will exit.
        '''
        )
    print()
    sys.exit()

# Get the league url and name.
cursor.execute("SELECT source, league_name FROM psss_config_sources WHERE enabled='1' AND idx=(SELECT MIN(idx) FROM psss_config_sources)")
data = cursor.fetchone()
if (data):
    league_url = str(data['source'])
    league_name = str(data['league_name'])
else:
    print("FATAL:  You must configure and enable a league page source in the web front end before running this script.")
    print()
    print("This script will exit.")
    print()

    # Log entry.
    error_no += 1
    error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",fatal,DEFAULT,PsychoStats script could not be run.  No league page source configured."

    # Generate the error log and exit.
    generate_psss_error_log()
    sys.exit()

# Get lastupdate from the database.
cursor.execute("SELECT lastupdate FROM psss_state LIMIT 1")
data = cursor.fetchone()
if data:
    lastupdate = data['lastupdate']
else:
    lastupdate = 0

# Check to see if league page exists.
request = requests.get(league_url)
if not request.status_code == 200:
    print(
        '''
        FATAL:  The league page does not exist.  Please check that you have
                entered the league url and league name into the League Page URL
                section of the Manage menu in the Admin Control Panel.

                The script will exit.
        '''
        )

    # Log entry.
    error_no += 1
    error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",fatal,DEFAULT,The league page at URL:  " + league_url + "  does not exist."

    # Generate the error log and exit.
    generate_psss_error_log()
    sys.exit()

# Load the league page into a variable.
with urlopen(league_url) as f:
    raw_lp_dump = f.read().decode()

## Check to see if the page and url are correct.
my_regex = r"^<body>[\r\n\|\r|\n]+<h1>.+(" + re.escape(league_name) + r").+?</h1>$"
if not re.search(my_regex, raw_lp_dump, re.MULTILINE):

    print(
        '''
        FATAL:  Either the league page is not up or there is a mismatch between
                the league URL and the league name.  Please check that you have
                entered the league url and league name into the League Page URL
                section of the Manage menu in the Admin Control Panel.

                The script will exit.
        '''
        )

    # Log entry.
    error_no += 1
    error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",fatal,DEFAULT,Either the league URL:  " + league_url + "  cannot be reached or there is a mismatch between the league URL and the league name."

    # Generate the error log and exit.
    generate_psss_error_log()
    sys.exit()


# Log entry.
error_no += 1
error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",info,DEFAULT,Initializing data processing for URL:  " + league_url + "  The URL is accessible and is for the correct league.\n"

# Get check loop variable from database.
cursor.execute("SELECT value FROM psss_config WHERE conftype='main' AND var='check_loop'")
data = cursor.fetchone()
if  data['value']:
    check_loop = int(data['value'])
else:
    check_loop = 0

# Check to see if the stats pages have been updated or continue if checkloop is not set.
if check_loop != 0:
    # Setup month range (April to October).
    mr = range(4, 11)
    # Return the current month as an integer.
    mc = int(now_utc.strftime("%-m"))

    # Only engage check loop if the month is April to October.
    if mc in mr:
        grp_check(check_loop, league_url)
    else:
        print(
            '''
            INFO:   This is the off season, the league page will not be checked for weekly results.
            '''
            )

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",info,DEFAULT,This is the off season.  The league page will not be checked for weekly results.\n"

# Get the list of available seasons from the league page.
my_regex = r"^Past seasons: +(.+)?</a><br>$"
seasons_h_line = re.search(my_regex, raw_lp_dump, re.MULTILINE).group(1)
my_regex = r"/(2[0-9][0-9][0-9])/"
seasons_h = re.findall(my_regex, seasons_h_line)

# Get the date on the league page.
my_regex = r"^<body>[\r\n\|\r|\n]+<h1>.+ ?((?:[1-9]|[1][0-9])-(?:[1-9]|[1-3][0-9])-(?:[0-9][0-9]))</h1>$"
pagedate = re.search(my_regex, raw_lp_dump, re.MULTILINE).group(1)

# Convert league page date to epoch time.
pattern = '%m-%d-%y'
pagedate = int(time.mktime(time.strptime(pagedate, pattern)))

# Add one and a half days to page date.
pagedate = pagedate + 129600

# Check to see if the stats have already been updated.
if ((lastupdate > pagedate) and (seasons_h[0] != season_c)):

    print(
        '''
        INFO:   The stats have already been updated.

                The script will exit.
        '''
        )

    # Log entry.
    error_no += 1
    error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",info,DEFAULT,The stats have already been updated.  The league pages will not be processed."

    # Generate the error log and exit.
    generate_psss_error_log()
    sys.exit()

# Update the database with the league page date.
query = "UPDATE psss_config_sources s SET s.date='" + str(pagedate) + "' WHERE s.league_name='" + league_name + "'"
cursor.execute(query)

# Process the current season and skip the current season if it is in the historical list.
season_c_skipped = int()
if (seasons_h[0] != season_c):
    process_data (league_url, season_c, league_name, raw_lp_dump)
else:
    season_c_skipped = 1

# Check for single season mode
cursor.execute("SELECT value FROM psss_config WHERE conftype='main' AND var='single_season'")
data = cursor.fetchone()
if data:
    single_season = int(data['value'])
else:
    single_season = 0

# If single_season is 1 and season_ns is not true, set it to season_c.
if (single_season == 1 and not season_ns):
    single_season = int(season_c)
elif (single_season == 1 and season_ns):
    single_season = int(seasons_h[0])
else:
    single_season = 2011

# Remove duplicate list item.
if (seasons_h[0] == seasons_h[1]):
    seasons_h.pop(1)

# Remove all members of list dating earlier than single_season.
seasons_h = [i for i in seasons_h if not int(i) < single_season]

# If there are seasons in the database earlier than single_season delete that season data,
# truncate the team_ids_name and team_profile tables, and remove non-admin users.
cursor.execute("SELECT season FROM psss_team_adv WHERE season<'" + str(single_season) + "' LIMIT 1")
data = cursor.fetchone()
if data:
    cursor.execute("DELETE FROM psss_user WHERE accesslevel<'99';")
    cursor.execute("DELETE FROM psss_team_adv WHERE season<'" + str(single_season) + "';")
    cursor.execute("DELETE FROM psss_team_ids_names WHERE lastseen<'" + str(single_season) + "-01-01';")
    cursor.execute("DELETE FROM psss_team_def WHERE season<'" + str(single_season) + "';")
    cursor.execute("DELETE FROM psss_team_off WHERE season<'" + str(single_season) + "';")
    cursor.execute("DELETE FROM psss_seasons_h WHERE season_h<'" + str(single_season) + "';")
    cursor.execute("TRUNCATE psss_team_wc")
    psss_db.commit()

    # Set the team_profile table to defaults.
    cursor.execute("SELECT team_id FROM psss_team_profile")
    data_prof = cursor.fetchall()
    for row in data_prof:
        for key,value in row.items():
            cursor.execute("UPDATE psss_team_profile SET userid=DEFAULT, name=DEFAULT, email=DEFAULT, discord=DEFAULT, twitch=DEFAULT, youtube=DEFAULT, website=DEFAULT, icon=DEFAULT, cc=DEFAULT, logo=DEFAULT WHERE team_id='" + str(value) +"'")

    # Log entry.
    error_no += 1
    error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",info,DEFAULT,Single Season Mode: Team data and users for previous seasons have been deleted and team profiles reset to defaults.\n"




################################################################################
#### #### #### ####  ***  Process historical seasons.  ***  #### #### #### #####
################################################################################
for season_h in list(seasons_h):

    # Check to see if season has already been processed.
    query = "SELECT games_back FROM psss_team_adv WHERE season='" + str(season_h) + "' AND (games_back='lc' OR games_back='dtlc')"
    cursor.execute(query)
    data = cursor.fetchone()
    if data:
        print("INFO:  Data for " + season_h + " has already been processed.  Continuing to the next season.")
        print()

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",info,DEFAULT,Data for " + season_h + " has already been processed.  Continuing to the next season.\n"

        continue

    # Get the league url name (doesn't always match league name in current url).
    my_regex = r"</a> <a href='/archive/" + str(season_h) + "/(:?FOR_|C)WWW/(.+.htm)?' target=_blank>" + str(season_h) + "</a>"
    lurl_name = re.search(my_regex, raw_lp_dump, re.MULTILINE).group(2)

    # Split the league url.
    lu_list = league_url.split("/")

    # Build the historical season url.
    lu_list.insert(3, 'archive')
    lu_list.insert(4, season_h)
    lu_list[6] = lurl_name
    season_url = "/".join(lu_list)

    # Check to see if league page exists.
    request = requests.get(season_url)
    if not request.status_code == 200:
        print(
            '''
            WARNING:  The historical league page does not exist.

                    This historical season will be skipped.
            '''
            )

        # Log entry.
        error_no += 1
        error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",warning,DEFAULT,The league page at URL:  " + season_url + "  does not exist.\n"
        
        # Remove the season from the list and continue.
        seasons_h.remove(season_h)
        continue

    # Load the league page into a variable.
    with urlopen(season_url) as f:
        raw_lp_dump = f.read().decode()
    
    # Log entry.
    error_no += 1
    error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",info,DEFAULT,Initializing data processing for season:  " + season_h + "\n"

    # Get the date on the league page.
    my_regex = r"^<body>[\r\n\|\r|\n]+<h1>.+ ?((?:[1-9]|[1][0-9])-(?:[1-9]|[1-3][0-9])-(?:[0-9][0-9]))</h1>$"
    pagedate = re.search(my_regex, raw_lp_dump, re.MULTILINE).group(1)

    # Convert league page date to epoch time.
    pattern = '%m-%d-%y'
    pagedate = int(time.mktime(time.strptime(pagedate, pattern)))

    # Process the historical season.
    process_data (season_url, season_h, league_name, raw_lp_dump)


## Create or modify the psss_seasons_h data table.
# Iterate through seasons_h.
for season_h in seasons_h:
    # Get the season_h data from the database.
    query = "SELECT season_h FROM psss_seasons_h WHERE season_h='" + str(season_h) + "'"
    cursor.execute(query)
    data = cursor.fetchone()
    # Is the table empty?
    if not data:
        # Add new entry to db.
        query = "INSERT INTO psss_seasons_h VALUES ('" + str(season_h) + "')"
        cursor.execute(query)

## Output the current season and update timestamp to the database.
# Get the league page source id from the database.
query = "SELECT id FROM psss_config_sources WHERE enabled=1 LIMIT 1"
cursor.execute(query)
data = cursor.fetchone()
source_id = data['id']
# Get the state data from the database.
query = "SELECT * FROM psss_state LIMIT 1"
cursor.execute(query)
data = cursor.fetchone()
# Is the table empty?
if data:
    # Update database.
    query = "UPDATE psss_state s SET s.source='" + str(source_id) + "', s.lastupdate='" + str(now_utc_ts) + "', s.season_c='" + str(season_c) + "'"
    cursor.execute(query)
else:
    # Add new entry to db.
    query = "INSERT INTO psss_state VALUES ('" + str(source_id) + "', '" + str(now_utc_ts) + "', '" + str(season_c) + "')"
    cursor.execute(query)

# Print exit status and output to log.
print(
    '''
    INFO:   Data processed and stats updated.

            The script will now exit.
    '''
    )

# Log entry.
error_no += 1
error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",info,DEFAULT,Data processing complete."

# Generate the error log and exit.
generate_psss_error_log()
sys.exit()

