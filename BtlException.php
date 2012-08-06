<?php
namespace application\plugin\btl
{
	use nutshell\core\exception\NutshellException;

	/**
	 * @author Dean Rather
	 */
	class BtlException extends NutshellException
	{
		/** The request must be valid JSON */
		const INVALID_REQUEST			= 1;
		
		/** See btl\Service\setControllerNamespace() */
		const MUST_SET_NAMESPACE		= 2;
		
		const FILE_DOESNT_EXIST			= 3;
		const CLASS_DOESNT_EXIST		= 4;
		const CLASS_METHOD_DOESNT_EXIST	= 5;
		
		/** Query must be one of: get, set, remove, check */
		const INVALID_QUERY_TYPE		= 6;
		
		/** The request must contain a 'timestamp' which must be a positive integer */
		const REQUEST_NEEDS_TIMESTAMP	= 9;
		
		/** The request must contain a 'call' */
		const REQUEST_NEEDS_CALL		= 10;
		
		/** Your application's config.json must have a "Btl" section which defined which classes/methods are accessable */
		const API_CONFIG_NOT_CONFIGURED	= 11;
		
		/** The class must be defined in api.json */
		const API_CLASS_NOT_ALLOWED		= 13;
		
		/** The class method must be defined in api.json */
		const API_CLASS_METHOD_NOT_ALLOWED = 14;
		
		/** This feature doesn't exist yet */
		const TODO = 15;

		/** The "Call" part of the BTL query must contain exactly 1 dot. */
		const INVALID_CALL_FORMAT = 16;
		
		/** The "query" block is only available for "get" methods" **/
		const CANT_QUERY_THAT_TYPE = 17;
		
	}
}
?>