<?php
/**
 * @group plugin_sneakyindexfix
 * @group plugins
 */
class plugin_sneakyindexfix_main_test extends DokuWikiTest {
    protected $oldAuth;
    protected $oldAuthAcl;
    protected $oldConf;
    
    function setUp() {
        global $auth;
        global $AUTH_ACL;
        global $USERINFO;
        global $conf;
        
        $this->oldAuth    = $auth;
        $this->oldAuthAcl = $AUTH_ACL;
        $this->oldConf    = $conf;
        
        $this->pluginsEnabled[] = 'sneakyindexfix';
        
        parent::setUp();
        
        $_SERVER['REMOTE_USER'] = 'testuser';
        $USERINFO['grps'] = array('foo','bar');
        $conf['useacl']    = 1;
        $conf['superuser'] = '';
    }
    
    function tearDown() {
        global $conf;
        global $AUTH_ACL;
        global $auth;
        
        $auth     = $this->oldAuth;
//         $AUTH_ACL = $this->oldAuthAcl;
//         $conf     = $this->conf;
    }
    
    function revertACL() {
        global $AUTH_ACL;
        $AUTH_ACL = $this->oldAuthAcl;
    }
    
    public function test_sneaky_index_off() {
        $conf['sneaky_index']    = 0;
        
        $AUTH_ACL[] = 'private:public:* @ALL  8';
        $perm = auth_quickaclcheck('private:*');
        
        $this->assertEquals(AUTH_NONE, $perm);
    }

    public function test_ns_sub_access() {
        global $AUTH_ACL;
        global $conf;
        
        $conf['sneaky_index']    = 1;

        $AUTH_ACL[] = 'private:public:* @ALL  8';
        $perm = auth_quickaclcheck('private:*');
        
        $this->assertEquals(AUTH_READ, $perm);
        
    }
    
    public function test_ns_page_access() {
        global $AUTH_ACL;
        global $conf;
        
        $conf['sneaky_index']    = 1;
        $AUTH_ACL[] = 'private:public:public @ALL  1';
        $perm = auth_quickaclcheck('private:*');
        
        $this->assertEquals(AUTH_READ, $perm);
        
    }
}
