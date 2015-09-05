// settings
window.timeBetweenPolling = 20000; // every 20 seconds


// display loading right away, if we have pagination
if($('#pagination').length>0) {
	display_loading();
	}

// always on esc-key
$(document).keyup(function(e) {
	if (e.keyCode == 27) {
		$('#quitimuserstream .account_profile_block .entity_actions').hide();
		$('#form_ostatus_connect').remove();
		$('#popup-register').hide();
		}
	});

// disable subscribe/unsubscribe button on click
$('body').on('click','.form_user_subscribe button#submit, .form_user_unsubscribe button#submit', function(){
	$(this).prop('disabled', true);
});
$('.form_user_subscribe button#submit, .form_user_unsubscribe button#submit').prop('disabled', false); // this can be cached by browser..

// hide stuff on click in margin
$('body').on('click',function(e){
	if($(e.target).is('#form_ostatus_connect')) {
		$('#form_ostatus_connect').hide();
		}
	if($(e.target).is('#quitimuserstream .account_profile_block .entity_actions')) {
		$('#quitimuserstream .account_profile_block .entity_actions').hide();
		}
	});

// activate infinite scroll for quitim actions
$(document).ready(function($){
  $('notices_primary').infinitescroll({
    debug: false,
    infiniteScroll  : !infinite_scroll_on_next_only,
    nextSelector    : 'body#quitimall li.nav_next a,'+
                      'body#quitimuserstream li.nav_next a,'+
                      'body#quitimnotifications li.nav_next a,'+
                      'body#quitimfavorited li.nav_next a,'+
                      'body#quitimpopular li.nav_next a',
    loadingImg      : ajax_loader_url,
    text            : "<em>Loading the next set of posts...</em>",
    donetext        : "<em>Congratulations, you\'ve reached the end of the Internet.</em>",
    navSelector     : "#pagination",
    contentSelector : "#notices_primary ol.notices",
    itemSelector    : "#notices_primary ol.notices > li"
    },function(){
        // Reply button and attachment magic need to be set up
        // for each new notice.
        // DO NOT run SN.Init.Notices() which will duplicate stuff.
        $(this).find('.notice').each(function() {
            SN.U.NoticeReplyTo($(this));
            SN.U.NoticeWithAttachment($(this));
        });

        // moving the loaded notices out of their container, and
        // activating masonry for them, if tiled page
		$('#infscr-loading').remove();
		var ids_to_append = Array(); var i=0;
		$.each($('.infscr-pages').children('.notice'),function(){

			// remove dupes
			if($('.threaded-notices > #' + $(this).attr('id')).length > 0) {
				$(this).remove();
				}

			// keep new unique notices
			else {
				ids_to_append[i] = $(this).attr('id');
				i++;
				}
			});
		var loaded_html = $('.infscr-pages').html();
		$('.infscr-pages').remove();

		// no results
		if(loaded_html == '') {
			remove_loading();
			}
		// threaded pages, just append
		else if($('.noticestream').hasClass('threaded-view')) {
			$('.noticestream ol.notices').append(loaded_html);
			}
		// in thumbnail tiles view, it's a little tricky. we need to load the images
		// first, otherwise masonry will overlay images
		else {
			var $live = $('<div>').html(loaded_html);
			var imgCount = $live.find('.quitim-notice > .link > img').length;
			$('.quitim-notice > .link > img',$live).load(function() {
				imgCount--;
				if (imgCount==0) {
					$('.noticestream ol.notices').append($live.children());
					$.each(ids_to_append,function(){
						$('.noticestream ol.notices').masonry('appended',$('#' + this));
						});
					// check for without style attribute and remove (probably dupes)
					$.each($('.noticestream ol.notices > .notice'),function(){
						if(typeof $(this).attr('style') === 'undefined' || $(this).attr('style') === 'false') {
							$(this).remove();
							}
						});
					}
				});

			}


    });
});




// show profile options for other peoples profiles
$('#quitimuserstream.user_in #topright').on('click',function(){
	$('#quitimuserstream:not(.me) .account_profile_block .entity_actions').show();
	});

// show my settings for my profile
$('#quitimuserstream.user_in.me #topright').on('click',function(){

	});

// when finished loading
$(window).bind("load", function() {
	// show thumbnails and apply masonry tiling
	$('.noticestream.thumbnail-view ol.notices').masonry({transitionDuration: 0})
	$('.noticestream.thumbnail-view').css('opacity','1');

	// trigger a scroll to activate infinitescroll...
	$(document).trigger('scroll');
	});

