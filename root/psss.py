#### Load libraries.
from datetime import datetime,timezone
import time
from urllib.request import urlopen
import configparser
import os
import re
import sys
import html2text
import pandas as pd
import numpy as np
import pymysql
import requests



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
            raw_lp_dump = html2text.html2text(f.read().decode())

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
def get_league_c (season_url):

    # Globals
    global error_no
    global error_log

    # Set the season_url Scoresheets URL.
    season_url_g = re.sub(r'.htm', '_G.htm', season_url, 1)

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
        raw_sp_dump = html2text.html2text(f.read().decode())
    
    # Remove empty lines.
    raw_sp_dump = re.sub(r'^( +|)\n', '', raw_sp_dump, 10000, re.MULTILINE)

    # Get second to last line of raw_sp_dump
    rsp_lines = raw_sp_dump.splitlines()
    c_line = rsp_lines[-2]
    
    # Get the team number from the championship line.
    league_c = re.search(r'([1-2][0-9]|[1-9])', c_line, 1).group(0)

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

# Generate the psss_team_ids_name table.
def generate_psss_team_ids_name (season, league_name, working_stats_com_dfo):

    # Globals
    global season_c
    global now_utc_ts
    
    # Team id names file name.
    cursor.execute("SELECT value FROM psss_config WHERE conftype='main' AND var='team_ids_name_fname'")
    data = cursor.fetchone()
    if data['value']:
        team_ids_name_fname = data['value'] + league_name + '.csv'
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
    
    # Replace with the database table name for the advanced stats.
    #DB_TABLE_TEAM_IDS_NAME='psss_team_ids_name'
    
    # Get season time in the appropriate format.
    if (season == season_c):
        tdtd = time.strftime('%Y-%m-%d %H:%M:%S', time.localtime(now_utc_ts))
    else:
        tdtd = str(season) + "-00-00 00:00:00"
    
    # Generate the team ids names table.
    team_ids_name_dfo = working_stats_com_dfo.loc[:, ['Team','Team_Name']]
    team_ids_name_dfo[['totaluses', 'firstseen', 'lastseen']] = pd.DataFrame([[1, tdtd, tdtd]], index=team_ids_name_dfo.index)
    
    ## Column Headers:
    #  1 Team ID, 2 Team Name, 3 Total Uses, 4 First Seen, 5 Last Seen
    
    # Iterate through ids names dataframe.
    for index, row in team_ids_name_dfo.iterrows():
        # Build and execute the SQL.
        query = "SELECT * FROM psss_team_ids_name WHERE team_id='" + str(row['Team']) + "' AND team_name=\"" + row['Team_Name'] + "\""
        cursor.execute(query)
        data = cursor.fetchone()
        # Does team id and team name exist in the db?
        if data:
            # Update lastseen if it is newer.
            query = "UPDATE psss_team_ids_name i SET i.lastseen= IF('" + str(row['lastseen']) + "' > i.lastseen, '" + str(row['lastseen']) + "', i.lastseen) WHERE i.team_id='" + str(row['Team']) + "' AND i.team_name=\"" + row['Team_Name'] + "\""
            cursor.execute(query)
            # TODO:  If first seen year and  last seen year don't match
            # total uses = first seen year minus last seen year +1
        else:
            # Add new name to db.
            query = "INSERT IGNORE INTO psss_team_ids_name VALUES ('" + str(row['Team']) + "', \"" + row['Team_Name'] + "\", '1', '" + str(row['firstseen']) + "', '" + str(row['lastseen']) + "')"
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
            query = "INSERT INTO psss_team_profile VALUES ('" + str(row['Team']) + "', DEFAULT, DEFAULT, DEFAULT, DEFAULT, DEFAULT, DEFAULT, DEFAULT, DEFAULT, DEFAULT, DEFAULT, 1)"
            cursor.execute(query)
    
    ## Column Headers:
    #  1 Team ID, 2 userid, 3 Owner Name, 4 email, 5 discord, 6 twitch, 7 youtube, 8 website, 9 icon, 10 country code, 11 logo, 12 name locked




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
    
    # Remove raw_lp_dump prefix to search string.
    my_regex =  r"(^.+Standings, Pitching +W   L   .+_)"
    my_list = re.split(my_regex, raw_lp_dump, 1, re.MULTILINE)
    raw_lp_dump = my_list[1] + my_list[2]

    # Empty the list.
    my_list = list()
    
    # Remove raw_lp_dump suffix to search string.
    my_regex =  r"(^(?:| +)(?:[1-9]|[1-2][0-9])  (?:[A-Z].+ [A-Z].+) +(?:[0-9]|1[0-9])-(?:[0-9]|1[0-9]):  .+,)"
    my_list = re.split(my_regex, raw_lp_dump, 1, re.MULTILINE)
    raw_lp_dump = my_list[0]

    # Empty the list.
    my_list = list()
    
    # Remove whitespace at start of lines.
    my_regex =  r"(^ +)"
    raw_lp_dump = re.sub(my_regex, '', raw_lp_dump, 200, re.MULTILINE)
    
    # Remove whitespace at end of lines.
    my_regex =  r"( +$)"
    raw_lp_dump = re.sub(my_regex, '', raw_lp_dump, 200, re.MULTILINE)

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
    f.write(raw_lp_dump)
    f.close()
    
    # Create defensive stats variable.
    my_regex =  r"(^_AB    R    H  .+_$)"
    my_list = re.split(my_regex, raw_lp_dump, 1, re.MULTILINE)
    working_stats_def_pre = my_list[0]

    # Delete empty lines.
    working_stats_def_pre = os.linesep.join([s for s in working_stats_def_pre.splitlines() if s])

    # Empty the list.
    my_list = list()
    
    # Create offensive stats variable.
    my_regex =  r"(^_AB    R    H  .+_$)"
    my_list = re.split(my_regex, raw_lp_dump, 1, re.MULTILINE)
    working_stats_off = my_list[1] + my_list[2]

    # Delete empty lines.
    working_stats_off = os.linesep.join([s for s in working_stats_off.splitlines() if s])

    # Empty the list.
    my_list = list()

    # Split off the wildcard standings if they exist.
    my_regex = r"(^_Wild Card Race  .+_$)"
    if re.search(my_regex, working_stats_def_pre, re.MULTILINE):
        my_list = re.split(my_regex, working_stats_def_pre, 1, re.MULTILINE)
        working_stats_def = my_list[0]
        working_stats_wc = my_list[1] + my_list[2]
    else:
        working_stats_def = working_stats_def_pre
        working_stats_wc = str()
    
    # Clean up.
    my_list = list()
    working_stats_def_pre = str()
    
    # Split working_stats_def into division standings.
    my_regex = r"(^_.+ Standings, Pitching  +.+_$)"
    my_list = re.split(my_regex, working_stats_def, 9, re.MULTILINE)
    # Remove empty keys.
    my_list = [ek for ek in my_list if ek]

    # Empty working_stats_def.
    working_stats_def = str()

    # Iterate through division keys and create a division field.
    div = str()
    for i in range(len(my_list)):
        if re.match(my_regex, my_list[i], re.MULTILINE):
            div = my_list[i].split()[0]
            div = div[1:]

            continue
        
        # Delete first line.
        my_list[i] = my_list[i][1:]

        # Append division field to every line.
        if div:
            my_list[i] = my_list[i].replace("\n", " " + div + "\n")
        else:
            my_list[i] = my_list[i].replace("\n", " na\n")
    
    # Recreate the working_stats_def string.
    working_stats_def = "\n".join(my_list)

    # Add the last division name to the last field.
    if div:
        working_stats_def = working_stats_def + " " + div
    else:
        working_stats_def = working_stats_def + " na"

    # Clean up.
    my_list = list()

    ## Clean up working_stats_def.
    if div:
        # Set the header line.
        my_regex =  r"(\A_[A-Z].+ Standings, Pitching )"
        working_stats_def = re.sub(my_regex, 'Team Team_Name ', working_stats_def, 1)
        my_regex =  r"(_$)"
        working_stats_def = re.sub(my_regex, ' Division', working_stats_def, 1, re.MULTILINE)
        # Remove internal header lines.
        my_regex =  r"(_.+_)"
        working_stats_def = re.sub(my_regex, '', working_stats_def)
    else:
        # Set the header line.
        my_regex =  r"(\AStandings, Pitching )"
        working_stats_def = re.sub(my_regex, 'Team Team_Name ', working_stats_def, 1)
        my_regex =  r"(_ na$)"
        working_stats_def = re.sub(my_regex, ' Division', working_stats_def, 1, re.MULTILINE)

    # Remove empty lines.
    working_stats_def = os.linesep.join([s for s in working_stats_def.splitlines() if s])

    # Set the header line for working_stats_off and remove empty lines.
    my_regex =  r"(\A_)"
    working_stats_off = re.sub(my_regex, 'Team ', working_stats_off, 1)
    my_regex =  r"(_$)"
    working_stats_off = re.sub(my_regex, '', working_stats_off, 1, re.MULTILINE)
    working_stats_off = os.linesep.join([s for s in working_stats_off.splitlines() if s])

    # Add a newline to the last field.
    #working_stats_off = working_stats_off + "\n"

    # Set the header line for working_stats_wc and remove empty lines.
    if working_stats_wc:
        my_regex =  r"(\A_Wild Card Race )"
        working_stats_wc = re.sub(my_regex, 'Team ', working_stats_wc, 1)
        my_regex =  r"(_$)"
        working_stats_wc = re.sub(my_regex, '', working_stats_wc, 1, re.MULTILINE)
        working_stats_wc = os.linesep.join([s for s in working_stats_wc.splitlines() if s])
    
    ## Remove team names from working_stats_def and working_stats_wc and create team_names_def variable.
    # working_stats_def
    my_regex =  r"^((?:[1-9]|[1-9][0-9])  )(.+)( +(?:1[0-9][0-9]| [1-9][0-9]|  [0-9]) (?:1[0-9][0-9]| [1-9][0-9]|  [0-9])  \.[0-9][0-9][0-9] .+)$"
    working_stats_def_nr = re.sub(my_regex, r'\g<1>###\g<3>', working_stats_def, 40, re.MULTILINE)
    team_names_def = re.sub(my_regex, r'\g<1>\g<2>', working_stats_def, 40, re.MULTILINE)
    # Remove whitespace at end of lines in team_names_def.
    my_regex =  r"( +$)"
    team_names_def = re.sub(my_regex, '', team_names_def, 40, re.MULTILINE)
    # Fix the header line in team_names_def.
    my_regex =  r"(^.+$)"
    team_names_def = re.sub(my_regex, 'Team Team_Name', team_names_def, 1, re.MULTILINE)
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
    # Read the csv file into a DataFrame objects.
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

    # Create the ADV stats table.
    working_stats_adv_dfo = working_stats_com_dfo.drop(['Team_Name','ERA','CG','ShO','RS','Sv','IP','RA','ER','HA','BAA','BBA','KA','WP','AB','R','H','D','T','HR','RBI','BB','K','BA','OBA','SlgA','SH','F','SF','GDP','SB','CS','LOB','OP','DP','E','OSB','OCS','PB'], axis=1)

    # Set division title status for historical seasons.
    if ((season != season_c) or season_c_skipped):
        set_divt_status(working_stats_adv_dfo)

    # Set league championship status for historical seasons.
    league_c = 0
    if ((season != season_c) or season_c_skipped):
        league_c = get_league_c(season_url)

    ## Column Headers:
    #  1 Season, 2 Team Number, 3 Team Division, 4 GP, 5 W, 6 L, 7 W%, 8 GB, 9 RDiff, 10 Pythag

    # Create the DEF stats table.
    working_stats_def_dfo = working_stats_com_dfo.drop(['Team_Name','Division','GP','W','L','pct.','GB','RS','AB','R','H','D','T','HR','RBI','BB','K','BA','OBA','SlgA','SH','F','SF','GDP','SB','CS','LOB'], axis=1)
    # Calculate RA9.
    working_stats_def_dfo['RA9'] = round(working_stats_def_dfo['RA'] / working_stats_def_dfo['IP'] * 9, 2)
    # Calculate WHIP.
    working_stats_def_dfo['WHIP'] = round((working_stats_def_dfo['HA'] + working_stats_def_dfo['BBA']) / working_stats_com_dfo['IP'], 2)
    # Calculate DRat.
    working_stats_def_dfo['DRat'] = round(((working_stats_def_dfo['OP'] - working_stats_def_dfo['E'] - working_stats_def_dfo['PB']) / working_stats_def_dfo['IP'] + working_stats_def_dfo['DP'] / (working_stats_def_dfo['HA'] + working_stats_def_dfo['BBA']) * 0.5 + working_stats_def_dfo['OCS'] / (working_stats_def_dfo['OSB'] + working_stats_def_dfo['OCS']) * 0.05) * 5, 2)
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
    if (season == season_c):
        generate_psss_tables(league_name, working_stats_com_dfo)
    
    # Generate the team ids names table.
    generate_psss_team_ids_name(season, league_name, working_stats_com_dfo)


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
            query = "UPDATE psss_team_adv a SET a.games_played='" + str(row['GP']) + "', a.wins='" + str(row['W']) + "', a.losses='" + str(row['L']) + "', a.win_percent='" + str(row['pct.']) + "', a.games_back='" + str(row['GB']) + "', a.team_rdiff='" + str(row['RDiff']) + "', a.pythag='" + str(row['Pythag']) + "' WHERE a.season='" + str(row['Season']) + "' AND a.team_id='" + str(row['Team']) + "'"
            cursor.execute(query)
        else:
            # Add new entry to db.
            query = "INSERT INTO psss_team_adv VALUES ('" + str(row['Season']) + "', '" + str(row['Team']) + "', '" + str(row['Division']) + "', '" + str(row['GP']) + "', '" + str(row['W']) + "', '" + str(row['L']) + "', '" + str(row['pct.']) + "', '" + str(row['GB']) + "', '" + str(row['RDiff']) + "', '" + str(row['Pythag']) + "')"
            cursor.execute(query)
    
    # Output league championship status to psss_team_adv data table
    if (league_c != 0):
        query = "UPDATE psss_team_adv a SET a.games_back= CASE WHEN games_back='dt' THEN 'dtlc' ELSE 'lc' END WHERE a.season='" + season + "' AND a.team_id='" + league_c + "'"
        cursor.execute(query)

    ## Create or modify the psss_team_def data table.
    # Iterate through working_stats_def_dfo.
    for index, row in working_stats_def_dfo.iterrows():
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
if (data['source'] and data['league_name']):
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
    raw_lp_dump = html2text.html2text(f.read().decode())

