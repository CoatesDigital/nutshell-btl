<?php
namespace application\plugin\btl
{
	use application\plugin\gsrc\GSRCException;

	use application\plugin\btl\BtlException;
	use application\plugin\mvcQuery\MvcQueryObject;
	use nutshell\core\plugin\PluginExtension;
	use nutshell\core\exception\NutshellException;
	use nutshell\Nutshell;
	
	/**
	 * Btl - The Batchable Transmission Layer
	 * Note that all exceptions thrown from this file include error code 1000+
	 * @throws BtlException
	 * @author Dean Rather
	 */
	class Service extends PluginExtension
	{
		
		/**
		 * If set to something, returns a JSONP response with this callback instead of a JSON callback
		 */
		private $callback = false;
		
		
		/**
		 * The namespace / folder within 'controller' where the controllers sit. eg "api"
		 */
		private $controllerNamespace = '';
		
		/**
		 * Handles the JSON Request, which may be a batch of requests.
		 * @throws BtlException
		 */
		public function handleRequests($JSON)
		{
			// Is it valid JSON?
			$data = json_decode($JSON);
			if(!$data) throw new BtlException(BtlException::INVALID_REQUEST);
			
			
			
			// Is it a batch of requests?
			if(is_array($data))
			{
				$response = array();
				foreach($data as $request)
				{
					$response[] = $this->handleRequest($request);
				}
				$response;
			}
			else
			{
				$response = $this->handleRequest($data);
			}
			
			$this->respond($response);
			exit;
		}
		
		
		private function handleRequest($request)
		{
			$code = 1;
			$success = 'true';
			$message = null;
			$errorCode = null;
			
			try {
				// check it is a conforming request
				$this->checkRequestMetaData($request);
				
				list($className, $method) = explode('.', $request->call);
				
				$this->checkRequestAPI($className, $method);
				
				if(!$this->controllerNamespace) throw new BtlException(BtlException::MUST_SET_NAMESPACE);
				
				// Include the file
				$filename = APP_HOME._DS_.'controller'._DS_.$this->controllerNamespace._DS_.ucfirst($className).'.php';
				if(!file_exists($filename)) throw new BtlException(BtlException::FILE_DOESNT_EXIST, $filename);
				require_once($filename);
				
				// Check the class exists
				$class = 'application\controller\\'.$this->controllerNamespace.'\\'.ucfirst($className);
				if(!class_exists($class)) throw new BtlException(BtlException::CLASS_DOESNT_EXIST, $class);
				
				// Create a new instance of the class
				$object = new $class($this->plugin->Mvc);
				
				if(isset($request->data) || (isset($request->data) && is_null($request->data))) // call the method on the object, pass it the 'data' from the request
				{	
					// Check the method exists
					if(!method_exists($class, $method))	throw new BtlException(BtlException::CLASS_METHOD_DOESNT_EXIST, $class, $method);
					
					$result = $object->$method($request->data);
				}
				else if(isset($request->query)) // call the 'query' method on the object
				{
					if($method != 'get') throw new BtlException(BtlException::CANT_QUERY_THAT_TYPE, $class, $method, $query);
					$result = $object->$method($request->query, array('query'=>true));
				}
				else
				{
					throw new BtlException(BtlException::REQUEST_NEEDS_DATA);
				}
			}
			catch(NutshellException $exception)
			{
				$exception->log();
				$code = $exception->getCode();
				$success = 'false';
				$message = 'request failed';
				if(NS_ENV=='dev') $message = $exception->getDescription();
				$result = null;
			}
			
			$response = array
			(
				'sequence'	=> isset($request->sequence)?$request->sequence:0,
				'timestamp'	=> time(),
				'call'		=> $request->call,
				'code'		=> $code,
				'success'	=> $success,
				'message'	=> $message,
				'data'		=> $result
			);
			
			return $response;
		}
		
		/**
		 * Packages Sends the responses downstream
		 */
		private function respond($data)
		{
			echo '<pre>'.print_r($data,1).'</pre>'; exit; // for debug purposes
			$type='json';
			if ($this->callback)
			{
				$data=json_encode($data);
				$data=$this->callback.'('.$data.');';
				$type.='p';
			}
			$this->plugin	->Responder($type)
							->setData($data)
							->send();
			exit;
		}
		
		/**
		 * If your controllers who extend Btl are in \application\controllers\api, then you must pass in 'api'
		 */
		public function setControllerNamespace($namespace=null)
		{
			$this->controllerNamespace=$namespace;
		}
		
		/**
		 * Pass me a request object, And I will throw an error if it does not have conforming meta data
		 * @param $request request object
		 * @throws BtlException
		 */
		private function checkRequestMetaData($request)
		{
			// if(!isset($request->sequence)) throw new BtlException(BtlException::REQUEST_NEEDS_SEQUENCE);
			// if(!is_int($request->sequence)) throw new BtlException(BtlException::REQUEST_NEEDS_SEQUENCE);
			// if($request->sequence < 0 ) throw new BtlException(BtlException::REQUEST_NEEDS_SEQUENCE);
			
			if(substr_count($request->call, '.') !== 1) throw new BtlException(BtlException::INVALID_CALL_FORMAT, $request);
			
			if(!isset($request->timestamp)) throw new BtlException(BtlException::REQUEST_NEEDS_TIMESTAMP);
			if(!is_numeric($request->timestamp)) throw new BtlException(BtlException::REQUEST_NEEDS_TIMESTAMP);
			if($request->timestamp < 0 ) throw new BtlException(BtlException::REQUEST_NEEDS_TIMESTAMP);
			
			if(!isset($request->call)) throw new BtlException(BtlException::REQUEST_NEEDS_CALL);
			if(!$request->call) throw new BtlException(BtlException::REQUEST_NEEDS_CALL);
			
			if(!isset($request->data) && !isset($request->query)) throw new BtlException(BtlException::REQUEST_NEEDS_DATA);
			
		}
		
		/**
		 * Checks this plugin's config
		 * The user is only allowed to perform operations defined in that configuration.
		 * @throws BtlException
		 */
		private function checkRequestAPI($className, $method)
		{
			$config = Nutshell::getInstance()->config;
			$api = $config->plugin->Btl;
			if(!$api) throw new BtlException(BtlException::API_CONFIG_NOT_CONFIGURED);
			if(!$api->$className) throw new BtlException(BtlException::API_CLASS_NOT_ALLOWED, $className, $api);
			if(!in_array($method, $api->$className)) throw new BtlException(BtlException::API_CLASS_METHOD_NOT_ALLOWED, $className, $method, $api);
		}

	}
}
