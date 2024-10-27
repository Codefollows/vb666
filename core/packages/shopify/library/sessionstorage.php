<?php
/*========================================================================*\
|| ###################################################################### ||
|| # vBulletin  - Licence Number VBC58C8600
|| # ------------------------------------------------------------------ # ||
|| # Copyright 2000-2022 MH Sub I, LLC dba vBulletin. All Rights Reserved.  # ||
|| # This file may not be redistributed in whole or significant part.   # ||
|| # ----------------- VBULLETIN IS NOT FREE SOFTWARE ----------------- # ||
|| # http://www.vbulletin.com | http://www.vbulletin.com/license.html   # ||
|| ###################################################################### ||
\*========================================================================*/

require_once(DIR . '/packages/shopify/vendor/autoload.php');
class shopify_Library_SessionStorage implements \Shopify\Auth\SessionStorage
{
	use vB_Trait_NoSerialize;

    // From what I can tell, this session storage is actually only for public apps
    // that go through the OAuth process during installation/auth on a specific
    // Shopify store instance. And in that scenario, a storage is expected to
    // store multiple stores' sessions.
    // Since we'll be using private apps, this isn't used, and we only have a
    // single store to worry about.
    // Unfortunately, the Context::initialize still requires some implementation
    // of SessionStorage.

	public function __construct()
	{
	}

    /**
     * Internally handles storing the given session object.
     *
     * @param \Shopify\Auth\Session $session The session to store
     *
     * @return bool Whether the operation succeeded
     */
    public function storeSession(\Shopify\Auth\Session $session): bool
	{
		// So this might be a GUID, or it might be some string like offline_{shopdomain}
		// Since we can't guarantee the length, we hash it for insert.
		$sessionId = $session->getId();
		$id_hash = md5($sessionId);
		$expires = $session->getExpires();
        $expiresUnixtime = 0;
        if (!is_null($expires))
        {
            $expiresUnixtime = $expires->getTimestamp();
        }
		$params = [
            'expires' => $expiresUnixtime,
            'id_hash' => $id_hash,
            'session' => serialize($session),
        ];
		$assertor = vB::getDbAssertor();
		$result = $assertor->insertIgnore('shopify:shopify_session', $params);

        return !empty($result);
	}

    /**
     * Internally handles loading the given session.
     *
     * @param string $sessionId The id of the session to load
     *
     * @return \Shopify\Auth\Session|null The session if it exists, null otherwise
     */
    public function loadSession(string $sessionId)
    {
		$id_hash = md5($sessionId);
        $timenow = time();
		$assertor = vB::getDbAssertor();
        $row = $assertor->getRow('shopify:shopify_session', ['id_hash' => $id_hash]);
        if (!empty($row) AND (empty($row['expires']) OR $row['expires'] > $timenow))
        {
            // Limit it to 'Shopify\Auth\Session'
            $options = ['allowed_classes' => [\Shopify\Auth\Session::class, ]];
            $session = unserialize($row['session'], $options);
            if ($session instanceof \Shopify\Auth\Session)
            {
                return $session;
            }
        }

        return null;
    }

    /**
     * Internally handles deleting the given session.
     *
     * @param string $sessionId The id of the session to delete
     *
     * @return bool Whether the operation succeeded
     */
    public function deleteSession(string $sessionId): bool
    {
		$id_hash = md5($sessionId);
		$params = [
            'id_hash' => $id_hash,
        ];
		$assertor = vB::getDbAssertor();
		$result = $assertor->delete('shopify:shopify_session', $params);

        return !empty($result);
    }

    // Not used yet, but presumably can be used with a cron.
    // Unnecessary until session storage is actually used for
    // private apps, or we have to switch to a public app
    public static function deleteExpiredSessions()
    {
        $timenow = time();
		$assertor = vB::getDbAssertor();
        $conditions = [
            ['field' => 'expires', 'value' => 0, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_NE],
            ['field' => 'expires', 'value' => $timenow, vB_dB_Query::OPERATOR_KEY => vB_dB_Query::OPERATOR_LTE],
        ];
		$result = $assertor->delete('shopify:shopify_session', $conditions);
    }
}

/*=========================================================================*\
|| #######################################################################
|| # Downloaded: 19:44, Thu Apr 14th 2022
|| # CVS: $RCSfile$ - $Revision: 105377 $
|| #######################################################################
\*=========================================================================*/
