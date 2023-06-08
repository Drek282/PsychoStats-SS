This is an unofficial version of PsychoStats by Stormtrooper. Updated to work with PHP 7.1.0+ and MySQL 5.5.0+.  The minimum required version of Python is 3.x.  This version of Psychostats has been modified to display Scoresheet Baseball team statistics.

All of the versions in this repository should be considered beta software.  Prior to 2010 PsychoStats was tested on thousands of websites with logs from thousands of game servers.  The base PsychoStats code should be robust and stable, the changes that have been made to adapt this to Scoresheet Baseball are significant but the base code should still be robust and stable, but there are no guarantees.

This also open source software.  The code can be modified by anyone who wishes to do so.  This means that the code on any given site hosting these stats may not match the code in the repository.  Every effort has been made to ensure that the code is stable and secure, but there are no guarantees.  To the extent allowed by law anyone using this software must do so at their own risk, whether you are hosting the software, or an end user.

This version of PsychoStats is not intended to replace the official league stats offered by Scoresheet Games.  It's purpose is to offer granular team statistics in a format that allows for quick and easy comparison.  It also provides historical league data in a format that is easy to access and offers that same level of granular comparative context.  If you are joining a new league, this software will allow you to quickly and easily find out who is who in your new league, and what kinds of players they value.  And for nerds such as myself, these stats are just fun.

## **Requirements**

You will need access to web hosting.  That can sound intimidating, but if you are interested in hosting these stats for your personal use, all you need to do is set up a traditional "LAMP stack" on your home network, with Python 3 support for the backend.  There is a lot of information on the web on how to do that.  Google is your friend.  If you want to host this software for your league, most web hosting services will offer the necessary components, which are a MySQL database, PHP and Python 3.

The "L" in "LAMP" refers to Linux, but you don't need to setup a Linux server to host this software.  This software can also be hosted on Windows using something like "[XAMPP](https://www.apachefriends.org/download.html 'XAMPP')", if you don't intend to allow web access.

See INSTALL.md for full installation and setup instructions.


You can view working demos of PsychoStats for Scoresheet Baseball at the following links: 

* [P-AL145](https://displaced.zone/psss_bb_145/ "P-AL145")
* [P-AL 152](https://displaced.zone/psss_bb_152/ "P-AL152")
* [AL Auction](https://displaced.zone/psss_bb_auction/ "AL Auction")
* [NL Coast to Coast](https://displaced.zone/psss_bb_coast/ "NL Coast to Coast")
* [NL Crash Davis](https://displaced.zone/psss_bb_crash/ "NL Crash Davis")
* [NL Brian Fawcett](https://displaced.zone/psss_bb_fawcett/ "NL Brian Fawcett")
* [NL JENKINS](https://displaced.zone/psss_bb_jenkins/ "NL JENKINS")
* [NL Justice League](https://displaced.zone/psss_bb_justice/ "NL Justice League")
* [BL DwMurphy](https://displaced.zone/psss_bb_murphy/ "BL DwMurphy")
* [AL Phelps](https://displaced.zone/psss_bb_phelps/ "AL Phelps")
* [BL Mr Mark Ward](https://displaced.zone/psss_bb_ward/ "BL Mr Mark Ward")
* [AL Bruce Worrall](https://displaced.zone/psss_bb_worrall/ "AL Bruce Worrall")


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

The basic text for the default privacy policy has been copied from the default WordPress privacy policy.

The generic Hall of Fame Plaque image is a modified version of a creative commons licensed image downloaded from https://www.flickr.com/.

PsychoStats makes use of various open source libraries, some precompiled.  Among these libraries are jQuery, the Smarty Template Engine and JpGraph.  Most of the versions used in PsychoStats are obsolete but still functional and secure.  PsychoStats would not function without them and a special debt of gratitude is owed to the creators and maintainers of those libraries.
