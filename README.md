# RealTimeMonitoringService for Moodle 3.X
Brought to you by the Moodle Team from Yorkville Education Company

Coded by Andrew Normore (ANormore@YorkvilleU.ca)


# What does RTMS do? 
- Scans every single course in your Moodle install
- Scans every module activity
- Scans every student

# CPU Useage
Typically, if you ever attempted to do the above operation on large Moodle installs, your server would melt to the ground.

With RTMS, we have gotten around this issue by building a cache system of courses to scan and digesting a reasonable amount of courses at a time.

This allows you to create very complex SQL queries that run all day, every day, and won't burn down your server!

# Cron every 1 minute (psudo real time)
Add this line to your Cron manager (crontab -e) to see the results of the RTMS on your system and gauge performance

*/1 * * * * wget -O - https://yourwebsite.com/local/RealTimeMonitoringService/?key=YOUR_WEB_KEY&debugMessages=false > /dev/null 2>&1

Again, even though CRON runs this every 1 second, it will not re-process itself until the previous operation has been completed. This allows Moodle to safely complete very complex operations without melting down.

Depending on your CPU, you can configure the RTMS to process more or less courses at a time. 

# Real examples of what RTMS can do

The first module ever built for RTMS was "Autograde overdue quizzes to a 0", some instructors would miss assigning the zero as they were accustomed to using the grade tool. 

So how do we scan for missed quizzes? Every single course, every single quiz, every single student. 

If you look inside of the /plugins/disabled/overdueAssignmentsToZero.php you may examine how simple this process is.

# Creating custom plugins for RTMS

Very easy! Just drop a PHP script in to /plugins/enabled/MY_SCRIPT.php and write code. It already assumes $USER, $DB, and the current $course.

So now you can get a course id very simply, $course->id -- and process any sort of logic and condition you wish.

