Log parser Task
-
### The instruction to make it work
To start de project 
```bash
cd docker
docker-compose up
```
After docker has finished 
```bash
docker-compose exec php-fpm bin/console app:parselog
```
This will parse the log file `./storage/gobankingrates.com.access.log` 
and output a CSV `./storage/file.csv` with the parsed info

About
-
The task intended to fulfill this points
- Read an access log file
- Resolve Country and State from IP address (IE MaxMind GeoLite2 Free)
- Translate useragent to device type (Mobile, Desktop, Tablet) and Browser
(Safari, Chrome, etc)
- Combine new Geo & Device fields with existing fields on access log file and
output/export a CSV

Bonus
- Docker


The access [log file](https://cti-developer-dropbox.s3.amazonaws.com/gobankingrates.com.access.log) 
is already in the repo

The repo also have the `GeoIP2-City` database file from 
[IE MaxMind GeoLite2 Free](https://www.maxmind.com/en/geoip2-services-and-databases)

Made using `Symfony framework skeleton` command and this packages
- [GeoIP2 PHP API](https://github.com/maxmind/GeoIP2-php)
- [Symfony Console](https://symfony.com/doc/current/components/console.html)

For Docker, I used this post as reference https://dev.to/martinpham/symfony-5-development-with-docker-4hj8,
remove the DB dependencies but let the nginx container, in my test
the docker parse is more slow, could be a problem in how volumes mounted.