// switch between thumb/threaded view on profiles
$('#set-thumbnail-view').on('click',function(e){
	if(!$('#usernotices').hasClass('thumbnail-view')) {
		$('#usernotices').removeClass('threaded-view');
		$('#usernotices').addClass('thumbnail-view');
		$('.noticestream ol.notices').masonry({transitionDuration: 0});
		$('.noticestream.thumbnail-view').css('opacity','1');
		}
	});
$('#set-threaded-view').on('click',function(e){
	if(!$('#usernotices').hasClass('threaded-view')) {
		$('.noticestream ol.notices').masonry('destroy');
		$('#usernotices').removeClass('thumbnail-view');
		$('#usernotices').addClass('threaded-view');
		}
	});

// display the bottom loading animation
function display_loading() {
	$('#wrap').append('<div id="clock-container"><div class="loader1"></div></div>');
	}
function remove_loading() {
	$('#clock-container').remove();
	}
function display_overlay_loading() {
	$('#spinner-overlay').append('<div id="clock-container"><div class="loader1"></div></div>');
	$('#spinner-overlay').show();
	}
function remove_overlay_loading() {
	$('#spinner-overlay #clock-container').remove();
	$('#spinner-overlay').hide();
	}

// update page with ajax when clicking the refresh spinner
$('#refresh').on('click', function(){
	$(this).addClass('spinning');
	$('#content_inner').load(document.URL + ' #usernotices', function(){
		SN.U.NoticeInlineReplySetup(); // activate the inline replies again
		$('#refresh').removeClass('spinning');
		$(document).scrollTop(0);
		$('.noticestream.thumbnail-view ol.notices').masonry({transitionDuration: 0})
		$('.noticestream.thumbnail-view').css('opacity','1');
		// trigger a scroll to activate infinitescroll...
		$(document).trigger('scroll');
		});
	});

// if a notice is liked we update the fave list
$('body').on('DOMNodeInserted', '.notice-options', function(e) {
	if ($(e.target).attr('class') === 'form_disfavor ajax' || $(e.target).attr('class') === 'form_favor ajax') {
		var notice_URL = $(e.target).closest('.notice').data('local-permalink') + '?ajax=1';
		var dom_path_to_fav_element = '#' + $(e.target).closest('.notice').attr('id') + ' > article > .notice-faves';
		$(e.target).closest('.notice').children('article').children('.notice-faves').load(notice_URL + ' ' + dom_path_to_fav_element, function(){
			$(this).children(':first').unwrap(); // we want to replace fav-container, not load into it
			});
		}
	});
	
// hide comment form when clicking back icon
$('body').on('click','.notice-reply legend', function(){
	$(this).closest('.notice-reply').css('display','none');
	});
// hide comment form when clicking back icon
$('body').on('click','.ui-dialog legend', function(){
	$(this).closest('.ui-dialog').remove();
	});	


// like on doubleclick
window.lastClick = new Object();
window.lastClick.time = 0;
window.lastClick.scrollPos = 0;
$('body').on('click','.quitim-notice img',function(e){

	// not if we're in thumbnail mode
	if($(this).closest('.noticestream').hasClass('thumbnail-view')) {
		return true;
		}

	var timeNow = Date.now();
	var scrollPosNow = $(window).scrollTop();
	var timeSinceLastClick = timeNow - window.lastClick.time;
	var scrollSinceLastClick = Math.abs(scrollPosNow-window.lastClick.scrollPos);
	if(timeSinceLastClick<400 && scrollSinceLastClick<5) {
		$(this).parent().append('<div class="double-tap-heart"></div>');
		$(this).closest('.notice').children('footer').children('.notice-options').find('form.form_favor').find('input.submit').trigger('click');
		setTimeout(function(){
			$('.double-tap-heart').fadeOut(500,function(){
				$('.double-tap-heart').remove();
				});
			},200);
		}
	else {
		window.lastClick.time = timeNow;
		window.lastClick.scrollPos = scrollPosNow;
		}
	});

// like on doubletap
window.lastTouchEnd = new Object();
window.lastTouchEnd.time = 0;
window.lastTouchEnd.scrollPos = 0;
$('body').on('touchend','.quitim-notice img',function(e){
	
	// not if we're in thumbnail mode
	if($(this).closest('.noticestream').hasClass('thumbnail-view')) {
		return true;
		}
	
	var timeNow = Date.now();
	var scrollPosNow = $(window).scrollTop();
	var timeSinceLastTouchEnd = timeNow - window.lastTouchEnd.time;
	var scrollSinceLastTouchEnd = Math.abs(scrollPosNow-window.lastTouchEnd.scrollPos);
	if(timeSinceLastTouchEnd<400 && scrollSinceLastTouchEnd<5) {
		$(this).parent().append('<div class="double-tap-heart"></div>');
		$(this).closest('.notice').children('footer').children('.notice-options').find('form.form_favor').find('input.submit').trigger('click');
		setTimeout(function(){
			$('.double-tap-heart').fadeOut(500,function(){
				$('.double-tap-heart').remove();
				});
			},200);
		}
	else {
		window.lastTouchEnd.time = timeNow;
		window.lastTouchEnd.scrollPos = scrollPosNow;
		}
	});


