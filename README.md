# LdapPlugin

Keep your user databases updated using this plugin. If you have two user databases, one for Wordpress and your own, you can keep them updated without communcating with LDAP.

This plugin connects Wordpress users with another user database without having to communicate directly to LDAP. Instead, it calls a serverless endpoints. 

## Prerequisites
Wordpress version 4.9.6 and higher

## Installing
This plugin has 3 files.

* wikitude-user-api.php: the frontend userface in wordpress when you log as an admin
* UserApi.php: connects to our user api for user management
* wikitude-user-api-addon.php: the main functionality to keep the two databases updated.

## Authors

Eva Triantafillopoulou
