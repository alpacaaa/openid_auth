
# OpenID Authentication #

This is a Symphony CMS extension that allow users to authenticate with their OpenID.


- Author: Marco Sampellegrini ([alpacaaa](http://github.com/alpacaaa/))
- Github repository: http://github.com/alpacaaa/openid_auth/
- Release date: 9th February 2011
- Version: 0.2


## Installation

Enable the extension [as always](http://symphony-cms.com/learn/tasks/view/install-an-extension/).
During the authentication process, the library needs to store some data. There are quite a few 
adapter available, but the most straightforward and simple is to use a file based store.
All you need to do is to designate a writable folder for this purpose.

It's better to keep it out of your public directory, but it isn't required.
Add a new entry to your `manifest/config.php` with the absolute path of the folder:

	'openid-auth' => array(
		'store-path' => '~/top-secret/id_store'
	),

If you don't provide any path, `EXTENSIONS. '/openid_auth/id_store'` will be used instead.
Just make sure it is writeable.

### Installing from git
Remember to initialize modules.

	cd extensions
	git clone git://github.com/alpacaaa/openid_auth.git
	cd openid_auth
	git submodule update --init


## Basic Usage

At its core the extension provides two events: *OpenID Authentication* and *OpenID Data*.
You need to attach both to your page and use a form like this to allow login:

	<form method="get" action="">
		<p>
			OpenID identifier:
			<input type="text" name="openid-identifier" />

			<input type="submit" />
		</p>
	</form>

If the authentication went fine, *OpenID Data* event will append to your xml the identifier of the user.



## Advanced Usage

There are a few features that is worth noting.

### Providers list
Along with the extension comes a `providers.json` file which lists most of the well known OpenID providers.
You should let the user choose between a set of providers so that he/she just needs to insert the username.
The list is in json format so that it can be used together with javascript libraries such as 
[OpenID selector](http://code.google.com/p/openid-selector/), which empower [StackOverflow](http://stackoverflow.com/users/login) login page (quite cool).

*OpenID Authentication* event attach this list as xml to your frontend.


### Simple Registration Extension
From the spec page:
>	OpenID Simple Registation is an extension to the OpenID Authentication protocol that allows for very light-weight profile exchange.
>	It is designed to pass eight commonly requested pieces of information when an End User goes to register a new account with a web service.

In short you can request, along with the user identifier, other information such as *fullname* or *date of birthday*.
The full list of parameters can be found here: http://openid.net/specs/openid-simple-registration-extension-1_0.html#response_format

There are two ways to request parameters.

- **(Sucks)** Send them with your form.

Just include two additional hidden inputs to your form and name them `required-fields` and `optional-fields`.
They have to be array, so use a syntax like this:

	<input type="hidden" name="required-fields[]" value="fullname" />
	<input type="hidden" name="required-fields[]" value="language" />

This method is ok only when you have optional fields but in fact should be avoided.

- **(Best)** Store them in your `manifest/config.php`.

Add a new entry to your config file that looks like this:

	'openid-auth' => array(
		'sreg-required-fields' => array('fullname', 'dob'),
		'sreg-optional-fields' => array('language')
	),

Yeah, that was easy.


### Delegates

After a succesful authentication, a new delegate is fired: `openidAuthComplete`.
For an example callback, have a look at `extension.driver.php`.

Basically, it just provides the identifier and the simple registration data, if any.
This is useful to store the user in your database, or associate his/her OpenID with
an already existing member.
