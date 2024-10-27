1) Create a twitter account

2) Create an app with your twitter account at
https://developer.twitter.com/en/portal/dashboard

3) Check App Settings

Go to the application settings via the sidebar on the dashboard.

Click on the settings tab and then the "User Settings" button.
* Select Read permissions.
* Select "Request email from users" on if desired.  The Twitter APIv2 does not currently
	allow email to be requested but when it does vBulletin will be upgraded to pull the 
	email from Twitter automatically on registration.  However this is optional and the 
	user will be notified that the email is requested when authorizing the app.
* Select "Web App"
* Set the callback url to {forumroot}/twitterlogin.auth/callback
* Set website url to your forum or website home page.
* Enter your privacy and terms of service urls.  The default urls for these pages on the forum
	are {forumroot}/terms-of-service and {forumroot}/privacy but you will need to configure these
	pages with your terms before using them.

4) App Key & Secret
After saving the settings, go to the "Keys and Access Tokens" tab.
Save the "Client ID" and "Client Secret" values located under the Oauth2 heading somewhere secure.
DO NOT SHARE THESE VALUES.
This values will need to be saved in the vBulletin settingsunder the "Third Party Login Options" 
setting group.

5) vBulletin Settings
In Admin Control Panel (Admin CP) go to "Products & Hooks" > "Manage Products". If the
product & hook system is disabled, there will be a link at the top of this page to enable it.
You should see "Third Party Login - Twitter" as one of the installed products on this page.
Click on the "Enable Sign-in with Twitter" under "Related Options".
Set "Enable Sign-in with Twitter" to "Yes", and enter the "Consumer Key (API Key)" and
"Consumer Secret (API Secret)" values from step 4 to "Twitter App Consumer Key (API Key)" and
"Twitter App Consumer Secret (API Secret)", respectively.
(Optional) Set "Enable Registration with Twitter" to allow new users to connect their twitter
accounts during registration.
Save the settings.

6) Connect/disconnect Twitter account
Your forum's users should now be able to connect (or disconnect) their twitter accounts.
Existing users can do so via the "Third-party Login Providers" section of their user settings
page after logging into the forum ({forumurl}/settings/account) and clicking on the "Connect
to Twitter" button (or "Disconnect from Twitter" button if they are already connected) in
that section.
If "Enable Sign-in with Twitter" was enabled, new users can connect their twitter accounts
during registration via the "Connect to Twitter" button at the top of the registration form.
