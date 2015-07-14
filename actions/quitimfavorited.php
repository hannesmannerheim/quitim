<?php

/**
 *
 *   QUITIM
 *
 *   h@nnesmannerhe.im
 *
 *   Popular page
 *
 *
 */

class QuitimFavoritedAction extends FormAction
{
    var $notice;
    var $page;

    protected $needLogin = false;

    protected function prepare(array $args=array())
    {
        parent::prepare($args);

        $this->page = ($this->arg('page')) ? ($this->arg('page')+0) : 1;

        $stream = new PopularNoticeStream(Profile::current());
        $this->notice = $stream->getNotices(($this->page-1)*NOTICES_PER_PAGE, NOTICES_PER_PAGE+1);

        return true;
    }

    function isReadOnly($args)
    {
        return true;
    }

    function title()
    {
        $current_user = common_current_user();
    	if(!$current_user) {
            return _('Welcome!');
        } else {
            return _('Quitim moments');
        }


    }

    protected function handle()
    {

        parent::handle();

    }

    protected function doPost()
    {
        // XXX: login throttle

        $nickname = $this->trimmed('nickname');
        $password = $this->arg('password');

        $user = common_check_user($nickname, $password);


        if (!$user instanceof User) {
            // TRANS: Form validation error displayed when trying to log in with incorrect credentials.
            throw new ServerException(_('Incorrect username or password.'));
        }

        // success!
        if (!common_set_user($user)) {
            // TRANS: Server error displayed when during login a server error occurs.
            throw new ServerException(_('Error setting user. You are probably not authorized.'));
        }

        common_real_login(true);
        $this->updateScopedProfile();

        if ($this->boolean('rememberme')) {
            common_rememberme($user);
        }

        $url = common_get_returnto();

        if ($url) {
            // We don't have to return to it again
            common_set_returnto(null);
            $url = common_inject_session($url);
        } else {
            $url = common_local_url('all',
                                    array('nickname' => $this->scoped->nickname));
        }

        common_redirect($url, 303);
    }

    public function showPageNotice()
    {

    }

    public function showInstructions()
    {

    }


    public function showForm($msg=null, $success=false)
    {

    }


    function showSections()
    {

    }

    function showStylesheets()
    {

        // We only want quitim stylesheet
        $path = Plugin::staticPath('Quitim', '');
        $this->cssLink($path.'css/quitim.css?changed='.date('YmdHis',filemtime(QUITIMDIR.'/css/quitim.css')));


    }

