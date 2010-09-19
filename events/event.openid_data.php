<?php

	require_once TOOLKIT . '/class.event.php';

	class EventOpenID_Data extends Event{
		
		public static function about(){
			return array(
					 'name' => 'OpenID Data',
					 'author' => array(
							'name' => 'Marco Sampellegrini',
							'email' => 'm@rcosa.mp'),
					 'version' => '1.0',
					 'release-date' => '2010-09-19');	
		}

		public static function allowEditorToParse(){
			return false;
		}

		public static function documentation(){
			return '
				<p>Returns OpenID user data.</p>
				<p>
					See <a href="'. URL. '/symphony/blueprints/events/info/openid_auth/">OpenID Authentication</a> 
					for an example form markup.
				</p>
			';
		}

		public function load(){
			$cookie = new Cookie('openid', TWO_WEEKS, __SYM_COOKIE_PATH__);
			$openid_data = new XMLElement('openid-data');

			if ($id = $cookie->get('identifier'))
				$openid_data->appendChild(new XMLElement('identifier', $id));

			$values = $cookie->get('sreg-data');
			if ($values)
			{
				$sreg_data = new XMLElement('sreg-data');

				foreach ($values as $k => $v)
				{
					$sreg_data->appendChild(
						new XMLElement('property', $v, array('key' => $k))
					);
				}

				$openid_data->appendChild($sreg_data);
			}

			return $openid_data;
		}

		protected function __trigger() { }
	}
