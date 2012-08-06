<?php
namespace application\plugin\btl
{
	use application\plugin\btl\BtlException;
	
	class BtlRequestObject
	{
		public function __construct($request=null)
		{
			if(is_object($request))
			{
				$this->checkRequestMetaData($request);
				
				// Inject Myself
				foreach($request as $key => $val)
				{
					$this->$key = $val;
				}
			}
		}
		
		/**
		 * A String representing the class and method to call.
		 * Eg: user.set
		 */
		private $call = null;
		
		public function getCall()
		{
		    return $this->call;
		}
		
		public function setCall($call)
		{
		    $this->call = $call;
		    return $this;
		}
		
		
		
		private $timestamp = null;
		
		public function getTimestamp()
		{
		    return $this->timestamp;
		}
		
		public function setTimestamp($timestamp)
		{
		    $this->timestamp = $timestamp;
		    return $this;
		}
		
		
		
		private $data = null;
		
		public function getData()
		{
		    return $this->data;
		}
		
		public function setData($data)
		{
		    $this->data = $data;
		    return $this;
		}
		
		
		private $query = null;
		
		public function getQuery()
		{
		    return $this->query;
		}
		
		public function setQuery($query)
		{
		    $this->query = $query;
		    return $this;
		}
		
		
		private $sequence = 0;
		
		public function getSequence()
		{
		    return $this->sequence;
		}
		
		public function setSequence($sequence)
		{
		    $this->sequence = $sequence;
		    return $this;
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
			
			if(!isset($request->call)) throw new BtlException(BtlException::REQUEST_NEEDS_CALL);
			if(!$request->call) throw new BtlException(BtlException::REQUEST_NEEDS_CALL);
			if(substr_count($request->call, '.') !== 1) throw new BtlException(BtlException::INVALID_CALL_FORMAT, $request);
			
			if(!isset($request->timestamp)) throw new BtlException(BtlException::REQUEST_NEEDS_TIMESTAMP);
			if(!is_numeric($request->timestamp)) throw new BtlException(BtlException::REQUEST_NEEDS_TIMESTAMP);
			if($request->timestamp < 0 ) throw new BtlException(BtlException::REQUEST_NEEDS_TIMESTAMP);
			
		}
	}
}
