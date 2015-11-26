# oauth-login-page

Login-page written in PHP, suitable for authentication via OAuth 2.0 server. Standalone, no frameworks.

# Features
- Communicates with OAuth 2.0 servers for authentication
- Supports being invoked to authenticate user from another site, will redirect back to it after authentication.
- Grabs tokens from OAuth 2.0 server and gives to the authenticated user's browser
- Only recognized sites will be able to have users authenticate and redirected back.
- Uses sophisticated nonce strings to counteract attempts to steal authentication tokens.
- Uses sessions to generate nonce strings, and to do security checks - sessions are stored in a DB.


# Installing 

## Configuration file


## Configure PHP

It is recommended to use database connection pooling, as the code does a lot of fast-run queries, and each request takes a short while.

## Creating DB-table

CREATE TABLE `lp_sessions` (
  `session_id` varchar(256) CHARACTER SET latin1 NOT NULL DEFAULT '',
  `session_expires` int(10) unsigned NOT NULL DEFAULT '0',
  `session_data` text,
  PRIMARY KEY (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8


