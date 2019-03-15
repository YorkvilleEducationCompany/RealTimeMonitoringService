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