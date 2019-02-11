<h1>About</h1>

This project was started at the request of Aideron Technologies CEO in 2013. 
Try LMeve's Database module here: http://pozniak.pl/database/index.php
follow lmeve production and get more information: http://pozniak.pl/wp/?tag=lmeve

This app requires EVE Online corporation CEO ESI keys to function.
All Eve Related Materials are Property Of CCP Games

<h3>Please do not contact "Lukas Rox" in game for support, because I do not read eve-mail</h3>
If you find a problem, please open an Issue on GitHub project page: https://github.com/roxlukas/lmeve/issues


<h1>Setup instructions</h1>


1 install lmeve dependancies : 
  apt-get : php-mysql, php-pear, apache2, libapache2-mod-php, 
            php-cli, php-dev, libyaml-dev, php-mbstring, 
            python-yaml, mysql-server, mysql-client, unzip
  
2 Configure Apache2 :
  Alias /lmeve /var/www/lmeve/wwwroot
      <Directory /var/www/lmeve/wwwroot>
        Order allow,deny
        Allow from all
        Require all granted 
        Options FollowSymLinks
      </Directory>

3 Configure MySQL install : 
  sudo mkdir /Incoming
  cd /Incoming
  sudo wget "https://www.fuzzwork.co.uk/dump/mysql-latest.tar.bz2.md5"
  tar -xjf mysql-latest.tar.bz2 --wildcards --no-anchored '*sql' -C /Incoming/ --strip-components 1
  sudo mv *.sql /Incoming/staticdata.sql
  sudo mysql
  CREATE DATABASE lmeve;
  CREATE DATABASE EveStaticData;
  USE DATABASE lmeve;
  source /var/www/lmeve/data/schema.sql;
  USE DATABASE EveStaticData;
  source /Incoming/staticdata.sql;
  CREATE USER 'lmeve'@'%' IDENTIFIED BY 'lmpassword';  <-- your custom password here
  GRANT ALL PRIVILEGES ON `lmeve`.* TO 'lmeve'@'%';
  GRANT ALL PRIVILEGES ON `EveStaticData`.* TO 'lmeve'@'%';
  FLUSH PRIVILEGES;

4 install lmeve core : 
     cd /var/www
     sudo git clone https://github.com/roxlukas/lmeve
     cd /var/www/lmeve/config
     cp config-dist.php config.php
     sudo nano config.php 
      -configure database settings for your mysql installation
      -add random $lm_salt valueSet up API poller in cron to run every 15 minutes
      */15 * * * * apache2/bin/php -h /var/www/lmeve/bin/poller.php
     
5 install lmeve icons and graphics
    cd /Incoming
    sudo wget "http://content.eveonline.com/data/January2019Release_1.0_Icons.zip"
    sudo wget "http://content.eveonline.com/data/January2019Release_1.0_Types.zip"
    tar -xjf January2019Release_1.0_Icons.zip Icons/Items/ -C /var/www/lmeve/wwwroot/ccp_icons
    tar -xjf January2019Release_1.0_Types.zip /Types/ -C /var/www/lmeve/wwwroot/ccp_img
    
6 Configure CCP Developer application using the lmeve sso config guide :
  https://github.com/roxlukas/lmeve/wiki/Integrating-LMeve-with-EVE-SSO

7 Finalize installation : 
login to lmeve using admin / admin credentials
Wait a few minutes while lmeve parses and alters database tables
Change admin password in Settings
Create a user accout for yourself
Logout, Login with new account
Add corp ESI key in Settings -> ESI Keys


  
<h1>Credits and copyrights</h1>

* LMeve by Lukasz "Lukas Rox" Pozniak

* LMframework v3 by 2005-2014 Lukasz Pozniak

* rixxjavix.css skin by Bryan K. "Rixx Javix" Ward

<h3>Thanks!</h3>

* TheAhmosis and Razeu - it's their idea that I had the pleasure to wrap in code
* Crysis McNally - for excellent ideas and thorough testing
* Aideron Technologies - for excellent closed beta
* CCP Games - for making such a great game and providing API for us, developer kind, to tinker with
* To all supporters and donators. Thank you!

<h3>Donations are welcome!</h3>

According to CCP Developers License paragraph 4 section 4 (https://developers.eveonline.com/resource/license-agreement)
you can buy me a coffe or help fund the server.

If you'd like to support the development, feel free to do so: https://www.paypal.me/roxlukas

<h4>Top donators:</h4>
Starfire Dai, Crysis McNally

