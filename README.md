This is an unofficial version of PsychoStats by Stormtrooper. Updated to work with PHP 7.1.0+ and MySQL 5.5.0+.  The minimum required version of Python is 3.x.  This version of Psychostats has been modified to display Scoresheet Baseball team statistics.

All of the versions in this repository should be considered beta software.  Prior to 2010 PsychoStats was tested on thousands of websites with logs from thousands of game servers.  The base PsychoStats code should be robust and stable, the changes that have been made to adapt this to Scoresheet Baseball are significant but the base code should still be robust and stable, but there are no guarantees.

This also open source software.  The code can be modified by anyone who wishes to do so.  This means that the code on any given site hosting these stats may not match the code in the repository.  Every effort has been made to ensure that the code is stable and secure, but there are no guarantees.  To the extent allowed by law anyone using this software must do so at their own risk, whether you are hosting the software, or an end user.


## **Known Issues**

*The plan for the following issues is to either fix them, or improve them, in future versions:*

* The CSRF protection is currently not working as intended.  This should not be a serious security issue but the hope is to revisit the code and get this working at some point in the future.
* The coded language support is incomplete.  The goal is to update and finish it, as well as add support, at a minimum, for Spanish.
* The setting to allow uploads for icons has no effect.  There is no code in place to allow users to upload images.


## **Stuff that Remains Untested**

* Nothing that I know of.


## **Future Plans**

* Add support for Spanish and French.
* Add historical season support for private, non-continuing leagues.
* Reinstate support for division profiles.
* Add user upload functionality for icon images.
* Fix CSRF protection.
* Add a configurable Help page with basic information on how to use the stats, how registration works, how the search works, glossary of terms, etc.
* Add a player roster page with the basic player stats available from Scoresheet on it.  This will be the Scoresheet player stats, not their MLB stats.


## **Credits**

Thank you to Jason Morriss, a.k.a. Stormtrooper, for all his original work. This software deserves to be used. The period between 2000 and 2005 and all the old Half-Life and Source mods represent a golden age in PC game modding. Those games deserve to be played. With a little massaging most of them still run very well on new hardware and new operating systems.

Credit to wakachamo, Rosenstein, Solomenka and janzagata for their contributions.  Thanks also to RoboCop from APG for his support and encouragement.

The basic text for the default privacy policy has been copied from the default WordPress privacy policy.

The generic Hall of Fame Plaque image is a modified version of a creative commons licensed image downloaded from https://www.flickr.com/.

PsychoStats makes use of various open source libraries, some precompiled.  Among these libraries are jQuery, the Smarty Template Engine and JpGraph.  Most of the versions used in PsychoStats are obsolete but still functional and secure.  PsychoStats would not function without them and a special debt of gratitude is owed to the creators and maintainers of those libraries.