// check for new notifications
checkForNewNotifications();
var checkForNewNotificationsInterval=window.setInterval(function(){checkForNewNotifications()},window.timeBetweenPolling);
function checkForNewNotifications() {
	if($('body').hasClass('user_in')) {
		// no new requests if requests are very slow, e.g. searches
		if(!$('body').hasClass('loading-notifications')) {
			$('body').addClass('loading-notifications');

			$.ajax({
				url: '/api/quitim/notifications.json',
				dataType: 'json',
				error: function(){
					$('body').removeClass('loading-notifications');
					},
				success: function(data){
					$('body').removeClass('loading-notifications');
					if(data.length == 0) {
						$('#notifications-bubble, #notifications-arrow').remove();
						}
					else {
						if($('#notifications-bubble').length == 0) {
							$('#notifications').append('<div id="notifications-bubble"></div><div id="notifications-arrow"></div>');
							}
						var bubbleContent = '';
						if(typeof data.mention != 'undefined' || typeof data.reply != 'undefined') {
							var sumMentionsAndReplies = 0;
							if(typeof data.mention != 'undefined') {
								sumMentionsAndReplies = parseInt(data.mention,10);
								}
							if(typeof data.reply != 'undefined') {
								sumMentionsAndReplies = sumMentionsAndReplies + parseInt(data.reply,10);
								}
							bubbleContent = bubbleContent + '<div id="noti-comments-mentions">' + sumMentionsAndReplies + '</div>';
							}
						if(typeof data.like != 'undefined') {
							bubbleContent = bubbleContent + '<div id="noti-likes">' + parseInt(data.like,10) + '</div>';
							}
						if(typeof data.follow != 'undefined') {
							bubbleContent = bubbleContent + '<div id="noti-follow">' + parseInt(data.follow,10) + '</div>';
							}
						$('#notifications-bubble').html(bubbleContent);
						}
					}
				});

			// only of logged in and not user stream
			if($('#user-container').css('display') == 'block' && $('.stream-item.user').length==0) {
				var lastId = $('#feed-body').children('.stream-item').not('.temp-post').attr('data-quitter-id-in-stream');
				var addThisStream = window.currentStream;
				getFromAPI(addThisStream + qOrAmp(window.currentStream) + 'since_id=' + lastId,function(data){
					if(data) {
						$('body').removeClass('loading-newer');
						if(addThisStream == window.currentStream) {
							addToFeed(data, false, 'hidden');
							}
						}
					});
				}
			}
		}
	}


/* ·
   ·
   ·   Register
   ·
   · · · · · · · · · · · · · */

