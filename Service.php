<?php
namespace application\plugin\btl
{
	use application\plugin\gsrc\GsrcException;

	use application\plugin\btl\BtlException;
	use application\plugin\btl\BtlRequestObject;
	use application\plugin\mvcQuery\MvcQueryObject;
	use nutshell\core\plugin\PluginExtension;
	use nutshell\core\exception\NutshellException;
	use nutshell\Nutshell;
	
	/**
	 * BTL - The Batchable Transmission Layer
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
			$code		= 1;
			$sequence	= 0;
			$success	= true;
			$message	= null;
			$errorCode	= null;
			$call		= '';
			
			try
			{
				// Check we've got a conforming request
				$request = new BtlRequestObject($request);
				$call = $request->getCall();
				if(substr_count($call, '.') !== 1) throw new BtlException(BtlException::INVALID_CALL_FORMAT, $request);
				list($className, $method) = explode('.', $call);
				$this->checkRequestAPI($className, $method);
				if(!$this->controllerNamespace) throw new BtlException(BtlException::MUST_SET_NAMESPACE);
				$sequence = $request->getSequence();
				
				// Include the file
				$filename = APP_HOME._DS_.'controller'._DS_.$this->controllerNamespace._DS_.ucfirst($className).'.php';
				if(!file_exists($filename)) throw new BtlException(BtlException::FILE_DOESNT_EXIST, $filename);
				require_once($filename);
				
				// Check the class exists
				$class = 'application\controller\\'.$this->controllerNamespace.'\\'.ucfirst($className);
				if(!class_exists($class)) throw new BtlException(BtlException::CLASS_DOESNT_EXIST, $class);
				
				// Create a new instance of the class
				$object = new $class($this->plugin->Mvc);
				
				// Check the method exists
				if(!method_exists($class, $method))	throw new BtlException(BtlException::CLASS_METHOD_DOESNT_EXIST, $class, $method);
				
				// Pass the request through to the method on the class
				$result = $object->$method($request);
			}
			catch(NutshellException $exception)
			{
				$exception->log();
				$code		= $exception->getCode();
				$success	= false;
				$message	= 'request failed';
				$result		= null;
				
				$nutshell = Nutshell::getInstance();
				if($nutshell->config->application->mode=='development')
				{
					// header('HTTP/1.1 500 Application Error');
					$message = $exception->getDescription('array');
				}
			}
			
			$response = array
			(
				'sequence'	=> $sequence,
				'timestamp'	=> time(),
				'call'		=> $call,
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
		 * If your controllers who extend BTL are in \application\controllers\api, then you must pass in 'api'
		 */
		public function setControllerNamespace($namespace=null)
		{
			$this->controllerNamespace=$namespace;
		}
		
		/**
		 * Checks this plugin's config
		 * The user is only allowed to perform operations defined in that configuration.
		 * @throws BtlException
		 */
		private function checkRequestAPI($className, $method)
		{
			$className = $className;
			$method = $method;
			$api = $this->getApi();
			if(!$api) throw new BtlException(BtlException::API_CONFIG_NOT_CONFIGURED);
			if(!isset($api[$className])) throw new BtlException(BtlException::API_CLASS_NOT_ALLOWED, $className, $api);
			if(!in_array($method, $api[$className])) throw new BtlException(BtlException::API_CLASS_METHOD_NOT_ALLOWED, $className, $method, $api);
		}
		
		public function getApi()
		{
			$config = Nutshell::getInstance()->config;
			$api = $config->plugin->Btl->toArray();
			return $api;
		}

	}
}