    function showBody()
    {

		$current_user = common_current_user();

		$bodyclasses = 'quitim';
		if($current_user) {
			$bodyclasses .= ' user_in';
        } else {
            $bodyclasses .= ' logged-out';
        }
		$this->elementStart('body', array('id' => strtolower($this->trimmed('action')), 'class' => $bodyclasses, 'ontouchstart' => ''));
        $this->element('div', array('id' => 'spinner-overlay'));
        $this->element('div', array('id' => 'popup-register'));

        $this->elementStart('div', array('id' => 'wrap'));
        QuitimFooter::showQuitimFooter();

        $this->elementStart('div', array('id' => 'header'));
		$this->elementStart('a', array('href' => '#top'));
		$this->elementStart('h1');
        $this->raw(_('Quitim moments'));
		$this->elementEnd('h1');
        $this->elementEnd('a');

		$this->elementStart('div', array('id' => 'topright'));
		$this->elementEnd('div');

        $this->elementEnd('div');

        $this->elementStart('div', array('id' => 'core'));
        $this->elementStart('div', array('id' => 'aside_primary_wrapper'));
        $this->elementStart('div', array('id' => 'content_wrapper'));
        $this->elementStart('div', array('id' => 'site_nav_local_views_wrapper'));

        $this->elementStart('div', array('id' => 'content'));
        if (common_logged_in()) {
            if (Event::handle('StartShowNoticeForm', array($this))) {
                $this->showNoticeForm();
                Event::handle('EndShowNoticeForm', array($this));
            }
        }

        $this->elementStart('div', array('id' => 'content_inner'));

        $this->elementStart('div', array('id' => 'thumb-thread'));
        $this->elementStart('div', array('id' => 'set-thumbnail-view'));
        $this->elementEnd('div');
        $this->elementStart('div', array('id' => 'set-threaded-view'));
        $this->elementEnd('div');
        $this->elementEnd('div');


        $this->elementStart('div', array('id' => 'usernotices', 'class' => 'noticestream thumbnail-view'));
		$this->showNoticesWithCommentsAndFavs();
        $this->elementEnd('div');

        $this->elementEnd('div');
        $this->elementEnd('div');


        $this->elementEnd('div');
        $this->elementEnd('div');
        $this->elementEnd('div');
        $this->elementEnd('div');



        $this->elementEnd('div');

        // login overlay if logged out
        $current_user = common_current_user();
		if(!$current_user) {

            $this->elementStart('div', array('id' => 'login-overlay'));
            $this->element('div', array('id' => 'loginlogo'));

            $this->elementStart('div', array('id' => 'login-content_inner'));

            if ($msg = $this->getError()) {
                $this->element('div', 'error', $msg);
                }

            $this->elementStart('form', array('method' => 'post',
                                              'id' => 'form_login',
                                              'class' => 'form_settings',
                                              'action' => common_local_url('login')));
            $this->elementStart('fieldset');
            // TRANS: Form legend on login page.
            $this->elementStart('ul', 'form_data');
            $this->elementStart('li');
            // TRANS: Field label on login page.
            $this->element('input', array('id'=>'nickname', 'type'=>'text', 'name'=>'nickname', 'autocomplete'=>'off', 'placeholder'=>_('Username or email')));
            $this->elementEnd('li');
            $this->elementStart('li');
            // TRANS: Field label on login page.
            $this->element('input', array('id'=>'password', 'class'=>'password', 'type'=>'password', 'name'=>'password',  'placeholder'=>_('Password')));
            //        $this->password('password', _('Password'));
            $this->elementEnd('li');
            $this->elementStart('li');
            // TRANS: Checkbox label label on login page.
            $this->checkbox('rememberme', _('Remember me'), 'checked');
            $this->elementEnd('li');
            $this->elementEnd('ul');
            // TRANS: Button text for log in on login page.
            $this->submit('submit', _m('BUTTON','Login'));
            $this->hidden('token', common_session_token());
            $this->elementEnd('fieldset');
            $this->elementEnd('form');
            $this->elementStart('p', array('id'=>'lostpassword'));
            $this->element('a', array('href' => common_local_url('recoverpassword')),
                           // TRANS: Link text for link to "reset password" on login page.
                           _('Lost or forgotten password?'));
            $this->elementEnd('p');



            $this->elementEnd('div');

            $this->elementStart('div',array('id'=>'what-is-quitim'));
            $this->raw('<h2>Welcome to Quitim!</h2>We are a federated, open source, non-profit, non-commercial, mobile image sharing community.<br><br>When you <a class="register-link" href="'.common_local_url('register').'">sign up</a> here you become an activist in the online anti-capitalist struggle, a part in building an open and non-commercial space where we can interact and organize without profit driven corporations interfering and sabotaging our communications. <br><br>★ Quitim is not a service and you are not a customer here – we\'re doing this together.<br><br>★ For-profit businesses are not allowed to register. Users who harass others or propagate discriminatory political views – such as racism, sexism, ableism, homo- and transphobia – will be removed. Quitim users are expected to participate in making the network a respectful and kind place where everyone feels safe.<br><br>★ Quitim is part of the <a href="http://gnu.io/">GNU social</a> network, which means that you don\'t have to sign up to Quitim to talk to your Quitim friends – you can join any GNU social server and follow them from there. <br><br> ★ All your images on Quitim are licensed under Creative Commons BY-NC, which means no one can use your content for advertising.');
            $this->elementStart('a',array('id'=>'quitim-sign-up', 'href'=>common_local_url('register'), 'class'=>'register-link'));
            $this->raw('Sign up!');
            $this->elementEnd('a');
            $this->elementEnd('div');


            $this->elementEnd('div');

            $this->elementEnd('div');

            $this->elementStart('script');

            $checknicknameurl = common_local_url('ApiCheckNickname',array('format'=>'json'));
            $registerapiurl = common_local_url('ApiAccountRegister',array('format'=>'json'));
            $this->raw('window.registerNickname = \''._('Nickname').'\';'.
                       'window.signUpEmail = \''._('Email address').'\';'.
                       'window.registerHomepage = \''._('Homepage').'\';'.
                       'window.registerBio = \''._('Bio').'\';'.
                       'window.loginPassword = \''._('Password').'\';'.
                       'window.registerRepeatPassword = \''._('Repeat password').'\';'.
                       'window.signUpButtonText = \''._('Sign up!').'\';'.
                       'window.licenseText = '.json_encode('By signing up I confirm that all my posted images and texts are available under <a href="'.htmlspecialchars(common_config('license', 'url')).'">'.htmlspecialchars(common_config('license', 'title')).'</a>. I understand that I should keep my own backups and that I can be suspended from using '.common_config('site', 'name').' at any time for any reason. The administrators of '.common_config('site', 'name').' take no responsability whatsoever. We can\'t even spell responsability. If you do not like this – start your own GNU social instance. You can follow Quitim users on any server.').';'.
                       'window.checkNicknameUrl = \''.$checknicknameurl.'\';'.
                       'window.registerApiUrl = \''.$registerapiurl.'\';');
            $this->elementEnd('script');
        }
        $this->showScripts();
        $this->elementEnd('body');
    }



	function showNoticesWithCommentsAndFavs()
	{

        $nl = new QuitimThreadedNoticeList($this->notice, $this);
        $cnt = $nl->show();
		$this->pagination(
			$this->page > 1, $cnt > NOTICES_PER_PAGE,
			$this->page, 'quitimfavorited');
	}

}
