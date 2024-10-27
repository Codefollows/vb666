<?php
/**
 * This file is an extension/customization for vBulletin of the smolblog/oauth2-twitter library
 */
use League\OAuth2\Client\Token\AccessToken;

class TwitterLogin_Oauth2_Provider extends Smolblog\OAuth2\Client\Provider\Twitter
{
	/**
	 * Returns the URL for requesting the resource owner's details.
	 *
	 * @param AccessToken $token
	 * @return string
	 */
	public function getResourceOwnerDetailsUrl(AccessToken $token): string
	{
		//include the profile picture in the user data
		return 'https://api.twitter.com/2/users/me?user.fields=profile_image_url';
	}
}