$('.register-link').click(function(e){
	e.preventDefault();

	display_overlay_loading();
	// 7 s timeout to annoy human spammers
	setTimeout(function(){
		remove_overlay_loading();
		$('#popup-register').html('<div id="popup-signup" class="front-signup">' +
									  '<div class="signup-input-container"><div id="atsign">@</div><input placeholder="' + window.registerNickname + '" type="text" autocomplete="off" class="text-input" id="signup-user-nickname-step2"><div class="fieldhelp">a-z0-9</div></div>' +
									  '<div class="signup-input-container"><input placeholder="' + window.signUpEmail + '" type="text" autocomplete="off" id="signup-user-email-step2" value=""></div>' +
									  '<div class="signup-input-container"><input placeholder="' + window.registerHomepage + '" type="text" autocomplete="off" class="text-input" id="signup-user-homepage-step2"></div>' +
									  '<div class="signup-input-container"><input placeholder="' + window.registerBio + '" type="text"  autocomplete="off" class="text-input" id="signup-user-bio-step2"></div>' +
									  '<div class="signup-input-container"><input placeholder="' + window.loginPassword + '" type="password" class="text-input" id="signup-user-password1-step2" value=""><div class="fieldhelp">>5</div></div>' +
									  '<div class="signup-input-container"><input placeholder="' + window.registerRepeatPassword + '" type="password" class="text-input" id="signup-user-password2-step2"></div>' +
									  '<div class="signup-input-container" id="terms">' +  window.licenseText + '</div>' +
									  '<button id="signup-btn-step2" class="signup-btn disabled" type="submit">' + window.signUpButtonText + '</button>' +
								   '</div>');
		$('#popup-register').show();

		// ask api if nickname is ok, if no typing for 1 s
		$('#signup-user-nickname-step2').on('keyup',function(){
			clearTimeout(window.checkNicknameTimeout);
			if($('#signup-user-nickname-step2').val().length>1 && /^[a-zA-Z0-9]+$/.test($('#signup-user-nickname-step2').val())) {
				window.checkNicknameTimeout = setTimeout(function(){
					$.get(window.checkNicknameUrl + '?nickname=' + encodeURIComponent($('#signup-user-nickname-step2').val()),function(data){
						if(data==0) {
							$('#signup-user-nickname-step2').addClass('nickname-taken');
							$('#signup-user-password2-step2').trigger('keyup'); // revalidates
							}
						else {
							$('#signup-user-nickname-step2').removeClass('nickname-taken');
							$('#signup-user-password2-step2').trigger('keyup');
							}
						});
					},1000);
				}
			else {
				$('.spinner-wrap').remove();
				}
			});


		// validate on keyup
		$('#popup-register input').on('keyup',function(){
			if(validateRegisterForm($('#popup-register'))) {
				if(!$('#signup-user-nickname-step2').hasClass('nickname-taken')) {
					$('#signup-btn-step2').removeClass('disabled');
					}
				else {
					$('#signup-btn-step2').addClass('disabled');
					}
				}
			else {
				$('#signup-btn-step2').addClass('disabled');
				}
			});
		$('#popup-register input').trigger('keyup');

		// submit on enter
		$('input#signup-user-name-step2,input#signup-user-email-step2,input#signup-user-password1-step2, input#signup-user-password2-step2').keyup(function(e){
			if(e.keyCode==13) {
				$('#signup-btn-step2').trigger('click');
				}
			});

		$('#signup-btn-step2').click(function(){
			if(!$('#signup-btn-step2').hasClass('disabled')) {
				$('#popup-register input,#popup-register button').addClass('disabled');
				display_overlay_loading();
				$.ajax({ url: window.registerApiUrl,
					type: "POST",
					data: {
						postRequest: 	'account/register.json',
						nickname: 		$('#signup-user-nickname-step2').val(),
						email: 			$('#signup-user-email-step2').val(),
						homepage: 		$('#signup-user-homepage-step2').val(),
						bio: 			$('#signup-user-bio-step2').val(),
						password: 		$('#signup-user-password1-step2').val(),
						confirm: 		$('#signup-user-password2-step2').val(),
						cBS: 			window.cBS,
						cBSm: 			window.cBSm
						},
					dataType:"json",
					error: function(data){ console.log('error'); console.log(data); alert('Try again! ' + $.parseJSON(data.responseText).error); },
					success: function(data) {
						remove_overlay_loading();
							 $('input#nickname').val($('#signup-user-nickname-step2').val());
							 $('input#password').val($('#signup-user-password1-step2').val());
							 $('#submit').trigger('click');
							 $('#popup-register').remove();
						 }
					});
				}
			});

		// reactivate register form on popup close
		$('#popup-register').on('remove',function(){
			$('.front-signup input, .front-signup button').removeAttr('disabled');
			$('.front-signup input, .front-signup button').removeClass('disabled');
			});
		},7000);
	});
// submit on enter
$('input#signup-user-name,input#signup-user-email,input#signup-user-password').keyup(function(e){
	if(e.keyCode==13) {
		$('#signup-btn-step1').trigger('click');
		}
	});

/* ·
   ·
   ·   Checks if register form is valid
   ·
   ·   @returns true or false
   ·
   · · · · · · · · · */

