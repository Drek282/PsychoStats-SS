This is an unofficial version of PsychoStats by Stormtrooper. Updated to work with PHP 7.1.0+ and MySQL 5.5.0+.  The minimum required version of Python is 3.x.  This version of Psychostats has been modified to display Scoresheet Baseball team statistics.

\* **Oracle's MySQL `8.0+` is *NOT* supported.  Please use [MariaDB](https://mariadb.org/ "MariaDB") instead.**

All of the versions in this repository should be considered beta software.  Prior to 2010 PsychoStats was tested on thousands of websites with logs from thousands of game servers.  The base PsychoStats code should be robust and stable, the changes that have been made to adapt this to Scoresheet Baseball are significant but the base code should still be robust and stable, but there are no guarantees.

This also open source software.  The code can be modified by anyone who wishes to do so.  This means that the code on any given site hosting these stats may not match the code in the repository.  Every effort has been made to ensure that the code is stable and secure, but there are no guarantees.  To the extent allowed by law anyone using this software must do so at their own risk, whether you are hosting the software, or an end user.

This version of PsychoStats is not intended to replace the official league stats offered by Scoresheet Games.  It's purpose is to offer granular team statistics for Scoresheet Baseball leagues in a format that allows for quick and easy comparison.  It also provides historical league data in a format that is easy to access and offers that same level of granular comparative context.  If you are joining a new league, this software will allow you to quickly and easily find out who is who in your new league, and what kinds of players they value.  And for nerds such as myself, these stats are just fun.

## **What Exactly Does PsychoStats for Scoresheet Baseball Do?**

This software scrapes league statistics from the official Scoresheet Baseball league pages and displays them in tables on a web page.  The tables include columns that can be sorted by clicking on the column header.  The software includes multiple themes which look pretty sweet and are intended to, for example, display well in an iframe on an existing website.  They can also exist as a standalone website.

It is also relatively simple to modify current templates, or create new ones, although that does require some ability to work with HTML and/or CSS.

There are separate pages for overall records, offensive and defensive team stats, as well as optional division standings and a Hall of Fame, which can be configured to list historical team records for just about any statistic, but by default displays statistics such as which teams have had the best single season winning percentage, have won the most division titles and league championships, etc. etc.

See the demos below for more details.  Note that the HELP page also provides specific technical information, such as the formulas used to calculate specific advanced statistics.


## **Requirements**

You will need access to web hosting.  That can sound intimidating, but if you are interested in hosting these stats for your personal use, all you need to do is set up a traditional "LAMP stack" on your home network, with Python 3 support for the backend.  There is a lot of information on the web on how to do that.  Google is your friend.  If you want to host this software for your league, most web hosting services will offer the necessary components, which are a MySQL database, PHP and Python 3.

The "L" in "LAMP" refers to Linux, but you don't need to setup a Linux server to host this software.  This software can also be hosted on Windows using something like "[XAMPP](https://www.apachefriends.org/download.html 'XAMPP')", if you don't intend to allow wan access.  You can also install all the components individually on Windows, which would be web server software, with MySQL, PHP and Python 3.  Specific instructions on how to do that are beyond the scope of this documentation, but there is a lot of great information available on the web on how to do that, for both Linux and Windows.

See INSTALL.md for full installation and setup instructions—not including instructions on how to setup a web server.


You can view working demos of PsychoStats for Scoresheet Baseball at the following links: 
*Note that this is a testing environment and as such the availability of the demos will not be 100%, 24/7.*

* [P-AL145](https://displaced.zone/psss_bb_145/ "P-AL145")
* [P-AL 152](https://displaced.zone/psss_bb_152/ "P-AL152")
* [AL Auction](https://displaced.zone/psss_bb_auction/ "AL Auction")
* [NL Del Boca Vista](https://displaced.zone/psss_bb_boca/ "NL Del Boca Vista")
* [NL Coast to Coast](https://displaced.zone/psss_bb_coast/ "NL Coast to Coast")
* [NL Crash Davis](https://displaced.zone/psss_bb_crash/ "NL Crash Davis")
* [NL Brian Fawcett](https://displaced.zone/psss_bb_fawcett/ "NL Brian Fawcett")
* [NL JENKINS](https://displaced.zone/psss_bb_jenkins/ "NL JENKINS")
* [NL Justice League](https://displaced.zone/psss_bb_justice/ "NL Justice League")
* [BL DwMurphy](https://displaced.zone/psss_bb_murphy/ "BL DwMurphy")
* [BL Mr Mark Ward](https://displaced.zone/psss_bb_ward/ "BL Mr Mark Ward")

Note that these demos are currently hosted on an Intel NUC, which is a mini system that uses a laptop processor.  You can use any old desktop PC, or mini system, to host this software, especially if you use Linux.  Linux will run just fine on very old, very low end hardware, and this software specifically will run perfectly well on very old, very low end hardware.  The monitor, keyboard and mouse for the system hosting these stats pages is on a KVM switch, which is only used for emergency purposes.  Most of the time the system hosting these demos is accessed and managed using SSH.


## **Known Issues**

*The plan for the following issues is to either fix them, or improve them, in future versions:*

* The CSRF protection is currently not working as intended.  This should not be a serious security issue but the hope is to revisit the code and get this working at some point in the future.
* The coded language support is incomplete.  The goal is to update and finish it, as well as add support, at a minimum, for Spanish.
* The setting to allow uploads for icons has no effect.  There is no code in place to allow users to upload icons.
* Choosing the "Overwrite existing tables" option produces the exact same result as dropping and recreating the database.


## **Stuff that Remains Untested**

* Automatic deletion of user accounts on owner change.


## **Future Plans**

* Add support for Spanish and French.
* Add historical season support for private, non-continuing leagues.
* Reinstate support for division profiles.
* Add user upload functionality for icon images.
* Fix CSRF protection.


## **Credits**

Thank you to Jason Morriss, a.k.a. Stormtrooper, for all his original work. This software deserves to be used. The period between 2000 and 2005 and all the old Half-Life and Source mods represent a golden age in PC game modding. Those games deserve to be played. With a little massaging most of them still run very well on new hardware and new operating systems.

Credit to wakachamo, Rosenstein, Solomenka and janzagata for their contributions.  Thanks also to RoboCop from APG for his support and encouragement.

Credit to Alessandro Poli for the most excellet rat used in the VRAT logo.
Credit to Gustavo Ferreira for the bat used in the VRAT logo.

The basic text for the default privacy policy has been copied from the default WordPress privacy policy.

PsychoStats makes use of various open source libraries, some precompiled.  Among these libraries are jQuery, the Smarty Template Engine and JpGraph.  Most of the versions used in PsychoStats are obsolete but still functional and secure.  PsychoStats would not function without them and a special debt of gratitude is owed to the creators and maintainers of those libraries.
