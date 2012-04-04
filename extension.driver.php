<?php

	class Extension_Openid_Auth extends Extension {
		
		public function getSubscribedDelegates() {
			return array(
				array(
					'page' => '/frontend/',
					'delegate' => 'openidAuthComplete',
					'callback' => 'authenticationComplete'
				)
			);
		}

		public function authenticationComplete($context)
		{
			$openid_data = $context['openid-data'];
			$cookie = new Cookie('openid', TWO_WEEKS, __SYM_COOKIE_PATH__);
			$cookie->set('identifier', $openid_data->identifier);
			$cookie->set('sreg-data',  $openid_data->sreg_data);
		}
	}
