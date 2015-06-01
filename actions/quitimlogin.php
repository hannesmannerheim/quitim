<?php
/**
 * StatusNet, the distributed open-source microblogging tool
 *
 * Login form
 *
 * PHP version 5
 *
 * LICENCE: This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @category  Login
 * @package   GNUsocial
 * @author    Evan Prodromou <evan@status.net>
 * @author    Sarven Capadisli <csarven@status.net>
 * @author    Mikael Nordfeldth <mmn@hethane.se>
 * @copyright 2008-2009 StatusNet, Inc.
 * @copyright 2013 Free Software Foundation, Inc.
 * @license   http://www.fsf.org/licensing/licenses/agpl-3.0.html GNU Affero General Public License version 3.0
 * @link      http://www.gnu.org/software/social/
 */

if (!defined('GNUSOCIAL')) { exit(1); }

class QuitimLoginAction extends LoginAction
{
    function showStylesheets()
    {

        $this->cssLink(Plugin::staticPath('Quitim', 'css/quitim.css'));

    }

    function showBody()
    {
		$bodyclasses = 'quitim';
		$this->elementStart('body', array('id' => strtolower($this->trimmed('action')), 'class' => $bodyclasses, 'ontouchstart' => ''));        

        $this->element('div', array('id' => 'spinner-overlay'));
        $this->element('div', array('id' => 'popup-register'));        

        $this->elementStart('div', array('id' => 'wrap'));

        $this->elementStart('div', array('id' => 'core'));
        $this->elementStart('div', array('id' => 'aside_primary_wrapper'));
        $this->elementStart('div', array('id' => 'content_wrapper'));
        $this->elementStart('div', array('id' => 'site_nav_local_views_wrapper'));

        $this->elementStart('div', array('id' => 'content'));

        $this->element('div', array('id' => 'loginlogo'));

        $this->elementStart('div', array('id' => 'content_inner'));
        
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
        $this->element('input', array('id'=>'nickname', 'type'=>'text', 'name'=>'nickname',  'placeholder'=>_('Username or email')));
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
        $this->elementEnd('div');


        $this->elementEnd('div');
        $this->elementEnd('div');
        $this->elementEnd('div');
        $this->elementEnd('div');

        $this->elementStart('div',array('id'=>'what-is-quitim'));
        $this->raw('<h2>Welcome to Quitim!</h2>We are a federated, open source, non-profit, non-commercial, mobile image sharing community. Confused? Check out the recent <a href="http://quit.im/favorited">popular images</a>.<br><br>When you <a class="register-link" href="'.common_local_url('register').'">sign up</a> here you become an activist in the online anti-capitalist struggle, a part in building an open and non-commercial space where we can interact and organize without profit driven corporations interfering and sabotaging our communications. <br><br>★ Quitim is not a service and you are not a customer here – we\'re doing this together.<br><br>★ For-profit businesses are not allowed to register. Users who harass others or propagate discriminatory political views – such as racism, sexism, ableism, homo- and transphobia – will be removed. Quitim users are expected to participate in making the network a respectful and kind place where everyone feels safe.<br><br>★ Quitim is part of the <a href="http://gnu.io/">GNU social</a> network, which means that you don\'t have to sign up to Quitim to talk to your Quitim friends – you can join any GNU social server and follow them from there. <br><br> ★ All your images on Quitim are licensed under Creative Commons BY-NC, which means no one can use your content for advertising.');
        $this->elementStart('a',array('id'=>'quitim-sign-up', 'href'=>common_local_url('register'), 'class'=>'register-link'));
        $this->raw('Sign up!');
        $this->elementEnd('a');        
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
              
        $this->showScripts();
        $this->elementEnd('body');
    }    

    /**
     * Core of the display code
     *
     * Shows the login form.
     *
     * @return void
     */
    function showContent()
    {
//
    }
}
