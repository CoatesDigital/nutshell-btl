<?php
namespace application\plugin\btl
{

	use nutshell\core\plugin\Plugin;
	use nutshell\behaviour\Native;
	use nutshell\behaviour\AbstractFactory;
	use nutshell\Nutshell;
	
	/**
	 * BTL (pron. "Beatle") - The Batchable Transmission Layer.
	 * Heaps compatible with GSRC
	 * @author Dean Rather
	 */
	class Btl extends Plugin implements Native, AbstractFactory
	{
		public static function loadDependencies()
		{
			require_once(__DIR__._DS_.'BtlException.php');
			require_once(__DIR__._DS_.'BtlRequestObject.php');
			require_once(__DIR__._DS_.'Service.php');
		}
		
		public static function registerBehaviours(){}
		
		public static function runFactory($handler)
		{	
			// We might need this if they do a 'query' request
//			$this->plugin->MvcQuery();
			return new Service();
		}
	}
}