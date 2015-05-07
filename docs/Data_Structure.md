-------
Request
-------
{
	"sequence":		<INTEGER>,			// A Unique Sequential ID of this request
	"timestamp":	<INTEGER>,			// Timestamp provided by the client
	"call":			<STRING>, 			// A representation of which data and which operation is to be called. eg "user.get"
	"data":			<OBJECT>|<ARRAY>, 	// An object or array of data representing a table
	"query":		<OBJECT>  			// An object representing a query 
}
--------
Response
--------
{
	"code":		<INTEGER>,
	"success":	<BOOLEAN>,
	"message":	<NULL>/<STRING>,
	"data":		<MIXED>
}

------
data
------
{
	"_type":	   <STRING>,		// This object represents this type of data
	"<PARAM_NAME>":<PARAM_VALUE>,	// These are they keys / values of the data
	"<PARAM_NAME>":<PARAM_VALUE>,
	"<PARAM_NAME>":<PARAM_VALUE>,
	"<PARAM_NAME>":<PARAM_VALUE>
}


-----
Query
-----
{
	"_<META_NAME>":<META_VALUE>,	// These are meta data about the query, eg: first, limit, sort
	"<PARAM_NAME>":<PARAM_VALUE>,	// These are keys / values which are search criteria
	"<PARAM_NAME>":<PARAM_VALUE>,
	"<PARAM_NAME>":<PARAM_VALUE>,
	"<PARAM_NAME>":<PARAM_VALUE>
}


--------------
Batch Requests
--------------
[
	<REQUEST>,
	<REQUEST>,
	<REQUEST>,
	<REQUEST>
]


--------
EXAMPLES
--------


GET SINGLE USER:
----------------
{
	"sequence":		2,
	"timestamp":	43645675436,
	"call":			"user.get",
	"data":
	{
		"_type":	"user",
		"id":		"5"
	}
}

RESPONSE:
{
	"seq":			2,
	"timestamp":	43645675436,
	"code":		1,					//Success
	"success":	true,
	"message":	null,
	"data":
	{
		"_type":	"user",
		"id":			5,
		"firstname":	"John",
		"secondname":	"Doe"
	}
}

GET MANY INDIVIDUAL USERS:
---------------
{
	"sequence":		3,
	"timestamp":	43645675437,
	"call":			"user.get",
	"data":
	[
		{
			"_type":	"user",
			"id":		"5"
		},
		{
			"_type":	"user",
			"id":		"4"
		}
	]
}


GET MANY USERS VIA QUERY:
---------------
{
	"sequence":		4,
	"timestamp":	43645675437,
	"call":			"user.get",
	"query":
	{
		"_start":		20,
		"_limit":		20,
		"firstname":	"%ja%"
	}
}


SET SINGLE USER:
----------------
{
	"sequence":		5,
	"timestamp":	43645675437,
	"call":			"user.set",
	"data":
	{
		"_type":		"user",
		"id":			5,			//Update
		"firstname":	"John",
		"secondname":	"Doe"
	}
}


BULK SET 2 USERS:
-----------------
{
	"sequence":		6,
	"timestamp":	43645675437,
	"call":			"user.set",
	"data":
	[
		{
			"_type":		"user",
			"id":			5,		//Update
			"firstname":	"John"
		},
		{
			"_type":		"user",
			"id":			null,	//Create
			"firstname":	"Jane",
			"secondname":	"Doe"
		}
	]
}


BATCH SET 2 USERS AND GET SECTION:
-----------------------------------
[
	{
		"sequence":		7,
		"timestamp":	43645675437,
		"call":			"user.set",
		"data":
		[
			{
				"_type":		"user",
				"id":			5,		//Update
				"firstname":	"John",
				"secondname":	"Doe"
			},
			{
				"_type":		"user",
				"id":			null,	//Create
				"firstname":	"Jane",
				"secondname":	"Doe"
			}
		]
	},
	{
		"sequence":		8,
		"timestamp":	43645675437,
		"call":			"section.get",
		"data":
		{
			"_type":	"section",
			"id":		"2"
		}
	}
]


REMOVE SINGLE USER:
-------------------
{
	"sequence":		2,
	"timestamp":	43645675437,
	"call":			"user.remove",
	"data":
	{
		"_type":	"user",
		"id":		"5"
	}
}

RESPONSE:
{
	"seq":			2,
	"timestamp":	43645675436,
	"response":
	{
		"code":		1,					//Success
		"success":	true,
		"message":	null,
		"data":
		{
			"_type":	"user",
			"id":		"5"
			"_affected": "1"
		}
	}
}


REMOVE MANY USERS:
------------------
{
	"sequence":		2,
	"timestamp":	43645675437,
	"call":			"user.remove",
	"query":
	{
		"firstname":	"%foo%"		//Remove all users like foo.
	}
}


REMOVE SEVERAL INDIVIDUAL USERS:
--------------------------------
{
	"sequence":		2,
	"timestamp":	43645675437,
	"call":			"user.remove",
	"data":
	[
		{
			"_type":		"user",
			"id":			5
		},
		{
			"_type":		"user",
			"id":			8
		}
	]
}


OTHER ACTION:
-------------
{
	"sequence":		2,
	"timestamp":	43645675437,
	"call":			"account.authenticate",
	"data":
	{
		"email":		"foo@bar.baz",
		"password":		"foobarbaz"
	}
}





CHECK WHETHER A USER HAS CHANGED:
---------------------------------
{
	"sequence":		7,
	"timestamp":	44645675437,
	"call":			"user.check",
	"data":
	{
		"_type":		"user",
		"id":			5,
		"firstname":	"John",
		"secondname":	"Doe"
	}
}

RESPONSE:
---------
{
	"seq":			7,
	"timestamp":	44645675439,
	"code":		1,
	"success":	true,
	"message":	null,
	"data":
	{
		"_type":	"user",
		"id":		5,
		"changed":	false
	}
}


CHECK WHETHER SEVERAL USERS HAVE CHANGED:
-----------------------------------------
{
	"sequence":		7,
	"timestamp":	44645675437,
	"call":			"user.check",
	"data":
	[
		{
			"_type":		"user",
			"id":			5,
			"firstname":	"John",
			"secondname":	"Doe"
		},
		{
			"_type":		"user",
			"id":			6,
			"firstname":	"John",
			"secondname":	"Dover"
		}
	]
}


RESPONSE:
---------
{
	"seq":			7,
	"timestamp":	44645675439,
	"code":		1,
	"success":	true,
	"message":	null,
	"data":
	[
		{
			"_type":	"user",
			"id":		5,
			"changed":	false
		},
		{
			"_type":	"user",
			"id":		6,
			"changed":	true
		}
	]
}













SELECT ALL, REMOVE ALL JANES, INSERT A JANE, SELECT ALL
-------------
[
	{
		"sequence":		1,
		"timestamp":	43645675437,
		"call":			"user.get",
		"query":		{}
	},
	{
		"sequence":		2,
		"timestamp":	43645675437,
		"call":			"user.remove",
		"query":
		{
			"firstname":	"Jane"
		}
	},
	{
		"sequence":		3,
		"timestamp":	43645675437,
		"call":			"user.set",
		"data":
		{
			"_type":		"user",
			"firstname":	"Jane",
			"secondname":	"Doe"
		}
	},
	{
		"sequence":		4,
		"timestamp":	43645675437,
		"call":			"user.get",
		"query":		{}
	}
]
