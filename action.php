<?php
/**
* DokuWiki Plugin sneakyindexfix (Action Component)
*
* 
*
* @license GPL 2 (http://www.gnu.org/licenses/gpl.html)
* @author lisps
*/
if (!defined('DOKU_INC')) die();
if (!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN', DOKU_INC . 'lib/plugins/');
require_once (DOKU_PLUGIN . 'action.php');

class action_plugin_sneakyindexfix extends DokuWiki_Action_Plugin {
    
    /**
     * Register the eventhandlers
     */
    function register(Doku_Event_Handler $controller) {
        $controller->register_hook('AUTH_ACL_CHECK', 'AFTER', $this, '_acl_check');
    }


    /**
     * 
     * only if sneaky_index is enabled
     * if namspace with AUTH_NONE -> check for acl definition 
     * with ACL > AUTH_NONE in deeper namespaces and manipulate ACL 
     * for this namespace to AUTH_READ
     * 
     */
    function _acl_check(Doku_Event $event) {
        if($event->result !== AUTH_NONE) return;
        $data = $event->data;
        
        /*copy auth_aclcheck_cb start*/
        $id     = & $data['id'];
        $user   = & $data['user'];
        $groups = & $data['groups'];

        global $conf;
        global $AUTH_ACL;
        /* @var DokuWiki_Auth_Plugin $auth */
        global $auth;
        /*copy end*/


        if(noNS($id) !== '*') return; //only namespacecheck
        if(!$conf['sneaky_index']) return; //we only need this when sneaky_index is enabled

        /*copy auth_aclcheck_cb start*/
        if(!$auth) return AUTH_NONE;

        //make sure groups is an array
        if(!is_array($groups)) $groups = array();

        if(!$auth->isCaseSensitive()) {
            $user   = utf8_strtolower($user);
            $groups = array_map('utf8_strtolower', $groups);
        }
        $user   = auth_nameencode($auth->cleanUser($user));
        $groups = array_map(array($auth, 'cleanGroup'), (array) $groups);


        //prepend groups with @ and nameencode
        foreach($groups as &$group) {
            $group = '@'.auth_nameencode($group);
        }
    
        $ns   = getNS($id);
        $perm = -1;
    
        //add ALL group
        $groups[] = '@ALL';

        //add User
        if($user) $groups[] = $user;
        /*copy end*/

        //check for deeper acl definition
        $matches = preg_grep('/^'.preg_quote($ns, '/').':[\w:\*]+[ \t]+([^ \t]+)[ \t]+/', $AUTH_ACL);
        if(count($matches)) {
            foreach($matches as $match) {
                $match = preg_replace('/#.*$/', '', $match); //ignore comments
                $acl   = preg_split('/[ \t]+/', $match);
                if(!$auth->isCaseSensitive() && $acl[1] !== '@ALL') {
                    $acl[1] = utf8_strtolower($acl[1]);
                }
                if(!in_array($acl[1], $groups)) {
                    continue;
                }
                if($acl[2] > AUTH_NONE) {
                    $event->result =  true;
                    return true; //set read access for this namespace
                }
            }

        }
    }
    
}
