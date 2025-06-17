{*<!--
/* * *******************************************************************************
 * The content of this file is subject to the VTE Custom User Login Page ("License");
 * You may not use this file except in compliance with the License
 * The Initial Developer of the Original Code is VTExperts.com
 * Portions created by VTExperts.com. are Copyright(C)VTExperts.com.
 * All Rights Reserved.
 * ****************************************************************************** */
-->*}
{strip}
    <!DOCTYPE html>
    <html>
    <head>
        <title>{$COMPANY_DETAILS.name}</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!-- for Login page we are added -->
        <link href="libraries/bootstrap/css/bootstrap.min.css" rel="stylesheet">
        <link href="libraries/bootstrap/css/bootstrap-responsive.min.css" rel="stylesheet">
        <link href="libraries/bootstrap/css/jqueryBxslider.css" rel="stylesheet" />
        <link href="layouts/vlayout/modules/Settings/UserLogin/resources/UserLogin.css" rel="stylesheet" />
        <link href="layouts/vlayout/modules/UserLogin/resources/UserLogin.css" rel="stylesheet" />
        <script src="libraries/jquery/jquery.min.js"></script>
        <script src="libraries/jquery/boxslider/jqueryBxslider.js"></script>
        <script src="libraries/jquery/boxslider/respond.min.js"></script>
        <script>
            jQuery(document).ready(function(){
                scrollx = jQuery(window).outerWidth();
                window.scrollTo(scrollx,0);
                slider = jQuery('.bxslider').bxSlider({
                    mode: '$USER_LOGIN_CUSTOM_SLIDE_TYPE$',
                    auto: true,
                    randomStart : false,
                    autoHover: false,
                    controls: false,
                    pager: false,
                    speed: '$USER_LOGIN_CUSTOM_SLIDE_SPEED$',
                    easing: '$USER_LOGIN_CUSTOM_SLIDE_EASING$',
                    onSliderLoad: function() {

                    }
                });
            });
        </script>
    </head>
    <body>
    <div class="vte-login-container">
        <div class="logo">
            <img src="$USER_LOGIN_CUSTOM_LOGO$" />
        </div>
        <div class="row-fluid">
            <div class="span6 slideshow">
                <div class="carousal-container">
                    <ul class="bxslider">
                        $USER_LOGIN_CUSTOM_SLIDE_IMAGES$
                    </ul>
                </div>
            </div>
            <div class="span6 login-area">

                <div class="span12 site-info">
                    <h1 class="login-header">$USER_LOGIN_CUSTOM_HEADER$</h1>
                    <p>$USER_LOGIN_CUSTOM_DESCRIPTION$</p>
                </div>
                <div class="span12 login-box" id="loginDiv">
                    <form class="form-horizontal login-form" action="index.php?module=Users&action=Login" method="POST">
                        {if isset($smarty.request.error)}
                            <div class="row alert alert-error">
                                <p>Invalid username or password.</p>
                            </div>
                        {/if}
                        {if isset($smarty.request.fpError)}
                            <div class="row alert alert-error">
                                <p>Invalid Username or Email address.</p>
                            </div>
                        {/if}
                        {if isset($smarty.request.status)}
                            <div class="row alert alert-success">
                                <p>Mail has been sent to your inbox, please check your e-mail.</p>
                            </div>
                        {/if}
                        {if isset($smarty.request.statusError)}
                            <div class="row alert alert-error">
                                <p>Outgoing mail server was not configured.</p>
                            </div>
                        {/if}
                        <div class="row">
                            <div class="span6 username">
                                <input type="text" id="username" name="username" placeholder="Username" value="">
                            </div>
                            <div class="span6 password">
                                <input type="password" id="password" name="password" placeholder="Password" value="">
                            </div>
                        </div>
                        <div class="row control-group signin-button pull-right">
                            <div class="span12" id="forgotPassword">
                                <a>Forgot Password ?</a>&nbsp;&nbsp;&nbsp;
                                <button type="submit" class="btn btn-primary sbutton">Sign in</button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="span12 login-box hide" id="forgotPasswordDiv">
                    <form class="form-horizontal login-form" action="forgotPassword.php" method="POST">
                        <div class="row">
                            <h3 class="forgot-password">Forgot Password</h3>
                        </div>
                        <div class="row">
                            <div class="span6 username">
                                <input type="text" id="user_name" name="user_name" placeholder="Username">
                            </div>
                            <div class="span6 password">
                                <input type="text" id="emailId" name="emailId"  placeholder="Email">
                            </div>
                        </div>
                        <div class="row control-group signin-button">
                            <div class="" id="backButton">
                                <input type="button" class="btn btn-back sbutton pull-left" value="Back">
                                <input type="submit" class="btn btn-primary sbutton pull-right" value="Submit" name="retrievePassword">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="span12 login-more-info vte-user-login">
                    <div class="row">
                        <div class="span4 social">
                            $USER_LOGIN_CUSTOM_SOCIAL_ICONS$
                        </div>
                        <div class="span6 copy-right">
                            <small>$USER_LOGIN_CUSTOM_COPYRIGHT$</small>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    </body>
    <script>
        jQuery(document).ready(function(){
            jQuery("#forgotPassword a").click(function() {
                jQuery("#loginDiv").hide();
                jQuery("#forgotPasswordDiv").show();
            });

            jQuery("#backButton .btn-back").click(function() {
                jQuery("#loginDiv").show();
                jQuery("#forgotPasswordDiv").hide();
            });

            jQuery("input[name='retrievePassword']").click(function (){
                var username = jQuery('#user_name').val();
                var email = jQuery('#emailId').val();

                var email1 = email.replace(/^\s+/,'').replace(/\s+$/,'');
                var emailFilter = /^[^@]+@[^@.]+\.[^@]*\w\w$/ ;
                var illegalChars= /[\(\)\<\>\,\;\:\\\"\[\]]/ ;

                if(username == ''){
                    alert('Please enter valid username');
                    return false;
                } else if(!emailFilter.test(email1) || email == ''){
                    alert('Please enater valid email address');
                    return false;
                } else if(email.match(illegalChars)){
                    alert( "The email address contains illegal characters.");
                    return false;
                } else {
                    return true;
                }

            });
        });
    </script>
    </html>
{/strip}