function validateRegisterForm(o) {

	var nickname 	= o.find('#signup-user-nickname-step2');
	var email 		= o.find('#signup-user-email-step2');
	var homepage 	= o.find('#signup-user-homepage-step2');
	var bio 		= o.find('#signup-user-bio-step2');
	var password1 	= o.find('#signup-user-password1-step2');
	var password2 	= o.find('#signup-user-password2-step2');
	var passwords 	= o.find('#signup-user-password1-step2,#signup-user-password2-step2');

	var allFieldsValid = true;

	if(nickname.val().length>1 && /^[a-zA-Z0-9]+$/.test(nickname.val())) {
		nickname.removeClass('invalid'); } else { nickname.addClass('invalid'); if(allFieldsValid)allFieldsValid=false; }

	if(/^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(email.val())) {
		email.removeClass('invalid'); } else { email.addClass('invalid'); if(allFieldsValid)allFieldsValid=false; }

	if($.trim(homepage.val()).length==0 || /^(ftp|http|https):\/\/[^ "]+$/.test(homepage.val())) {
		homepage.removeClass('invalid'); } else { homepage.addClass('invalid'); if(allFieldsValid)allFieldsValid=false; }

	if(bio.val().length < 1000) {
		bio.removeClass('invalid'); } else { bio.addClass('invalid'); if(allFieldsValid)allFieldsValid=false; }

	if(password1.val().length>5 && password2.val().length>5 && password1.val() == password2.val()) {
		passwords.removeClass('invalid'); } else { passwords.addClass('invalid'); if(allFieldsValid)allFieldsValid=false; }

	return allFieldsValid;
	}



// camera
$('#camera').on('click',function(){
	$('input:file').click(function(){ $(this).one('change',function(e){ // trick to make the change event only fire once when selecting a file
		renderFileInput2(e);
		})});

	// trigger click for firefox
	if(navigator.userAgent.toLowerCase().indexOf('firefox') > -1) {
		$('#input_form_status .notice_data-attach').trigger('click');
		}
	// other browsers
	else {
		var evt = document.createEvent("HTMLEvents");
		evt.initEvent("click", true, true);
		$('#input_form_status .notice_data-attach')[0].dispatchEvent(evt);
		}
	});


// load image from file input
function renderFileInput2(e) {

	window.filterCache = new Object();

	// show container
	$('body').prepend('<div id="image-preview"></div>');
	$('#image-preview').show();

	// get orientation
	loadImage.parseMetaData(e.target.files[0], function (data) {
		if (data.exif) {
			var orientation = data.exif.get('Orientation');
			}
		else {
			var orientation = 1;
			}

		// create one 1000px wide canvas image
		loadImage(e.target.files[0],
				function (img) {
					var appendedImg = document.getElementById('image-preview').appendChild(img);
					appendedImg.setAttribute('id','hires_image');
					},
				{ maxWidth: 800,
				  maxHeight: 800,
				  canvas:true,
				  orientation: orientation } // Options
			);

		// create one 320px wide canvas image
		loadImage(e.target.files[0],
				function (img) {

					var appendedImg = document.getElementById('image-preview').appendChild(img);
					appendedImg.className = 'filtered-image';
					appendedImg.setAttribute('id','image-normal');

					// show toolbar
					$('#image-preview').append('<div id="image-nav"><div id="close-image">close</div><div id="submit-image">submit</div></div>\
					<div id="image-toolbar-extras">\
					<div id="vignette" class="toolbar-button-extra"></div>\
					<div id="exposure-contrast" class="toolbar-button-extra"></div>\
					</div>\
					<div id="image-toolbar">\
					<div class="filter active" id="filter-normal" data-filter="normal"><div class="filter-thumb"></div>Normal</div>\
					<div class="filter" id="filter-luddite" data-filter="luddite"><div class="filter-thumb"></div>Luddite</div>\
					<div class="filter" id="filter-che" data-filter="che"><div class="filter-thumb"></div>Che</div>\
					<div class="filter" id="filter-pigasus" data-filter="pigasus"><div class="filter-thumb"></div>Pigasus</div>\
					<div class="filter" id="filter-punk" data-filter="punk"><div class="filter-thumb"></div>Punk</div>\
					<div class="filter" id="filter-solanas" data-filter="solanas"><div class="filter-thumb"></div>Solanas</div>\
					<div class="filter" id="filter-redstar" data-filter="redstar"><div class="filter-thumb"></div>Red Star</div>\
					<div class="filter" id="filter-bourgeoisie" data-filter="bourgeoisie"><div class="filter-thumb"></div>Bourgeoisie</div>\
					<div class="filter" id="filter-blackcat" data-filter="blackcat"><div class="filter-thumb"></div>Black Cat</div>\
					<div class="filter" id="filter-matrix" data-filter="matrix"><div class="filter-thumb"></div>Matrix</div>\
					<div class="filter" id="filter-armchair" data-filter="armchair"><div class="filter-thumb"></div>Armchair</div>\
					<div class="filter" id="filter-postcolonial" data-filter="postcolonial"><div class="filter-thumb"></div>Postcolonial</div>\
					<div class="filter" id="filter-utopia" data-filter="utopia"><div class="filter-thumb"></div>Utopia</div>\
					<div class="filter" id="filter-queer" data-filter="queer"><div class="filter-thumb"></div>Queer</div>\
					<div class="filter" id="filter-lazydays" data-filter="lazydays"><div class="filter-thumb"></div>Lazy Days</div>\
					<div class="filter" id="filter-thespectacle" data-filter="thespectacle"><div class="filter-thumb"></div>The Spectacle</div>\
					<div class="filter" id="filter-buddha" data-filter="buddha"><div class="filter-thumb"></div>Buddha</div>\
					</div>');

					},
				{ maxWidth: 320,
				  canvas:true,
				  orientation: orientation } // Options
			);
		});
	}


// filters
Filtrr2.fx('normal', function(p) {
    this.render();
	});
Filtrr2.fx('normalExposureContrast', function(p) {
    this.contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255}).render();
	});