## Check to see if the page and url are correct.
head_rlp_dump = raw_lp_dump.partition('\n')[0]
my_regex = r".+(" + re.escape(league_name) + r").+"
if not re.match(my_regex, head_rlp_dump):

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

# Check to see if the stats pages have been updated or continue if
# checkloop is not set.
if check_loop != 0:
    grp_check(check_loop, league_url)

# Get the list of available seasons from the league page.
my_regex = r"^\[(2[0-9][0-9][0-9])\]\(.+$"
seasons_h = re.findall(my_regex, raw_lp_dump, re.MULTILINE)

# Remove all members of list dating 2010 or earlier.
for i in range(len(seasons_h)):
    if (int(seasons_h[i]) < 2011):
        seasons_h.pop(i)

# Get the date on the league page.
my_regex = r"^.+ ((?:[1-9]|[1][0-9])-(?:[1-9]|[1-3][0-9])-(?:[0-9][0-9]))$"
pagedate = re.match(my_regex, head_rlp_dump).group(1)

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
    process_data (season_c, league_name, raw_lp_dump)
else:
    season_c_skipped = 1




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

    # Build the season url.
    lu_list = league_url.split("/")
    lu_list.insert(3, 'archive')
    lu_list.insert(4, season_h)
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
        raw_lp_dump = html2text.html2text(f.read().decode())
    
    # Log entry.
    error_no += 1
    error_log = error_log + str(error_no) + "," + str(now_utc_ts) + ",info,DEFAULT,Initializing data processing for season:  " + season_h + "\n"

    # Get the date on the league page.
    head_rlp_dump = raw_lp_dump.partition('\n')[0]
    my_regex = r"^.+ ((?:[1-9]|[1][0-9])-(?:[1-9]|[1-3][0-9])-(?:[0-9][0-9]))$"
    pagedate = re.match(my_regex, head_rlp_dump).group(1)

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

