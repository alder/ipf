<?php

class IPF_Session_Middleware
{
    function processRequest(&$request)
    {
        $session = new Session();
        $user = new User();
        if (!isset($request->COOKIE[IPF::get('session_cookie_id')])) {
            $request->user = $user;
            $request->session = $session;
            return false;
        }
        try {
            $data = $this->_decodeData($request->COOKIE[IPF::get('session_cookie_id')]);
        } catch (Exception $e) {
            $request->user = $user;
            $request->session = $session;
            return false;
        }
        if (isset($data[$user->session_key])) {
            $found_user = $user->getTable()->find($data[$user->session_key]);
            if ($found_user) {
                $request->user = $found_user;
                if (43200 < IPF_Utils::dateCompare($request->user->last_login)) {
                    $request->user->last_login = gmdate('Y-m-d H:i:s');
                    $request->user->save();
                }
            } else
                $request->user = $user;
        } else
            $request->user = $user;

        if (isset($data['IPF_SESSION_KEY'])) {
            $found_session = $session->getTable()->findOneBySession_key($data['IPF_SESSION_KEY']);
            if ($found_session)
                $request->session = $found_session;
            else
                $request->session = $session;
        } else {
            $request->session = $session;
        }
        return false;
    }

    function processResponse($request, $response)
    {
        if ($request->session->touched) {
            $request->session->save();
            $data = array();
            if ($request->user->id > 0) {
                $data[$request->user->session_key] = $request->user->id;
            }
            $data['IPF_SESSION_KEY'] = $request->session->session_key;
            $response->cookies[IPF::get('session_cookie_id')] = $this->_encodeData($data);
        }
        return $response;
    }

    protected function _encodeData($data)
    {
        if ('' == ($key = IPF::get('secret_key'))) {
            throw new IPF_Exception('Security error: "secret_key" is not set in the configuration file.');
        }
        $data = serialize($data);
        return base64_encode($data).md5(base64_encode($data).$key);
    }

    protected function _decodeData($encoded_data)
    {
        $check = substr($encoded_data, -32);
        $base64_data = substr($encoded_data, 0, strlen($encoded_data)-32);
        if (md5($base64_data.IPF::get('secret_key')) != $check) {
            throw new IPF_Exception('The session data may have been tampered.');
        }
        return unserialize(base64_decode($base64_data));
    }

    /*
    public static function processContext($signal, &$params)
    {
        $params['context'] = array_merge($params['context'],
            IPF_Session_Middleware_ContextPreProcessor($params['request']));
    }
    */
}

/*
function IPF_Session_Middleware_ContextPreProcessor($request)
{
    return array('user' => $request->user);
}

Pluf_Signal::connect('Pluf_Template_Context_Request::construct',
                     array('Pluf_Middleware_Session', 'processContext'));
*/