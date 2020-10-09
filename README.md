Log parser Task
-
### The instruction to make it work
Install the dependencies with composer
```bash
composer install
```
Then move to the folder and run
```bash
php bin/console app:parselog
```
This will parse the log file `storage/gobankingrates.com.access.log` 
and output a CSV `storage/file.csv` with the parsed info

About
-
The task intended to fulfill this points
- Read an access log file
- Resolve Country and State from IP address (IE MaxMind GeoLite2 Free)
- Translate useragent to device type (Mobile, Desktop, Tablet) and Browser
(Safari, Chrome, etc)
- Combine new Geo & Device fields with existing fields on access log file and
output/export a CSV

The access [log file](https://cti-developer-dropbox.s3.amazonaws.com/gobankingrates.com.access.log) 
is already in the repo

The repo also have the `GeoIP2-City` database file from 
[IE MaxMind GeoLite2 Free](https://www.maxmind.com/en/geoip2-services-and-databases)

Made using `Symfony framework skeleton` command and this packages
- [GeoIP2 PHP API](https://github.com/maxmind/GeoIP2-php)
- [Symfony Console](https://symfony.com/doc/current/components/console.html)
