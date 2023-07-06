# PsychoStats for Scoresheet Baseball Installation


## BASICS

PsychoStats has two parts. The local Python script in the 'root' folder that does all the database updates and most of the data processing. And the PHP 'www' files that comprise the web front end that allow you to view the stats web pages on your website.  The PHP front end also processes the data for the awards.

The local 'root' files SHOULD NEVER BE located inside your website directory tree.  If you put it somewhere where the webserver can access it then any user on the Internet would be able to read your Config/psss.conf file and see your database settings, user name and password. You have been warned.


## IF YOU ARE UPGRADING FROM A PREVIOUS VERSION OF PSYCHOSTATS FOR SCORESHEET BASEBALL

There is no option currently for a graceful or automated upgrade.  Typically in an update you will need to upload all the files other than the install folder and the config.php file from the new version www folder to your web hosting folder.  If the psss.py script has changed you will need to upload that from the root folder, reset the stats from the Manage page of your Admin CP and rerun the psss.py script.  If the database structure has changed you will need to upload the "install" folder from the www folder to your web hosting folder and reinstall the software.


## INSTALLATION

1. If you've already unzipped the archive you will have a directory structure that looks like this:
  
\root  
\www  
changelog.txt  
INSTALL.md  
license.txt  
readme_*.txt  
README.md  

2. You will need a MySQL or MariaDB database, user and password.  If you don't know how to set that up, Google is your friend.  It's fairly simple and there is a lot of information on how to do that on the web.  Make sure your user has full permissions on the database.

You should not use a database super user for PsychoStats, especially in a production context where you have exposed your stats to the web.  You should create a user then create a database, then make sure your user has full permissions on that database, before you run the installer.

3. You will need to have Python version 3.x installed to run the stats generation script, and your web hosting will need to have PHP installed on it.  Note that there are a number of Python and PHP modules that you will need to have installed.  Again, Google is your friend, if you encounter errors when you try to run the web install, or when you run the stats generation script, those errors should let you know what you need to install.

The minimum required version of Python is `3.0`, of PHP is `7.1.0` and MySQL is `5.5.0`.  Required Python modules include the `timezone`, `urlopen`, `pandas`, `numpy`, and `pymysql` modules.

4. These instructions assume you already know the basics of hosting a website.  The specifics of that will depend on your hosting arrangements.  Copy the contents of the 'www' directory to your web hosting folder.  It is recommended that you don't expose your PsychoStats web folder to the public web until you have completed the install process.

5. Browse to the stats installation wizard with your browser and follow the instructions:  
	http[s]://[domain|localhost]/[stats folder name if any]/install/

### IMPORTANT

The install process will automatically delete your install folder when it is completed.  If you need to run the install process again you will need to upload the install folder to your PsychoStats web folder again.

6. The 'root' folder contains the heart of your PsychoStats.  You can put that anywhere as long as your user has executable permissions and you can access your PsychoStats database from that machine.  The contents of the 'root' folder should not be exposed to the web.  Edit the 'Config/psss.conf' in that folder with your PsychoStats database name, user and password.

### A NOTE ON SINGLE SEASON STANDARD OR PRIVATE LEAGUES

You can use PschoStats for Scoresheet Baseball for leagues that are not continuing leagues.  However, if you do so you should check the option for "Single Season Mode" in the main configuration of the Admin CP.  In single season mode historical seasons will not be parsed.  Seasons where the team numbers do not match the same owners, and the number of teams per season change, will break the parsing of historical seasons, as well as the user account functionality.

In single season mode non-admin user accounts will be deleted at the start of every season and users will need to re-register if they wish to have an account linked to their team.

### RUNNING PSSS.PY ON A SCHEDULE

The sats script, psss.py, is intended to be run on a schedule.  The weekly game results are typically released shortly after 12:00pm on Mondays, Eastern time.  You can use cron to run the script on Linux, or use the Windows scheduler.  The cron.d script should contain the following on Linux:

> 0 13 * * mon [user] . /home/[user]/.profile; /home/[user]/[path to folder containing script]/python3 psss.py

Example:

> 0 13 * * mon norm . /home/norm/.profile /home/norm/.local/share/psychostats/python3 psss.py

In the Main section of the Config page in the Admin CP you will see the following setting `Check Number for Published Game Results`.  The script will check to see if the game results have been published once every ten minutes until that number is reached.  When that number is reached it will exit with an error message if the `Check String` is not matched, see below.  A good number is 36 which means the script will run for 6 hours before it fails and exits.

In the Main section of the Config page in the Admin CP you will see the following setting `Check String for Published Game Results`.  This is the message Scoresheet uses to announce the fact that game results have been published.  Typically this should not need to be changed, but Scoresheet has been known, on occasion, to change the message.  If they do that setting will need to be changed to match that.  The two matches so far have been `game results are up` and `game results are now up`, the current setting should match either of those.

### Security Notes

Common sense is your best protection.  You want a distinct PsychoStats database user that only has permissions on the PsychoStats database.  Never run the PsychoStats script as root in Linux.  Never use your MySQL/MariaDB super user as your PsychoStats user in any context other than a closed testing environment.

The config.php and Config/psss.conf files contain your PsychoStats database user name and password, once the config.php file has been written in the install process, and once you've entered your information into the Config/psss.conf files, the respective users only need read access to them.

No other user should have permissions to read them, let alone write or execute them.

The only built in defence against spam registrations is that user registrations need to be confirmed by an admin.  Because PsychoStats isn't popular software most of the spam registration scripts out there will probably not work with PsychoStats, so spam registrations should not, at this time, be problematic.  The information in the user profiles, should those be enabled, is only visible to logged in users.