Filtrr2.fx('luddite', function(p) {
    this.saturate(-95).sepia().saturate(-50)
    .render();
	});
Filtrr2.fx('ludditeExposureContrast', function(p) {
    this.saturate(-95).sepia().saturate(-50)
    .contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
    .render();
	});
Filtrr2.fx('che', function(p) {
    this.blur('simple').posterize(4).saturate(-95)
    .render();
	});
Filtrr2.fx('cheExposureContrast', function(p) {
    this.blur('simple').posterize(4).saturate(-95)
    .contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
    .render();
	});
Filtrr2.fx('pigasus', function(p) {
    this.adjust(0,-0.2,-0.2).saturate(-30)
    .render();
	});
Filtrr2.fx('pigasusExposureContrast', function(p) {
    this.adjust(0,-0.2,-0.2).saturate(-30)
    .contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
    .render();
	});
Filtrr2.fx('punk', function(p) {
    this
	.adjust(0,-0.3,-0.7)
    .saturate(-30)
    .contrast(-5)
    .gamma(1.4)
    .expose(5)
    .render();
	});
Filtrr2.fx('punkExposureContrast', function(p) {
    this.contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
	.adjust(0,-0.3,-0.7)
    .saturate(-30)
    .contrast(-5)
    .gamma(1.4)
    .expose(5)
    .render();
	});
Filtrr2.fx('solanas', function(p) {
    this
    .saturate(-30)
    // .contrast(60)
    .expose(-10)
    .render();
	});
Filtrr2.fx('solanasExposureContrast', function(p) {
    this.contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
    .saturate(-30)
    // .contrast(60)
    .expose(-10)
    .render();
	});
Filtrr2.fx('redstar', function(p) {
    this
    .gamma(-2)
	.adjust(0.5,0,0)
    .expose(-20)
    .contrast(-10)
    .render();
	});
Filtrr2.fx('redstarExposureContrast', function(p) {
    this.contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
    .gamma(-2)
	.adjust(0.5,0,0)
    .expose(-20)
    .contrast(-10)
    .render();
	});
Filtrr2.fx('bourgeoisie', function(p) {
    this
    .saturate(-35)
    // .sharpen()
	.adjust(-0.1,-0.05,0)
	.curves({x: 0, y: 0}, {x: 120, y: 100}, {x: 128, y: 140}, {x: 255, y: 255})
    .render();
	});
Filtrr2.fx('bourgeoisieExposureContrast', function(p) {
    this.contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
    .saturate(-35)
    // .sharpen()
	.adjust(-0.1,-0.05,0)
	.curves({x: 0, y: 0}, {x: 120, y: 100}, {x: 128, y: 140}, {x: 255, y: 255})
    .render();
	});
Filtrr2.fx('blackcat', function(p) {
    this
    .saturate(-100)
    // .contrast(20)
	.curves({x: 0, y: 0}, {x: 120, y: 100}, {x: 128, y: 140}, {x: 255, y: 255})
    .render();
	});
Filtrr2.fx('blackcatExposureContrast', function(p) {
    this.contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
    .saturate(-100)
    // .contrast(20)
	.curves({x: 0, y: 0}, {x: 120, y: 100}, {x: 128, y: 140}, {x: 255, y: 255})
    .render();
	});
Filtrr2.fx('matrix', function(p) {
    this
    .brighten(10)
    .expose(15)
	.curves({x: 0, y: 0}, {x: 200, y: 0}, {x: 155, y: 255}, {x: 255, y: 255})
	.saturate(-20)
    .gamma(1.8)
    .render();
	});
Filtrr2.fx('matrixExposureContrast', function(p) {
    this.contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
    .brighten(10)
    .expose(15)
	.curves({x: 0, y: 0}, {x: 200, y: 0}, {x: 155, y: 255}, {x: 255, y: 255})
	.saturate(-20)
    .gamma(1.8)
    .render();
	});
