<?php
/* Copyright (C) 2019	Regis Houssin	<regis.houssin@inodbox.com>
 * Copyright (C) 2021   Philippe Grand  <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

if (! defined('ISLOADEDBYSTEELSHEET')) die('Must be call by steelsheet');
// topmenu
if (!isset($conf->global->THEME_ELDY_TOPMENU_BACK1)) {
	$conf->global->THEME_ELDY_TOPMENU_BACK1 = $colorbackhmenu1;
}
$colorbackhmenu1     = empty($user->conf->THEME_ELDY_ENABLE_PERSONALIZED) ? (empty($conf->global->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $conf->global->THEME_ELDY_TOPMENU_BACK1) : (empty($user->conf->THEME_ELDY_TOPMENU_BACK1) ? $colorbackhmenu1 : $user->conf->THEME_ELDY_TOPMENU_BACK1);
?>
/* <style type="text/css" > */
/*
 Dropdown
*/

.open>.updf-dropdown-menu { /*, #topmenu-login-dropdown:hover .dropdown-menu*/
    display: block;
}

.updf-dropdown-menu {
    box-shadow: none;
    border-color: #eee;
}
.updf-dropdown-menu {
    position: absolute;
    top: 100%;
    left: 0;
    z-index: 1000;
    display: none;
    float: left;
    min-width: 160px;
    padding: 5px 0;
    margin: 2px 0 0;
    font-size: 14px;
    text-align: left;
    list-style: none;
    background-color: #fff;
    -webkit-background-clip: padding-box;
    background-clip: padding-box;
    border: 1px solid #ccc;
    border: 1px solid rgba(0,0,0,.15);
    border-radius: 4px;
    -webkit-box-shadow: 0 6px 12px rgba(0,0,0,.175);
    box-shadow: 0 6px 12px rgba(0,0,0,.175);
}



/*
* MENU Dropdown
*/
.login_block.usedropdown .logout-btn {
    display: none;
}

.login_block .open.updfdropdown, .login_block .updfdropdown:hover {
    background: rgba(0, 0, 0, 0.1);
}
.login_block .updf-dropdown-menu {
    position: absolute;
    right: 0;
    left: auto;
    line-height:1.3em;
}
.login_block .updf-dropdown-menu .updf-body {
    border-bottom-right-radius: 4px;
    border-bottom-left-radius: 4px;
}
.updf-body {
    color: #333;
}
.side-nav-vert .updf-menu .updf-dropdown-menu {
    border-top-right-radius: 0;
    border-top-left-radius: 0;
    padding: 1px 0 0 0;
    border-top-width: 0;
    width: 300px;
}
.side-nav-vert .updf-menu .updf-dropdown-menu {
    margin-top: 0;
    border-top-left-radius: 0;
    border-top-right-radius: 0;
}

.side-nav-vert .updf-menu .updf-dropdown-menu > .updf-header {
    height: 175px;
    padding: 10px;
    text-align: center;
    white-space: normal;
}

.dropdown-updf-image {
	font-size: 90px;
    border-radius: 50%;
    vertical-align: middle;
    z-index: 5;
    height: 90px;
    width: 90px;
    /*border: 3px solid;
    border-color: transparent;
    border-color: rgba(255, 255, 255, 0.2);*/
    max-width: 100%;
    max-height :100%;
}

.updf-dropdown-menu > .updf-header {
    background-color: rgb(<?php echo $colorbackhmenu1 ?>);
}

.updf-dropdown-menu > .updf-footer {
    background-color: #f9f9f9;
    padding: 10px;
}

.updf-footer:after {
    clear: both;
}

.updf-dropdown-menu > .updf-body {
    padding: 15px;
    border-bottom: 1px solid #f4f4f4;
    border-top: 1px solid #dddddd;
    white-space: normal;
}

#topmenu-updf-dropdown {
    padding: 0 5px 0 5px;
}
#topmenu-updf-dropdown a:hover {
    text-decoration: none;
}
.atoplogin #updf-dropdown-icon-down, .atoplogin #updf-dropdown-icon-up {
    font-size: 0.7em;
}
.atoplogin #updf-dropdown-icon {
	cursor:pointer;
}

#topmenuupdfmoreinfo-btn {
    display: block;
    text-align: left;
    color:#666;
    cursor: pointer;
}

#topmenuupdfmoreinfo {
    display: none;
    clear: both;
    font-size: 0.95em;
}

.button-top-menu-dropdown {
    display: inline-block;
    padding: 6px 12px;
    margin-bottom: 0;
    font-size: 14px;
    font-weight: 400;
    line-height: 1.42857143;
    text-align: center;
    white-space: nowrap;
    vertical-align: middle;
    -ms-touch-action: manipulation;
    touch-action: manipulation;
    cursor: pointer;
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    background-image: none;
    border: 1px solid transparent;
    border-radius: 4px;
}

.updf-footer .button-top-menu-dropdown {
    color: #666666;
    border-radius: 0;
    -webkit-box-shadow: none;
    -moz-box-shadow: none;
    box-shadow: none;
    border-width: 1px;
    background-color: #f4f4f4;
    border-color: #ddd;
}
