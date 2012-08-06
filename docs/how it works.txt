CURRENT BEHAVIOR:

Client
Javascript creates a JSON object conforming to the BTL spec and sends it up.

BTL | Service->handleRequest()
Creates an instance of BtlRequestObject from the json Query
Calls the class / method defined in 'call'
Passes the request object
formats the result as a json response, returns the result

GSRC | eg. GsrcController->getIndividualRequest()
Accepts 1 parameter, the "request" object, looks for "data" and "query" parts
creates an MvcQueryObject representing the query
passes it off to MVCQuery's 'query' function, returns the result

MVCQuery | MvcQuery->query()
Reads the MvcQueryObject, and translates it into parameters for the QueryHandler (eg handler/MySQL.php)
some additional properties which aren't supported parameters remain in the QueryObject, which is passed into the function
calls the appropriate query function on the handler (insert, read, update, delete)
returns the result

MVCQuery Handler | eg. MVCQuery/handler/SQLite->read()
Accepts some parameters, but other options are passed in the mvcquery object
generates the query, queries, returns
