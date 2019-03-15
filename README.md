# RealTimeMonitoringService for Moodle 3.X
Brought to you by the Development team from Yorkville Education Company

# What does RTMS do? 
- Scans every single course in your Moodle install
- Scans every module activity
- Scans every student

# CPU Useage
Typically, if you ever attempted to do the above operation on large Moodle installs, your server would melt to the ground.

With RTMS, we have gotten around this issue by building a cache system of courses to scan and digesting a reasonable amount of courses at a time.

This allows you to create very complex SQL queries that run all day, every day, and won't burn down your server!

# Cron every 1 minute
Add this line to your Cron manager (crontab -e) to see the results of the RTMS on your system and gauge performance

*/1 * * * * wget -O - https://yourwebsite.com/local/RealTimeMonitoringService/?key=YOUR_WEB_KEY&debugMessages=false > /dev/null 2>&1

Again, even though CRON runs this every 1 second, it will not re-process itself until the previous operation has been completed. This allows Moodle to safely complete very complex operations without melting down.
