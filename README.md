Oyster-Push-Notifications
=========================

Oyster Push Notifications, is a server-side script written in PHP that saves your travel history to a MySql database and sends a push notification using Boxcar service when a new journey is added.

Instructions:

* Create a database and import dbSchema.sql
* Rename config.sample.php to config.php
* Add your boxcard API data, Oystercard login and MySql information to config.php
* Add index.php as a cron job in your server for example every 30 minutes.
* Receive notifications in your iOS device using Boxcar app.