Filtrr2.fx('armchair', function(p) {
    var dup = this.dup().sepia();
    this
    .saturate(-40)
    .gamma(1.1)
	.adjust(-0.2,0,0.1)
	.curves({x: 0, y: 0}, {x: 80, y: 50}, {x: 128, y: 230}, {x: 255, y: 255})
    .layer("softLight", dup)
    .render();
	});
Filtrr2.fx('armchairExposureContrast', function(p) {
    var dup = this.dup().sepia();
    this.contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
    .saturate(-40)
    .gamma(1.1)
	.adjust(-0.2,0,0.1)
	.curves({x: 0, y: 0}, {x: 80, y: 50}, {x: 128, y: 230}, {x: 255, y: 255})
    .layer("softLight", dup)
    .render();
	});
Filtrr2.fx('postcolonial', function(p) {
    var dup = this.dup().fill(244,150,0).saturate(-40);
    this
    .gamma(0.8)
    // .contrast(30)
    .layer("softLight", dup)
	.curves({x: 0, y: 0}, {x: 80, y: 50}, {x: 128, y: 230}, {x: 255, y: 255})
    .render();
	});
Filtrr2.fx('postcolonialExposureContrast', function(p) {
    var dup = this.dup().fill(244,150,0).saturate(-40);
    this.contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
    .gamma(0.8)
    // .contrast(30)
    .layer("softLight", dup)
	.curves({x: 0, y: 0}, {x: 80, y: 50}, {x: 128, y: 230}, {x: 255, y: 255})
    .render();
	});
Filtrr2.fx('utopia', function(p) {
    var dup = this.dup().saturate(20)
    .brighten(20)
    .contrast(20);
    this
	.blur('gaussian')
    .layer("softLight", dup)
    .render();
	});
Filtrr2.fx('utopiaExposureContrast', function(p) {
    var dup = this.dup().saturate(20)
    .brighten(20)
    .contrast(20);
    this.contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
	.blur('gaussian')
    .layer("softLight", dup)
    .render();
	});
Filtrr2.fx('queer', function(p) {
    var dup = this.dup().fill(181,0,134).saturate(-50)
    this
    .layer("softLight", dup)
    .render();
	});
Filtrr2.fx('queerExposureContrast', function(p) {
    var dup = this.dup().fill(181,0,134).saturate(-50)
    this.contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
    .layer("softLight", dup)
    .render();
	});
Filtrr2.fx('lazydays', function(p) {
    var dup = this.dup().saturate(-100).sepia();
    this
    .saturate(-70)
    // .contrast(15)
    .expose(10)
	.adjust(-0.05,-0.05,0)
	.blur('gaussian')
    .sharpen()
    .layer("softLight", dup)
    .render();
	});
Filtrr2.fx('lazydaysExposureContrast', function(p) {
    var dup = this.dup().saturate(-100).sepia();
    this.contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
    .saturate(-70)
    // .contrast(15)
    .expose(10)
	.adjust(-0.05,-0.05,0)
	.blur('gaussian')
    .sharpen()
    .layer("softLight", dup)
    .render();
	});
Filtrr2.fx('thespectacle', function(p) {
    var dup = this.dup().sepia();
    this
    .saturate(-100)
    .expose(10)
	.adjust(0.1,0,0)
    .layer("softLight", dup)
    .render();
	});
Filtrr2.fx('thespectacleExposureContrast', function(p) {
    var dup = this.dup().sepia();
    this.contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
    .saturate(-100)
    .expose(10)
	.adjust(0.1,0,0)
    .layer("softLight", dup)
    .render();
	});
Filtrr2.fx('buddha', function(p) {
    var dup = this.dup().sharpen().saturate(-50).adjust(0.1,0,0);
    this
    .sharpen()
	// .contrast(30)
	.expose(10)
	.adjust(0,0,0.2)
    .layer("multiply", dup)
    .brighten(10)
    .saturate(20)
    .render();
	});
Filtrr2.fx('buddhaExposureContrast', function(p) {
    var dup = this.dup().sharpen().saturate(-50).adjust(0.1,0,0);
    this
    .sharpen()
	// .contrast(30)
	.expose(10)
	.adjust(0,0,0.2)
    .layer("multiply", dup)
    .brighten(10)
    .saturate(20)
    .contrast(20).curves({x: 0, y: 0}, {x: 70, y: 150}, {x: 128, y: 150}, {x: 255, y: 255})
    .render();
	});




// when image is closed we count up this, so new images without page reload gets unique ids
window.imageNumber = 1;

