Purpose
-------

We want to unify 3rd party reports inside Tagcade. Some 3rd parties do not have an API, for these parties will use selenium to login and extract the data.
The data will be sent to an e-mail address. That e-mail address will be monitored by another program to automatically import attachments.

To run it, download selenium standalone and start the server and pass in the path to chrome driver:

```
java -jar /opt/selenium/selenium-server-standalone-2.48.2.jar -Dwebdriver.chrome.driver=/usr/lib/chromium-browser/chromedriver
```

Alternatively, you can start chrome driver directly without selenium and use `http://localhost:9515` as the server url instead of `http://localhost:4444/wd/hub`

Proxy
-----

Creating a proxy is easy, you can use SSH to quickly create a SOCKS proxy

```
ssh -fN -D localhost:8888 myusername@myserver.com
```

Some commands allow you to proxy requests

Config output dir for fetched files
-----------------------------------

Edit output dir config in app/config/parameters.yml, make sure the dir contains "fetcher" at the end:

```
...
    tagcade.default_data_path:      ./data/fetcher
...
```


Commands to run fetcher
-----------------------

**Run activator to execute fetchers for all scheduled data source integrations**  
```
php app/console tc:unified-report-fetcher:activator:run -vvv
```

**Run single fetcher for a specific data source**  

```
php app/console tc:unified-report-fetcher:activator:datasource:run <data source id> [-p "<params separated by comma, format is name:type:value>"] [-f] [-u] -vvv
```
where: 
- [-f]: force run without check scheduled
- [-u]: also update scheduled time for data source integration

E.g
```
php app/console tc:unified-report-fetcher:activator:datasource:run 36 -p "username:plainText:admin,password:plainText:123456,startDate:date:2017-04-07,endDate:date:2017-04-09,reportType:option:Demand,dailyBreakdown:bool:true" -f -vvv
```

See help for more command options

Resources
---------

https://code.google.com/p/selenium/wiki/JsonWireProtocol
http://www.slisenko.net/2014/06/22/best-practices-in-test-automation-using-selenium-webdriver/
https://mestachs.wordpress.com/2012/08/13/selenium-best-practices/
https://sites.google.com/a/chromium.org/chromedriver/capabilities
http://selenium-tutorial.blogspot.ca/2014/03/webdriver-wait-for-page-load.html
http://stackoverflow.com/questions/25695299/chromedriver-on-ubuntu-14-04-error-while-loading-shared-libraries-libui-base
https://stackoverflow.com/questions/16090869/how-to-pretty-print-xml-from-the-command-line
