In regards to the JavaScript Framework Proxy and Nutshell API

Instead of CRUD we have:
- Get
- Set
- Remove
- Check


Batching
--------
A batch is several requests batched together into one request. These requests might be bulk, regular, or combination thereof
Batch requests are sent as HTTP_RAW_DATA


Requests
--------

Requests are made to:
website.com/api

Each request (regular or bulk) contains:
a "sequence" parameter, This is an incremental reference unique for that request
a "timestamp" parameter, which is a millisecond timestamp provided by the client
a "call" parameter which may represent the controller / action to use, or some other key we look for. eg "user.add"

And either:
a "data" parameter which is a data object or an array of data objects
or a "query" paramater which is an object representing a query

"data" objects are typically sets of key:value pairs representing data names and values, for example column names and data for a table.
"data" objects are checked for a "_type" parameter, and this is used to determine which controller / table / etc. must be used to handle the object.
