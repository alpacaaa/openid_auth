<?php

	require_once TOOLKIT . '/class.event.php';

	class EventOpenID_Auth extends Event{
		
		public static function about(){
			return array(
					 'name' => 'OpenID Authentication',
					 'author' => array(
							'name' => 'Marco Sampellegrini',
							'email' => 'm@rcosa.mp'),
					 'version' => '1.1',
					 'release-date' => '2011-02-09');	
		}

		public static function allowEditorToParse(){
			return false;
		}

		public function load(){			
			$root = new XMLElement('openid-auth');
			$providers_xml = new XMLElement('providers');
			$providers = self::getProviders();

			foreach ($providers as $handle => $provider)
			{
				$el = new XMLElement('provider');
				$el->setAttribute('name', $handle);

				foreach ($provider as $k => $v)
				{
					$property = new XMLElement('property');
					$property->setAttribute('key', $k);
					$property->setValue($v);

					$el->appendChild($property);
				}

				$providers_xml->appendChild($el);
			}

			$root->appendChild($providers_xml);

			if (isset($_GET['openid-identifier']) || isset($_GET['finish-auth']))
				$xml = $this->__trigger();
			
			if ($xml) $root->appendChild($xml);
			return $root;
		}

		protected function __trigger(){

			require_once EXTENSIONS. '/openid_auth/lib/class.openidhelper.php';

			$status  = 'success';
			$openid_identifier = $_GET['openid-identifier'];
			$trust_root = URL;
			$return_to  = URL. getCurrentPage(). '?finish-auth'; //&debug

			if ($openid_identifier)
			{
				// Authenticate!
				$openid_identifier = str_replace(
					'{username}', $_GET['username'], $openid_identifier
				);

				$fields = self::getSregFields();

				try {
					OpenIDHelper::authenticate(
						$openid_identifier, $trust_root, $return_to, $fields
					);
				}
				catch(Exception $e)
				{
					$status  = 'failed';
					$message = $e->getMessage();
				}

				if ($status == 'success') exit();
			}


			if (isset($_GET['finish-auth']))
			{
				try {
					$openid_data = OpenIDHelper::completeAuthentication($return_to);
				}
				catch(Exception $e)
				{
					$status  = 'failed';
					$message = $e->getMessage();
				}
			}


			$xml = new XMLElement('authentication');
			$xml->setAttribute('status', $status);

			if ($message)
			{
				$xml->appendChild(
					new XMLElement('message', $message)
				);
				return $xml;
			}

			$xml->appendChild(
				new XMLElement('identifier', $openid_data->identifier)
			);

			if ($openid_data->sreg_data)
			{
				$sreg = new XMLElement('sreg-data');
				foreach ($openid_data->sreg_data as $key => $value)
				{
					$sreg->appendChild(
						new XMLElement('property', $value, array('key' => $key))
					);
				}

				$xml->appendChild($sreg);
			}

			require_once TOOLKIT. '/class.extensionmanager.php';
			$em = new ExtensionManager(Symphony::Engine());

			$em->notifyMembers(
				'openidAuthComplete', '/frontend/',	array(
					'openid-data' => $openid_data
			));

			return $xml;
		}

		public static function getProviders($file = '')
		{
			if (!$file)
				$file = EXTENSIONS. '/openid_auth/providers.json';

			if (!file_exists($file) || !is_readable($file)) return array();

			return json_decode(file_get_contents($file));
		}

		public static function getSregFields()
		{
			$options = Symphony::Configuration()->get('openid-auth');
			$default = array(
				'required' => $_GET['required-fields'],
				'optional' => $_GET['optional-fields']
			);

			if (!is_array($options)) return $default;

			return array_merge($default, array_filter(array(
				'required' => $options['sreg-required-fields'],
				'optional' => $options['sreg-optional-fields']
			)));
		}

		public static function documentation(){
			return '
				<p>
					This event let users authenticate through OpenID.<br />
					It can be used in conjunction with the example event provided by the extension: 
					<a href="'. URL. '/symphony/blueprints/events/info/openid_data/">OpenID Data</a>.
				</p>
				<p>This is an example of the form markup you can use on your front end.</p>
				<pre class="XML"><code>
&lt;xsl:template match=&quot;/&quot;&gt;
	
	&lt;xsl:apply-templates select=&quot;data/events/openid-data | data/events/openid-auth/authentication&quot; /&gt;
	
	&lt;form method=&quot;get&quot; action=&quot;&quot;&gt;
		&lt;p&gt;
			username:
			&lt;input type=&quot;text&quot; name=&quot;username&quot; /&gt;
		&lt;/p&gt;
		
		&lt;p&gt;
			provider:
			&lt;select name=&quot;openid-identifier&quot;&gt;
				&lt;xsl:apply-templates select=&quot;data/events/openid-auth/providers/provider&quot; /&gt;
			&lt;/select&gt;
		&lt;/p&gt;
		
		&lt;p&gt;
			&lt;input type=&quot;hidden&quot; name=&quot;required-fields[]&quot; value=&quot;fullname&quot; /&gt;
			&lt;input type=&quot;hidden&quot; name=&quot;optional-fields[]&quot; value=&quot;dob&quot; /&gt; &lt;!-- date of birthday --&gt;
			&lt;input type=&quot;hidden&quot; name=&quot;optional-fields[]&quot; value=&quot;language&quot; /&gt;

			&lt;input type=&quot;submit&quot; /&gt;
		&lt;/p&gt;
	&lt;/form&gt;
&lt;/xsl:template&gt;

&lt;xsl:template match=&quot;providers/provider&quot;&gt;
	&lt;option value=&quot;{property[@key = \'url\']}&quot;&gt;&lt;xsl:value-of select=&quot;@name&quot; /&gt;&lt;/option&gt;
&lt;/xsl:template&gt;

&lt;xsl:template match=&quot;identifier&quot;&gt;
	&lt;p&gt;You are logged in as: &lt;xsl:value-of select=&quot;text()&quot; /&gt;&lt;/p&gt;
&lt;/xsl:template&gt;

&lt;xsl:template match=&quot;sreg-data&quot;&gt;
	&lt;dl&gt;
		&lt;xsl:apply-templates match=&quot;property&quot; /&gt;
	&lt;/dl&gt;
&lt;/xsl:template&gt;

&lt;xsl:template match=&quot;sreg-data/property&quot;&gt;
	&lt;dt&gt;
		&lt;xsl:value-of select=&quot;@key&quot; /&gt;
	&lt;/dt&gt;
	&lt;dd&gt;
		&lt;xsl:value-of select=&quot;text()&quot; /&gt;
	&lt;/dd&gt;
&lt;/xsl:template&gt;

&lt;xsl:template match=&quot;message&quot;&gt;
	&lt;div class=&quot;errors&quot;&gt;&lt;xsl:value-of select=&quot;text()&quot; /&gt;&lt;/div&gt;
&lt;/xsl:template&gt;
				</code></pre>
			';
		}
	}
