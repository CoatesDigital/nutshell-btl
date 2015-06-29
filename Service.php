<?php
namespace application\plugin\btl
{

	use application\plugin\btl\BtlException;
	use application\plugin\btl\BtlRequestObject;
	use application\plugin\gsrc\GsrcException;
	use application\plugin\mvcQuery\MvcQueryObject;
	use nutshell\core\exception\NutshellException;
	use nutshell\core\plugin\PluginExtension;
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
			
			$this->log(">", $data);
			
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
			
			$this->log("<", $response);
			
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
				$result		= array();
				
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
		
		public function request($URL, $command, $data, $query)
		{
			$request = array();
			$request['timestamp']	= 1;
			$request['data']		= $data;
			$request['query']		= $query;
			$request['call']		= $command;
			$request = json_encode($request);
			
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $URL);
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $request);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($curl);
			curl_close($curl);
			if(!$result) throw new BtlException(BtlException::REQUEST_FAILED, $curl);
			
			$result = json_decode($result);
			if(!$result) throw new BtlException(BtlException::REQUEST_FAILED, $curl);
			if(!$result->success) throw new BtlException(BtlException::REQUEST_FAILED, $result, $curl);
			$result = $result->data;
			
			return $result;
		}
		
		private function log($message, $objects)
		{
			// in php, both arrays and objects can have key->val
			// in json, arrays cannot have key->val
			// this'll change the interior "arrays" from the "response" which appear in json as objects, into objects
			$objects = json_encode($objects);
			$objects = json_decode($objects);
			
			// prevent logging of passwords
			// if (property_exists($objects, "data") && property_exists($objects->data, "password")) {
			if (isset($objects->data) && isset($objects->data->password)) {
				$objects->data->password = 'privacy blanket';
			}
			
			if(is_array($objects))
			{
				$tidyObjects = array();
				foreach($objects as $object)
				{
					$tidyObjects[] = $this->tidyObject($object);
				}
			}
			else
			{
				$tidyObjects = $this->tidyObject($objects);
			}
			
			$output = json_encode($tidyObjects);
			
			// record the user's last activity
			$userName = "Unknown";
			if(isset($_SERVER['REMOTE_ADDR'])) $userName = $_SERVER['REMOTE_ADDR'];
			if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])) $userName = $_SERVER['HTTP_X_FORWARDED_FOR'];
			if($currentUser = $this->plugin->Auth->getUserID()) {
				
				$model = $this->plugin->MvcQuery->getModel('User');
				$results = $model->read(array('id'=>$currentUser));
				if(sizeof($results))
				{
					$user = $results[0];
					$userName = $user['email'];
				}
			}
			\application\helper\DebugHelper::logToFile('api.log', "$userName $message $output");
		}
		
		private function tidyObject($object)
		{
			$object = (array)$object;
			$tidyObject = array();
			$properties = array
			(
				'call',
				'query',
				'data',
				'success',
				'sequence',
				'timestamp',
				'code',
				'message'
			);
			
			foreach($properties as $property)
			{
				if(array_key_exists($property, $object))
				{
					$tidyObject[$property] = $object[$property];
				}
			}
			if(sizeof($tidyObject) != sizeof($object)) throw new BtlException("Failed to tidy object!", $object, $tidyObject);
			return $tidyObject;
		}

	}
}
