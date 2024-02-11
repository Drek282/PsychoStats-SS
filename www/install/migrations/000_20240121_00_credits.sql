INSERT INTO `psss_config` (`id`, `conftype`, `section`, `var`, `value`, `label`, `type`, `locked`, `verifycodes`, `options`, `help`) 
    VALUES 
        (2702,'theme','credits','credits','
            <h1>Credits</h1>
            <div>
            <ul>
                <li><strong>Jason Morriss, a.k.a. Stormtrooper</strong>—the original creator of PsychoStats</li>
                <li><a href="http://www.scoresheet.com/"><strong>Scoresheet Fantasy Sports</strong></a>—for the most addictive and compelling fantasy baseball experience available anywhere</li>
                <li><strong>wakachamo, Rosenstein, Solomenka and janzagata</strong>—for contributions to the code</li>
                <li><a href="https://www.behance.net/alessandroart"><strong>Alessandro Poli</strong></a>—for the most excellent rat used in the VRat logo</li>
                <li><a href="https://openclipart.org/artist/GusEinstein"><strong>Gustavo Ferreira</strong></a>—for the bat used in the VRat logo</li>
                <li><strong>RoboCop from APG and Mike Gasson</strong>—for feedback, support and encouragement</li>
                <li>PsychoStats makes use of various open source libraries, some precompiled.  Among these libraries are jQuery, the Smarty Template Engine and JpGraph.  Most of the versions used in PsychoStats are obsolete but still functional and secure.  PsychoStats would not function without them and a special debt of gratitude is owed to the creators and maintainers of those libraries.</li>
            </ul>
            </div>',
            'Credits','textarea',0,'','','This is the content of the Credits for PsychoStats for Scoresheet Baseball.  You can edit this to create your own custom thank you list.  It uses html formatting.'),
        (5015,'theme','credits',NULL,'Credits for PsychoStats for Scoresheet Baseball.','Credits','none',1,NULL,NULL,NULL);
