# course-reserves-blackboard-connect

The Library Online Course Reserves system at UBC is currently accessed through BlackBoard Connect.
The code provided in this repo is the code used by UBC to display the system within Connect.
However, as each LMS is different, and widely customised, this code may serve best as a starter regarding what can be displayed.

Part of the system that can easily be transported:
- controllers
- models
- views


Parts of the system that may require institutional re-writes:
- This system code is served through a plugin served in Connect. This is a BlackBoard plugin, that then loads the system html. Based on your LMS, you institution can guide you on how best to provide an external app within your LMS. You will want to take into consideration that the LMS Plugin that serves this code will be able to access the Access/User Context in the LMS, and use that to authenticate the user within this system, so that when the plugin loads, the user is not faced with a login screen.
- Please review the distributable config file, there are some BlackBoard specific URLS, that are the URLs to this plugin. Your LMS will need to provide a way to link to this plugin, so that when students click on a link (e.g. on a teacher's website), that, after institutional login, the student can be directed straight to the relevant course within this app, that holds their readings etc.

Other Notes:
- the resource folder contains multiple examples of a script called locrjs.js. You can review this to see how we inject an iFrame into the BlackBoard Plugin, and then replace that iFrame src with our system. This allowed us to re-use the existing PHP code, however, you could approach this as a total plugin re-write, using the LMS relevant language.
