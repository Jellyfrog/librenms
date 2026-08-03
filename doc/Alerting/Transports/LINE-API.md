## LINE Messaging API

[LINE Messaging API Docs](https://developers.line.biz/en/docs/messaging-api/overview/)

These are the steps to set up a LINE bot and use it in LibreNMS.

1. Register with your real LINE account in the [developer portal](https://developers.line.biz/).

1. Add a new channel, select `Messaging API` and fill in the forms. Note: you cannot edit `Channel name` later.

1. Go to the "Messaging API" tab of your channel. It shows some important values.

	- `Bot basic ID` and `QR code` are the ID and QR code of your LINE bot.
	- `Channel access token (long-lived)`: you use it in LibreNMS. Keep it safe.

1. With your real Line account, add your LINE bot as a friend.

1. The Recipient ID can be `groupID`, `userID` or `roomID`. LibreNMS uses it to send a message to a group or a user. Use the NodeJS program below and `ngrok` as a temporary https webhook to listen for it.

	[LINE-bot-RecipientFetcher](https://github.com/j796160836/LINE-bot-RecipientFetcher)

1. Run the program, and use `ngrok` to make the port public

	```
	$ node index.js
	$ ngrok http 3000
	```

1. Go to the "Messaging API" tab of your channel. Set the Webhook URL to `https://<your ngrok domain>/webhook`


1. If you want the LINE bot to send messages to you, use your real account to send a message to your LINE bot. The program shows the `userID` in the console.

	example value:  
	
	```
	{"type":"user","userId":"U527xxxxxxxxxxxxxxxxxxxxxxxxxc0ee"}
	```
	
1. If you want the LINE bot to send messages to a group, do these steps.

	- Add your LINE bot into the group
	- Use your real account to send a message to the group
	
	The program shows the `groupID` in the console. This is the Recipient ID. Keep it safe.

	example value:

	```
	{"type":"group","groupId":"Ce51xxxxxxxxxxxxxxxxxxxxxxxxxx6ef","userId":"U527xxxxxxxxxxxxxxxxxxxxxxxxxc0ee"} ```
	```

**Example:**

| Config | Example |
| ------ | ------- |
| Access token | fhJ9vH2fsxxxxxxxxxxxxxxxxxxxxlFU= |
| Recipient (groupID, userID or roomID) | Ce51xxxxxxxxxxxxxxxxxxxxxxxxxx6ef |