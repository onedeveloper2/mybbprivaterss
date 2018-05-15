# mybbprivaterss
Private RSS notifications system for mybb.


## How it works
Every user is given a static URL address for his RSS feed. This address includes a secret key, which is generated at the very first login since the plugin has been installed.

The key is available for the user in the "Your Account Summary" section of the user cp area.

When the user accesses his RSS feed, the plugin assotiates the key with the user ID and generates an RSS feed from his forum and thread subscriptions.

## Installation

 * checkout the repository
 * move the files to their appropriate location as the structure suggests
 * the 'templates' directory contains some changes to the original usercp template, you have to change the template in the administration area of mybb
 * navigate to plugin settings in the administration are and hit 'activate'

 ! Your database user must have create table rights !