// apply filter to preview image
$('body').on('click','.filter',function(){
	$('.filter').removeClass('active');
	$(this).addClass('active');

	// filtername + extras
	var filter = $(this).attr('data-filter');
	var canvasId = filter + '-' + window.imageNumber;
	if($('#vignette').hasClass('active')) {
		canvasId = canvasId + '-vignette'
		}
	if($('#exposure-contrast').hasClass('active')) {
		filter = filter + 'ExposureContrast'
		canvasId = canvasId + '-exposure-contrast'
		}

	// if we already applied this filter before, just bring to front
	if($('#image-' + canvasId).length>0) {
		$('.filtered-image').css('z-index','1');
		$('#image-' + canvasId).css('z-index','2');
		}
	// if we haven't applied this filter before, apply and bring to front
	else {

		$('#image-preview').prepend('<img id="image-' + canvasId + '" class="filtered-image" src="' + $('#image-normal')[0].toDataURL() + '" />');

		// vignette?
		if($('#vignette').hasClass('active')) {
			var effect = {
				vignette: 0.6,
				lighten: 0.1
				};
			}
		else {
			var effect = {};
			}

		// apply vintagejs filter
		$('#image-' + canvasId).vintage({
			onStop: function() {

				// apply Filtrr2 filter
				Filtrr2('#image-' + canvasId, function() {
					this[filter]().render(function(){

						// remove vintagejs:s img-element and rename filtrr2:s canvas
						$('#image-' + canvasId).remove();
						$('#filtrr2-image-' + canvasId).attr('id','image-' + canvasId);

						// bring to front
						$('.filtered-image').css('z-index','1');
						$('#image-' + canvasId).css('z-index','2');
						});
					});
				}
			}, effect);

		}
	});

// extra filters
$('body').on('click','.toolbar-button-extra',function(){
	$(this).toggleClass('active');
	$('.filter.active').trigger('click');
	});


// hide/show toolbars on doubleclick on canvas
$('body').on('doubletap','.filtered-image',function(){
	if($('#image-toolbar').css('display') == 'none') {
		$('#image-toolbar, #image-toolbar-extras').fadeIn();
		}
	else {
		$('#image-toolbar, #image-toolbar-extras').fadeOut();
		}
	});

// close
$('body').on('click','#close-image',function(){
	window.imageNumber = window.imageNumber+1;
	$('#image-preview').remove();
	$('input:file').unbind('click');
	});

// submit
$('body').on('click','#submit-image',function(){

	display_overlay_loading();
	setTimeout(function(){ // timeout, otherwise loading animation don't show immediately

	// filtername + extras
	var filter = $('.filter.active').attr('data-filter');
	var canvasId = filter + '-' + window.imageNumber;
	if($('#vignette').hasClass('active')) {
		canvasId = canvasId + '-vignette'
		}
	if($('#exposure-contrast').hasClass('active')) {
		filter = filter + 'ExposureContrast'
		canvasId = canvasId + '-exposure-contrast'
		}

	$('#image-preview').prepend('<img id="image-to-submit" class="filtered-image" src="' + $('#hires_image')[0].toDataURL() + '" />');

	// vignette?
	if($('#vignette').hasClass('active')) {
		var effect = {
			vignette: 0.6,
			lighten: 0.1
			};
		}
	else {
		var effect = {};
		}

	// apply vintagejs filter
	$('#image-to-submit').vintage({
		onStop: function() {

			// apply Filtrr2 filter
			Filtrr2('#image-to-submit', function() {
				this[filter]().render(function(){
					$('#input_form_status .notice_data-attach').attr('type','hidden');

					var imgBase64Data = $('#filtrr2-image-to-submit')[0].toDataURL('image/jpeg');

					// some browser, e.g. android, can't create jpegs, so we have to do it (to reduce image size to upload)
					if(imgBase64Data.substring(0,100).indexOf('image/png') > -1) {

						var c = $('#filtrr2-image-to-submit')[0];
						var ctx = c.getContext("2d");
						var imgData = ctx.getImageData(0,0,c.width,c.height);
						var jpeg = new JPEGEncoder();
						var imgBase64Data = jpeg.encode(imgData, 90);
						}

					$('#input_form_status .notice_data-attach').val(imgBase64Data);
					$('.form_notice').submit();
					});
				});
			}
		}, effect);

		},50);

	});

SN.U.addCallback('notice_posted', function (userdata) {
	// reload if this is not a reply
	if(!$('#' + userdata.notice.id).parent().hasClass('threaded-replies')) {
		location.reload(true);
	}
});
