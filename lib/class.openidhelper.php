<?php

	require_once "Auth/OpenID/Consumer.php";
	require_once "Auth/OpenID/FileStore.php";
	require_once "Auth/OpenID/SReg.php";

	class OpenIDHelper
	{
		public static function authenticate($openid_identifier, $trust_root, $return_to, $fields = null)
		{
			$consumer = self::getConsumer();
			$auth_request = $consumer->begin($openid_identifier);

			if (!$auth_request)
				throw new Exception(__('Authentication error: not a valid OpenID.'));

			if (is_array($fields)) $fields = (object) $fields;

			$sreg_request = Auth_OpenID_SRegRequest::build($fields->required, $fields->optional);

			if ($sreg_request)
				$auth_request->addExtension($sreg_request);

			if ($auth_request->shouldSendRedirect())
			{
				$redirect_url = $auth_request->redirectURL($trust_root, $return_to);

				if (Auth_OpenID::isFailure($redirect_url))
					throw new Exception(__('Could not redirect to server: %s.', array($redirect_url->message)));

				redirect($redirect_url);
			}

			$form_id = 'openid_message';
			$form_html = $auth_request->htmlMarkup(
				$trust_root, $return_to, false, array('id' => $form_id)
			);

			if (Auth_OpenID::isFailure($form_html))
				throw new Exception(__('Could not redirect to server: %s.', array($form_html->message)));

			echo $form_html;
		}
		
		public static function completeAuthentication($return_to)
		{
			$consumer = self::getConsumer();
			$response = $consumer->complete($return_to);

			if ($response->status == Auth_OpenID_CANCEL)
				throw new Exception(__('Verification cancelled.'));

			if ($response->status == Auth_OpenID_FAILURE)
				throw new Exception(__('OpenID authentication failed'). ': '. __($response->message));

			// Success!
			$openid_data = new StdClass();
			$openid_data->identifier = $response->getDisplayIdentifier();
			$openid_data->sreg_data  = Auth_OpenID_SRegResponse::fromSuccessResponse($response)->contents();

			return $openid_data;
		}
		
		protected static function getStore()
		{
			$store_path = self::getStorePath();

			if (!file_exists($store_path) && !mkdir($store_path))
			{
					throw new Exception(
						__('Could not create the FileStore directory %s. Please check the effective permissions.',
						array($store_path))
					);
			}
			return new Auth_OpenID_FileStore($store_path);
		}
		
		protected static function getConsumer()
		{
			$store = self::getStore();
			return new Auth_OpenID_Consumer($store);
		}
		
		protected static function getStorePath()
		{
			$options = Symphony::Configuration()->get('openid-auth');
			if (is_null($options) || is_null($options['store-path']))
			{			
				return EXTENSIONS. '/openid_auth/id_store';
			}

			return $options['store-path'];
		}
	}
