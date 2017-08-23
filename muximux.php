<?php
/*
* DO NOT CHANGE THIS FILE!
*/
ini_set("log_errors", 1);
ini_set('max_execution_time', 300);
error_reporting(E_ERROR);
ini_set("error_log", $errorLogPath);
date_default_timezone_set((date_default_timezone_get() ? date_default_timezone_get() : "America/Chicago"));
$configPath = getenv('CONFIG_PATH') ? getenv('CONFIG_PATH') : dirname(__FILE__) . '/config/';
$logPath = getenv('LOG_PATH') ? getenv('LOG_PATH') : dirname(__FILE__) . '/log/';
defined("CONFIG") ? null : define('CONFIG', $configPath.'settings.ini.php');
defined("CONFIGEXAMPLE") ? null : define('CONFIGEXAMPLE', dirname(__FILE__).'/config/settings.ini.php-example');
defined("LOGPATH") ? null : define('LOGPATH',$logPath.'muximux.log');
require_once dirname(__FILE__) . '/vendor/autoload.php';
require_once dirname(__FILE__) . '/util.php';
require_once 'iconindex.php';


// Check if this is our first run and do some things.
if(!file_exists(CONFIG)){
	if (file_exists('settings.ini.php')) {
		if (copy('settings.ini.php',CONFIG)) {
			unlink('settings.ini.php');
		}
	} else {
		copy(CONFIGEXAMPLE, CONFIG);
	}
    checksetSHA();
}

$config = new Config_Lite(CONFIG);
$_SESSION['secret'] = $config->get("general","secret",false);
if (!$_SESSION['secret']) {
	$_SESSION['secret'] = createSecret();
}

if (isset($_POST['function']) && isset($_POST['secret'])) {
	if ($_POST['secret'] == file_get_contents(SECRET)) write_ini();
} 

// Create a secret for communication to the server
function createSecret() {
	$config = new Config_Lite(CONFIG);
	write_log("Creating server secret.");
    $text = uniqid("muximux-", true);
    $config->set("general","secret",$text);
    saveConfig($config);
    return $text;
}

// Save our settings on submit
function write_ini()
{
    $config = new Config_Lite(CONFIG);
    $oldHash = getPassHash();
    $oldBranch = getBranch();
    $terminate = false;
    $authentication = $config->getBool('general','authentication',false);
	
    // Double check that a username post didn't sneak through
    foreach ($_POST as $parameter => $value) {
    	$splitParameter = explode('_-_', $parameter);
	if ($splitParameter[1] == "username") {
	    die;
	}
    }
	unlink(CONFIG);
    $config = new Config_Lite(CONFIG);
    foreach ($_POST as $parameter => $value) {
        $splitParameter = explode('_-_', $parameter);
        $value = (($value == "on") ? "true" : $value );
		switch ($splitParameter[1]) {
			case "password":
				if ($value != $oldHash) {
					write_log('Successfully updated password.','I');
					$value = password_hash($value, PASSWORD_BCRYPT);
					$terminate = true;
				}
			break;
			case "authentication":
			    if ($value != $authentication) {
					$terminate = true;
				}
			break;
			case "theme":
			    $value = strtolower($value);
			break;
			case "branch":
				if ($value != $oldBranch) {
					$config->set('settings','branch_changed',true);
					$config->set('settings','sha','00');
				} else {
					$config->set('settings','branch_changed',false);
				}
			break;
		}
        
        if ($parameter !== 'function' && $parameter !== 'secret')$config->set($splitParameter[0], $splitParameter[1], $value);
    }
    // save object to file
    saveConfig($config);
    if ($terminate) {
        session_start();
        session_destroy();
    }
}

// Parse settings.php and create the Muximux elements
function parse_ini()
{
	$config = new Config_Lite(CONFIG);
	checksetSHA();
    fetchBranches(false);
    $branchArray = getBranches();
    $branchList = "";
    $css = getThemeFile();
    $tabColorEnabled = $config->getBool('general', 'tabcolor', false);
    $updatePopup = $config->getBool('general', 'updatepopup', false);
    $mobileOverride = $config->getBool('general', 'mobileoverride', false);
    $cssColor = ((parseCSS($css,'.colorgrab','color') != false) ? parseCSS($css,'.colorgrab','color') : '#FFFFFF');
    $themeColor = $config->get('general','color',$cssColor);
    $autoHide = $config->getBool('general', 'autohide', false);
    $splashScreen = $config->getBool('general', 'splashscreen', false);
    $userName = $config->get('general', 'userNameInput', 'admin');
    $passHash = $config->get('general', 'password', 'Muximux');
    $authentication = $config->get('general', 'authentication', 'off');
	$authSettingsOnly = $config->getBool('general', 'autSettingsOnly', false);
    $rss = $config->getBool('general', 'rss', false);
	$rssUrl = $config->get('general','rssUrl','https://www.wired.com/feed/');
    $myBranch = getBranch();
	
    foreach ($branchArray as $branchName => $shaSum ) {
        $branchList .= "
                                <option value='".$branchName."' ".(($myBranch == $branchName) ? 'selected' : '' ).">". $branchName ."</option>";
    }
    $title = $config->get('general', 'title', 'Muximux - Application Management Console');
    $pageOutput = "<form class='form-inline'>
	
						<div class='applicationContainer card generalContainer' style='cursor:default;'>
                        <h3>General</h3>
                        <div class='row justify-content-center'>
	                        <div class='appDiv form-group'>
	                            <label for='titleInput' class='col-xs-6 col-sm-4 col-lg-3 control-label left-label'>Main Title: </label>
	                            <div class='appInput col-xs-6 col-sm-8 col-md-4 col-lg-8'>
									<input id='titleInput' data-section='general' data-attribute='title' class='form-control form-control-sm settingInput general_-_title' name='general_-_title' value='" . $title . "'>
								</div>
	                        </div>
	                        <div class='appDiv form-group'>
								<label for='branch'  class='col-xs-6 col-sm-4 col-lg-3 control-label left-label'>Git branch: </label>
								<div class='appInput col-xs-6 col-sm-2 col-md-4 col-lg-6'>
									<select id='branch' data-section='general' data-attribute='branch' class='form-control form-control-sm settingInput' name='general_-_branch'>".
										$branchList ."
									</select>
								</div>
	                        </div>
							<div class='appDiv form-group'>
								<label for='theme' class='col-xs-6 col-sm-4 col-lg-3 control-label left-label'>Theme: </label>
								<div class='appInput col-xs-6 col-sm-2 col-md-4 col-lg-7'>
									<select id='theme' data-section='general' data-attribute='theme' class='form-control form-control-sm general_-_value settingInput' name='general_-_theme'>".
										listThemes() . "
									</select>
								</div>
							</div>
							<div class='appDiv form-group'>
								<label for='general_-_color' class='control-label left-label col-xs-6 col-sm-4 col-lg-3'>Color:</label>
								<div class='appInput col-xs-6 col-sm-8 col-md-4 col-lg-3'>
									<input id='general_-_default' data-section='general' data-attribute='color' class='appsColor col-xs-6 col-sm-2 generalColor general_-_color settingInput' value='" .$themeColor."' name='general_-_color'>
								</div>
	                        </div>
	                        </div>
	                        <div class='row justify-content-center'>
	                        	<div class='col-xs-6 col-md-2 med-gutters btn-group' data-toggle='buttons'>
		                            <label for='updatepopup' data-toggle='tooltip' data-placement='top' title='Enable this to receive notifications of updates to Muximux.' class='btn btn-primary btn-sm btn-block". ($updatePopup ? ' active' : '') ."'>
										<input id='updatepopup' data-section='general' data-attribute='updatepopup' name='general_-_updatepopup' type='checkbox'". ($updatePopup ? ' checked' : '') .">Update Alerts
									</label>
		                        </div>
								<div class='col-xs-6 col-md-2 med-gutters btn-group' data-toggle='buttons'>
		                            <label for='splashscreen' data-toggle='tooltip' data-placement='top' title='Show the splash screen when Muximux loads.' class='btn btn-primary btn-sm btn-block". ($splashScreen ? ' active' : '') ."'>
										<input id='splashscreen' data-section='general' data-attribute='splashscreen' class='settingInput' name='general_-_splashscreen' type='checkbox'".($splashScreen ? ' checked' : '') .">Splash Screen
									</label>
		                        </div>
								<div class='col-xs-6 col-md-2 med-gutters btn-group' data-toggle='buttons'>
		                            <label for='mobileoverride' class='btn btn-primary btn-sm btn-block". ($mobileOverride ? ' active' : '') ."'>
		                                <input id='mobileoverride' data-section='general' data-attribute='mobileoverride' class='settingInput' name='general_-_mobileoverride' type='checkbox'".($mobileOverride ? ' checked' : '').">Mobile Override
									</label>
		                        </div>
		                        <div class='col-xs-6 col-md-2 med-gutters btn-group' data-toggle='buttons'>
		                            <label for='tabcolor' class='btn btn-primary btn-sm btn-block". ($tabColorEnabled ? ' active' : '') ."'>
										<input id='tabcolor' data-section='general' data-attribute='tabcolor' class='settingInput' name='general_-_tabcolor' type='checkbox'" . ($tabColorEnabled ? ' checked' : '').">App Colors
									</label>
		                        </div>
								<div class='col-xs-6 col-md-2 med-gutters btn-group' data-toggle='buttons'>
		                            <label for='autohide' class='btn btn-primary btn-sm btn-block". ($autoHide ? ' active' : '') ."'>
										<input id='autohide' data-section='general' data-attribute='autohide' class='settingInput' name='general_-_autohide' type='checkbox'".($autoHide ? ' checked' : '').">Auto-hide Bar
									</label>
								</div>
		                        <div class='col-xs-6 col-md-2 med-gutters btn-group' data-toggle='buttons'>
		                        	<label for='rss' class='btn btn-primary btn-sm btn-block". ($rss ? ' active' : '') ."'>
		                        		<input id='rss' data-section='general' data-attribute='rss' class='settingInput' name='general_-_rss' type='checkbox' ".($rss ? 'checked' : ''). ">Splash RSS
									</label>
								</div>
							</div>
							<div class='row justify-content-center'>
								<span id='authHeader'>Authorization</span><br>
								<div class='col-xs-12 col-md-4 med-gutters btn-group' data-toggle='buttons'>
									<label class='btn btn-primary".($authentication === "off" ? " active":"")."'>
										<input type='radio' data-section='general' data-attribute='authentication' class='settingInput' name='auths' id='off' autocomplete='off'".($authentication === "off" ? " checked":"").">None
									</label>
									<label class='btn btn-primary".($authentication === "login" ? " active":"")."'>
										<input type='radio' data-section='general' data-attribute='authentication' class='settingInput' name='auths' id='login' autocomplete='off'".($authentication === "login" ? " checked":"").">Login
									</label>
									<label class='btn btn-primary".($authentication === "settingsOnly" ? " active":"")."'>
										<input type='radio' data-section='general' data-attribute='authentication' class='settingInput' name='auths' id='settingsOnly' autocomplete='off'".($authentication === "settingsOnly" ? " checked":"").">Settings
									</label>
		                        </div>
							</div>
							<div class='row justify-content-center'>
								<div class='userinput appDiv form-group rssUrlGroup'>
									<label for='rssUrl' class='col-xs-4 control-label right-label'>Feed Url: </label>
										<div class='col-xs-7 col-sm-5 col-md-3 col-lg-8'>
										<input id='rssUrl' data-section='general' data-attribute='rssUrl' class='form-control settingInput' general_-_value' name='general_-_rssUrl' value='" . $rssUrl . "'>
									</div>
								</div>
								<div class='inputdiv appDiv form-group'>
									<div class='userinput appDiv form-group'>
										<label for='userNameInput' class='col-xs-6 col-lg-6 control-label left-label'>Username:</label>
											<div class='col-xs-6 col-lg-5'>
											<input id='userNameInput' data-section='general' data-attribute='userNameInput' class='form-control settingInput' general_-_value' name='general_-_userNameInput' value='" . $userName . "'>
										</div>
									</div>
								</div>
								<div class='inputdiv appDiv form-group'>
									<div class='userinput appDiv form-group'>
										<label for='password' class='col-xs-6 col-lg-5 control-label left-label'>Password: </label>
										<div class='col-xs-6 col-lg-5'>
											<input id='password' data-section='general' data-attribute='password' type='password' autocomplete='new-password' class='form-control settingInput' general_-_value' name='general_-_password' value='" . $passHash . "'>
										</div>
									</div>
								</div>
							</div>
	                    </div>
                	
					<input type='hidden' class='settings_-_value' name='settings_-_enabled' value='true'>
					<input type='hidden' class='settings_-_value' name='settings_-_default' value='false'>
					<input type='hidden' class='settings_-_value' name='settings_-_name' value='Settings'>
					<input type='hidden' class='settings_-_value' name='settings_-_url' value='muximux.php'>
					<input type='hidden' class='settings_-_value' name='settings_-_landingpage' value='false'>
					<input type='hidden' class='settings_-_value' name='settings_-_icon' value='muximux-cog'>
					<input type='hidden' class='settings_-_value' name='settings_-_dd' value='true'>
					<div id='sortable'>";
    foreach ($config as $section => $name) {
        if (is_array($name) && $section != "settings" && $section != "general") {
            $name = $config->get($section, 'name', '');
            $url = $config->get($section, 'url', 'http://www.plex.com');
            $color = $config->get($section, 'color', '#000');
            $icon = $config->get($section, 'icon', 'muximux-play');
	        $icon = str_replace('fa-','muximux-',$icon);
	        $scale = $config->get($section, 'scale', 1) * 100;
            $default = $config->getBool($section, 'default', false);
            $enabled = $config->getBool($section, 'enabled', true);
            $landingpage = $config->getBool($section, 'landingpage', false);
            $dd = $config->getBool($section, 'dd', false);
            $pageOutput .= "
						<div class='applicationContainer card' id='" . $section . "'>
							<span class='bars fa muximux-bars sortable-handle'></span>
							<div class='row justify-content-center'>
							<div class='appDiv form-group'>
									<label for='" . $section . "_-_url' class='col-xs-6 col-sm-4 control-label left-label'>Name: </label>
									<div class='col-xs-6 col-sm-8 col-md-4 col-lg-8'>
										<input data-section='" . $section . "' data-attribute='name' class='form-control form-control-sm settingInput " . $section . "_-_value' name='" . $section . "_-_name' value='" . $section . "'>
									</div>
								</div>
								<div class='appDiv form-group'>
									<label for='" . $section . "_-_url' class='col-xs-6 col-sm-4 control-label left-label'>URL: </label>
									<div class='col-xs-6 col-sm-8 col-md-4 col-lg-8'>
										<input data-section='" . $section . "' data-attribute='url' class='form-control form-control-sm settingInput " . $section . "_-_value' name='" . $section . "_-_url' value='" . $url . "'>
									</div>
								</div>
								<div  class='appDiv form-group col-lg-3'>
									<label for='" . $section . "_-_scale' class='col-xs-6 col-sm-4 control-label col-form-label left-label'>Zoom: </label>
									<div class='slider-outer col-xs-6 col-md-4 col-lg-8'>
										<input data-section='" . $section . "' data-attribute='scale' class='sliderInput' id='" . $section . "_-_scale' data-slider-id='" . $section . "_-_scale' type='text' data-slider-min='0' data-slider-max='100' data-slider-step='10' data-slider-value='".$scale."'/>
									</div>
								</div>
								<div class='appDiv form-group'>
									<label for='" . $section . "_-_icon' class='col-xs-6 col-sm-4 control-label left-label'>Icon: </label>
									<input data-section='" . $section . "' data-attribute='icon' data-bv-notempty='true' class='iconpicker settingInput' data-bv-notempty-message='You must pick a font' type='text' name='" . $section . "_-_icon' id='fip_1' value='".$icon."' />
								</div>
								<div class='appDiv form-group colorDiv'>
									<label for='" . $section . "_-_color' class='col-xs-6 col-sm-4 col-lg-3 control-label color-label left-label'>Color:</label>
									<div class='appInput col-xs-6 col-sm-8 col-md-4 col-lg-3'>
										<input data-section='" . $section . "' data-attribute='color' id='" . $section . "_-_color' class='form-control form-control-sm appsColor settingInput " . $section . "_-_color' value='" . $color . "' name='" . $section . "_-_color'>
									</div>
								</div>
							</div>
							<div class='row justify-content-center'>
								<div class='col-xs-6 col-md-2 med-gutters btn-group' data-toggle='buttons'>
		                        	<label for='" . $section . "_-_enabled' class='btn btn-primary btn-sm btn-block ".($enabled ? 'active' : ''). "'>
										<input data-section='" . $section . "' data-attribute='enabled' type='checkbox' class='settingInput' id='" . $section . "_-_enabled' name='" . $section . "_-_enabled'".($enabled ? ' checked' : '') .">Enabled
									</label>
								</div>
								<div class='col-xs-6 col-md-2 med-gutters btn-group' data-toggle='buttons'>
		                        	<label for='" . $section . "_-_landingpage' class='btn btn-primary btn-sm btn-block ".($landingpage ? 'active' : ''). "'>Splash Item
										<input data-section='" . $section . "' data-attribute='landingpage' type='checkbox' class='settingInput' id='" . $section . "_-_landingpage' name='" . $section . "_-_landingpage'".($landingpage ? ' checked' : '') .">
									</label>
								</div>
								<div class='col-xs-6 col-md-2 med-gutters btn-group' data-toggle='buttons'>
		                        	<label for='" . $section . "_-_dd' class='btn btn-primary btn-sm btn-block".($dd ? 'active' : ''). "'>
										<input data-section='" . $section . "' data-attribute='active' type='checkbox' class='settingInput' id='" . $section . "_-_dd' name='" . $section . "_-_dd'".($dd ? ' checked' : '') .">Dropdown
									</label>
								</div>
								<div class='col-xs-6 col-md-2 med-gutters btn-group' data-toggle='buttons'>
									<label class='btn btn-primary btn-sm".($default ? ' active' : ''). "' for='" . $section . "_-_default' >
										<input data-section='" . $section . "' data-attribute='default' type='radio' class='settingInput' name='" . $section . "_-_default' id='" . $section . "_-_default' autocomplete='off' ".($default ? ' checked' : '') .">Default
									</label>
								</div>
							</div>
							<div class='row justify-content-center'>
								<button type='button' class='btn btn-danger btn-sm col-xs-6 col-md-2' value='Remove' id='remove-" . $section . "'>Remove</button>
							</div>
						</div>";
        }
    }
    $pageOutput .= "
                </div>
                <div class='text-center' style='margin-top: 15px;'>
                    <div class='btn-group' role='group' aria-label='Buttons'>
                        <a class='btn btn-primary btn-sm btn-block btn-md' id='addApplication'><span class='fa muximux-plus'></span> Add new</a>
                        <a class='btn btn-danger btn-md' id='removeAll'><span class='fa muximux-trash'></span> Remove all</a>
                    </div>
                </div>
            </form>";
    return $pageOutput;
}

// Generate our splash screen contents (basically a very little version of parse_ini).
function splashScreen() {
    $config = new Config_Lite(CONFIG);
    $css = getThemeFile();
    $cssColor = ((parseCSS($css,'.colorgrab','color') != false) ? parseCSS($css,'.colorgrab','color') : '#FFFFFF');
    $themeColor = $config->get('general','color',$cssColor);
    $tabColor = $config->getBool('general','tabcolor',false);
    
    $splash = "";
    
    foreach ($config as $keyname => $section) {
	$enabled = $config->getBool($keyname,'enabled',false);
	if (($keyname != "general") && ($keyname != "settings") && $enabled) {
    	$color = ($tabColor===true ? $section["color"] : $themeColor);
		$icon = $config->get($keyname,'icon','muximux-play');
		$icon = str_replace('fa-','muximux-',$icon);
			
			$splash .= "
									<div class='btnWrap'>
										<div class='card card-inverse splashBtn' data-content='" . $keyname . "'>
											<a class='panel-heading' data-title='" . $section["name"] . "'>
												<br><i class='fa fa-3x " . $icon . "' style='color:".$color."'></i><br>
												<p class='splashBtnTitle' style='color:#ddd'>".$section["name"]."</p>
											</a>
										</div>
									</div>";
		}
	}
	return $splash;
}

// Generate the contents of the log
function log_contents() {
    $out = '<ul>
                <div id="logContainer">
    ';
    $filename = LOGPATH;
	$file = file($filename);
	$file = array_reverse($file);
	$lineOut = "";
	$concat = false;
	foreach($file as $line){
		$color = 'alert alert-info';
		$lvl = preg_match("/\[[^\]]*\]/", $line)[0];
		switch ($lvl) {
			case "ERROR":
				$color = 'alert alert-danger';
				break;
			case "DEBUG":
				$color = 'alert alert-warning';
				break;
			case "INFO":
				$color = 'alert alert-success';
				break;
		}

		if ($concat) {
			$out .='
                    <li class="logLine alert alert-info">'.
                        $lineOut.'
                    </li>';
		}
		$concat = false;
		

		if (! $concat) {
			$out .='
                    <li class="logLine '.$color.'">'.
                        $lineOut.'
                    </li>';
		}
        
    }
    $out .= '</div>
            </ul>
    ';
    return $out;
}

function isDomainAvailible($domain) {
	//check, if a valid url is provided
	if(!filter_var($domain, FILTER_VALIDATE_URL))
	{
		return false;
	}
	file_get_contents($domain);
	write_log("Header: ".$http_response_header[0]);
	$result = (preg_match("/200 OK/",$http_response_header[0]) ? true : false);
	write_log("CHeck result: ".$result);
	return $result;
}


// Check if the user changes tracking branch, which will change the SHA and trigger an update notification
function checkBranchChanged() {
    $config = new Config_Lite(CONFIG);
    if ($config->getBool('settings', 'branch_changed', false)) {
        saveConfig($config);
	checksetSHA();
        return true;
    } else {
        return false;
    }
}

// Quickie to get the theme from settings
function getTheme()
{
    $config = new Config_Lite(CONFIG);
    $item = $config->get('general', 'theme', 'Classic');
	return strtolower($item);
}



// List all available themes in directory
function listThemes() {
    $dir    = './css/theme';
    $themelist ="";
    $themes = scandir($dir);
    foreach($themes as $value){
        $splitName = explode('.', $value);
		if  (!empty($splitName[0])) {
			$name = ucfirst($splitName[0]);
            $themelist .="
                                <option value='".$name."' ".(($name == ucfirst(getTheme())) ? 'selected' : '').">".$name."</option>";
        }
    }
    return $themelist;
}

// Build the contents of our menu
function menuItems() {
    $config = new Config_Lite(CONFIG);
    $standardmenu = "<ul class='cd-tabs-navigation'>
                <nav>";
    $dropdownmenu = "
							<li>
								<a data-toggle='modal' data-target='#settingsModal' data-title='Settings'>
									<span class='fa muximux-cog'></span>Settings
								</a>
							</li>
							<li>
								<a id='logModalBtn' data-toggle='modal' data-target='#logModal' data-title='Log Viewer'>
									<span class='fa muximux-file-text-o'></span> Log
								</a>
							</li>";
    $int = 0;
	$autohide = $config->getBool('general', 'autohide', false);
	$dropdown = $config->getBool('general', 'enabledropdown', true);
	$mobileoverride = $config->getBool('general', 'mobileoverride', false);
	$authentication = $config->getBool('general', 'authentication', false);
    $drawerdiv = '';
	foreach ($config as $keyname => $section) {
        if (($keyname != "general") && ($keyname != "settings")) {
            $name = $config->get($keyname, 'name', '');
            $color = $config->get($keyname, 'color', '#000');
            $icon = $config->get($keyname, 'icon', 'muximux-play');
		    $icon = str_replace('fa-','muximux-',$icon);
		    $default = $config->getBool($keyname, 'default', false);
            $enabled = $config->getBool($keyname, 'enabled', false);
            $dd = $config->getBool($keyname, 'dd', false);
			        
			if ($enabled) {
				if ($dropdown) {
					if (!$dd) {
						$standardmenu .= "
							<li class='cd-tab' data-index='".$int."'>
								<a data-content='" . $keyname . "' data-title='" . $name . "' data-color='" . $color . "' class='".($default ? 'selected' : '')."'>
									<span class='fa " . $icon . " fa-lg'></span> " . $name . "
								</a>
							</li>";
						$int++;
					} else {
						$dropdownmenu .= "
							<li>
								<a data-content='" . $keyname . "' data-color='" . $color . "' data-title='" . $name . "'>
									<span class='fa " . $icon . "'></span> " . $name . "
								</a>
							</li>";
					}
				}
			}
		}	
	}
	$standardmenu .= "</nav>
            </ul>";
	$splashScreen = $config->getBool('general', 'splashscreen', false);
    
    $moButton = "
			<ul class='main-nav'>
                <li class='navbtn ".(($mobileoverride == "true") ? '' : 'hidden')."'>
                    <a id='override' title='Click this button to disable mobile scaling on tablets or other large-resolution devices.'>
                        <span class='muximux-mobile mm-lg'></span>
                    </a>
                </li>
                <li class='navbtn ".(($splashScreen == "true") ? '' : 'hidden')."'>
					<a id='showSplash' data-toggle='modal' data-target='#splashModal' data-title='Show Splash'>
                		<span class='muximux-home4 mm-lg'></span>
                    </a>
                </li>
                <li class='navbtn ".(($authentication == "true") ? '' : 'hidden')."'>
                    <a id='logout' title='Click this button to log out of Muximux.'>
                        <span class='muximux-sign-out mm-lg'></span>
                    </a>
                </li>
				<li class='navbtn'>
                    <a id='reload' title='Double click your app in the menu, or press this button to refresh the current app.'>
                        <span class='muximux-refresh mm-lg'></span>
                    </a>
                </li>
				
			
    ";


    $drawerdiv .= "<div class='cd-tabs-bar ".(($autohide == "true")? 'drawer' : '')."'>";

    if ($dropdown == "true") {
        $item = 
			$drawerdiv . 
            $moButton ."
                <li class='dd navbtn'>
                    <a id='hamburger'>
                        <span class='muximux-bars mm-lg'></span>
                    </a>
                    <ul class='drop-nav'>" .
                                $dropdownmenu ."
                    </ul>
                </li>
            </ul>".
            $standardmenu ."
                
        </div>
        ";
    } else {
        $item =  
			$drawerdiv . 
			$moButton .
			$standardmenu;
    }
    return $item;
}

// Quickie fetch the main title
function getTitle() {
    $config = new Config_Lite(CONFIG);
    $item = $config->get('general', 'title', 'Muximux - Application Management Console');
    return $item;

}
// Quickie fetch of the current selected branch
function getBranch() {
    $config = new Config_Lite(CONFIG);
    $branch = $config->get('general', 'branch', 'master');
    return $branch;
}

// Reads for "branches" from settings.  If not found, fetches list from github, saves, parses, and returns
function getBranches() {
    $config = new Config_Lite(CONFIG);
    $branches = [];
    $branches = $config->get('settings', 'branches',$branches);
    if ($branches == []) {
        fetchBranches(true);
    } else {
        $branches = $config->get('settings', 'branches');
    }
    return $branches;
}
// Fetch a list of branches from github, along with their current SHA
function fetchBranches($skip) {
    $config = new Config_Lite(CONFIG);
    $last = $config->get('settings', 'last_check', "0");
    if ((time() >= $last + 3600) || $skip) { // Check to make sure we haven't checked in an hour or so, to avoid making GitHub mad
        if (time() >= $last + 3600) {
            write_log('Refreshing branches from github - automatically triggered.');
        } else {
            write_log('Refreshing branches from github - manually triggered.');
        }
        $url = 'https://api.github.com/repos/mescon/Muximux/branches';
            $options = array(
          'http'=>array(
            'method'=>"GET",
            'header'=>"Accept-language: en\r\n" .
                      "User-Agent: Mozilla/5.0 (iPad; U; CPU OS 3_2 like Mac OS X; en-us) AppleWebKit/531.21.10 (KHTML, like Gecko) Version/4.0.4 Mobile/7B334b Safari/531.21.102011-10-16 20:23:10\r\n" // i.e. An iPad
          )
        );

        $context = stream_context_create($options);
        $json = file_get_contents($url,false,$context);
        if ($json == false) {
            write_log('Error fetching JSON from Github.','E');
            $result = false;
        } else {
            $array = json_decode($json,true);
            $names = array();
            $shas = array();
            foreach ($array as $value) {
                foreach ($value as $key => $value2) {
                    if ($key == "name") {
                            array_push($names,$value2);
                    } else {
                        foreach ($value2 as $key2 => $value3) {
                            if ($key2 == "sha" ) {
                                array_push($shas,$value3);
                            }
                        }
                    }
                }
            }
            $outP = array_combine($names,$shas);
            $config ->set('settings','branches',$outP);
            $config ->set('settings','last_check',time());
            saveConfig($config);
            $result = true;
        }

    } else {
        $result = false;
    }
    return $result;

}

// This checks whether we have a SHA, and if not, whether we are using git or zip updates and stores
// the data accordingly
function checksetSHA() {
	$config = new Config_Lite(CONFIG);
	$shaOut = $branchOut = $git = false;
    $shaIn = $config->get('settings','sha',false);
	$branchIn = getBranch();
	$git = can_git();
	if ($git) {
		$shaOut = exec('git rev-parse HEAD');
		$branchOut = exec('git rev-parse --abbrev-ref HEAD');
	} else {
		if (! $shaIn) {
			$branchArray = getBranches();
			$branchOut = $branchIn();
			foreach ($branchArray as $branchName => $shaVal) {
				if ($branchName==$branchOut) {
					$shaOut = $shaVal;
				}
			}
		} 
	}
	$changed = false;
	if ($branchIn != $branchOut) {
		$config->set('settings', 'branch', $branchOut);
		$changed = true;
	}
	if ($shaIn != $shaOut) {
		$config->set('settings', 'sha', $shaOut);
		$changed = true;
	}
	if ($changed) {
		saveConfig($config);
        
	}
}

// Read SHA from settings and return it's value.
function getSHA() {
    $config = new Config_Lite(CONFIG);
    $item = $config->get('settings', 'sha', '00');
    return $item;
}

// Retrieve password hash from settings and return it for "stuff".
function getPassHash() {
    $config = new Config_Lite(CONFIG);
    $item = $config->get('general', 'password', 'foo');
    return $item;
}

// This little gem helps us replace a whome bunch of AJAX calls by sorting out the info and
// writing it to meta-tags at the bottom of the page.  Might want to look at calling this via one AJAX call.
function metaTags() {
	$inipath = false;
    $config = new Config_Lite(CONFIG);
    $iniPath = php_ini_loaded_file();
        if (! $inipath) {
            $inipath = "php.ini";
        }
    $created = filectime(CONFIG);
	$branchChanged = (checkBranchChanged() ? 'true' : 'false');
	$tags = "";

	$general = $config->get('general');
	$general['cwd'] = getcwd();
	$general['sha'] = getSHA();
	$general['phpini'] = $iniPath;
	$general['created'] = $created;
	foreach ($general as $id=>$value) {
		$id .= "-data";
		if (is_bool($value)) $value = boolval($value);
		$tags .= '<meta id="'.$id.'" data='.$value.' class="metatag">'.PHP_EOL;
	}

    return $tags;
}

// Set up the actual iFrame contents, as the name implies.
function frameContent() {
    $config = new Config_Lite(CONFIG);
    if (empty($item)) $item = '';
    foreach ($config as $keyname => $section) {
    $landingpage = $config->getBool($keyname,'landingpage',false);
    $enabled = $config->getBool($keyname,'enabled',true);
    $default = $config->getBool($keyname,'default',false);
    $scale = $config->get($keyname,'scale',1);
    $url = $section["url"];
    $urlArray = parse_url($url);
	if (!$urlArray) {
		$protocol = preg_match("/http/",explode("://",$url)[0]) ? explode("://",$url)[0] . "://" : serverProtocol();
		$url = str_replace($protocol,"",$url);
		$port = explode(":",$url)[1] ? ":".explode(":",$url)[1] : "";
		$url = str_replace($port,"",$url);
		$host = $url ? $url : $_SERVER['HTTP_HOST'];
		$url = $protocol.$host.$port;
		$urlArray = parse_url($url);
	}
    // TODO: Make sure this still works
    if ($landingpage) $urlArray['query'] = isset($urlArray['query']) ? $urlArray['query']."&" : ""."landing=" . urlencode($keyname.":".$url);
    $url = $urlArray ? build_url($urlArray) : $url;
    if ($enabled && ($keyname != 'settings') && ($keyname != 'general')) {
		$item .= "
				<li data-content='" . $keyname . "' data-scale='" . $scale ."' ".($default ? "class='selected'" : '').">
					<iframe sandbox='allow-forms allow-presentation allow-same-origin allow-pointer-lock allow-scripts allow-popups allow-modals allow-top-navigation'
					allowfullscreen='true' webkitallowfullscreen='true' mozallowfullscreen='true' scrolling='auto' data-title='" . $section["name"] . "' ".($default ? 'src' : 'data-src')."='" . $url . "'></iframe>
				</li>";
        }
    }
    return $item;
}

function build_url($parsed_url) {
	$scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : serverProtocol();
	$host     = isset($parsed_url['host']) ? $parsed_url['host'] : "";
	$port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
	$user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
	$pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
	$pass     = ($user || $pass) ? "$pass@" : '';
	$path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
	$query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
	$fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
	return "$scheme$user$pass$host$port$path$query$fragment";
}
// Build a landing page.
function landingPage($keyname) {
    $config = new Config_Lite(CONFIG);
    $item = "
    <html lang='en'>
    <head>
    <title>" . $config->get($keyname, 'name') . "</title>
    <link rel='stylesheet' href='css/landing.css'>
    </head>
    <body>
    <div class='login'>
        <div class='heading'>
            <h2><span class='fa " . $config->get($keyname, 'icon') . " fa-3x'></span></h2>
            <section>
                <a href='" . $config->get($keyname, 'url') . "' target='_self' title='Launch " . $config->get($keyname, 'name') . "!'><button class='float'>Launch " . $config->get($keyname, 'name') . "</button></a>
            </section>
        </div>
     </div>
     </body></html>";
    if (empty($item)) $item = '';
    return $item;
}

// This method checks whether we can execute, if the directory is a git, and if git is installed
function can_git()
{
	if ((exec_enabled() == true) && (file_exists('.git'))) {
		$whereIsCommand = (PHP_OS == 'WINNT') ? 'where git' : 'which git'; 	// Establish the command for our OS
		$gitPath = shell_exec($whereIsCommand); 							// Find where git is
		$git = (empty($gitPath) ? false : true); 							// Make sure we have a path
		if ($git) {															// Double-check git is here and executable
			exec($gitPath . ' --version', $output);
			preg_match('#^(git version)#', current($output), $matches);
			$git = (empty($matches[0]) ? $gitPath : false);  				// If so, return path.  If not, return false.
		}
	} else {
		$git = false;
	}
	return $git;
}


// URL parameters
if (isset($_GET['landing'])) {
    $keyname = $_GET['landing'];
    echo landingPage($keyname);
    die();
}


// Things wrapped inside this are protected by a secret hash.
if (isset($_GET['secret'])) {
	if ($_GET['secret'] !== $_SESSION['secret']) {
		write_log("Invalid secret sent, dying.","ERROR");
		die();
	}
	if (isset($_GET['get']) && $_GET['get'] == 'hash') {
        if (exec_enabled() == true) {
		$git = can_git();
            if ($git !== false) {
                $hash = 'unknown';
            } else {
                $hash = exec($git . ' log --pretty="%H" -n1 HEAD');
            }
        } else {
            $hash = 'noexec';
        }
        echo $hash;
        die();
    }

    if (isset($_GET['colors'])) {
    	echo json_encode(appColors());
    	die;
    }

    if (isset($_GET['id']) && isset($_GET['value'])) {
		$key = $_GET['id'];
		$value = $_GET['value'];
		$section = $_GET['section'];
	    $oldHash = getPassHash();
	    $oldBranch = getBranch();
	    $terminate = false;
	    if ($key == 'scale') $value = $value / 100;
    	    switch ($key) {
		        case "password":
			        if ($value != $oldHash) {
				        write_log('Successfully updated password.', 'I');
				        $value = password_hash($value, PASSWORD_BCRYPT);
			        }
			        break;
		        case "name":
		        	if ($section !== 'general') {
		        		write_log("Need to rename the section here.");
		        		if (isset($config[$section])) {
		        			write_log("Found section $section");
		        			foreach($config as $search => $data) {
		        				if ($search == $section) $search = $value;
					        }
		        			saveConfig($config);
		        			die();
				        }
			        }
			        break;
	        }
	    write_log("Updating value for $key : $value in $section","INFO");
    	$config->set($section,$key,$value);
    	saveConfig($config);
    	die();
    }

    if(isset($_GET['remove']) && $_GET['remove'] == "backup") {
        unlink('backup.ini.php');
        echo "deleted";
        die();
    }

    if(isset($_GET['action']) && $_GET['action'] == "update") {
        $sha = $_GET['sha'];
        $results = downloadUpdate($sha);
		if ($results === true) {
			echo $results;
			die();
		} else {
			$data = array('type' => 'error', 'message' => $results);
			header('HTTP/1.1 400 Bad Request');
			header('Content-Type: application/json; charset=UTF-8');
			echo json_encode($data);
			die();
		}
    }

    if(isset($_GET['action']) && $_GET['action'] == "branches") {
        $results = fetchBranches(true);
        echo $results;
        die();
    }

    if(isset($_GET['action']) && $_GET['action'] == "log") {
        echo log_contents();
        die();
    }

    if(isset($_GET['action']) && $_GET['action'] == "writeLog") {
        $msg = $_GET['msg'];
        if(isset($_GET['lvl'])) {
            $lvl = $_GET['lvl'];
            write_log($msg,$lvl);
        } else {
            write_log($msg);
        }
        die();
    }
}

// This downloads updates from git if available and able, otherwise, from zip.
function downloadUpdate($sha) {
	$git = can_git();
	if ($git !== false) {
		$branch = getBranch();
		if ($sha == $branch) {
			$resultshort = exec('git status');
			$result = (preg_match('/clean/',$resultshort));
				if ($result !== true) {
				$resultmsg = shell_exec('git status');
				$result ='Install Failed!  Local instance has files that will interfer with branch change - please manually stash changes and try again. Result message: "' . $resultshort.'"';
				write_log($result ,'E');
				$result ='Install Failed!  Local instance has files that will interfer with branch changed - please manually stash changes and try again. See log for details.';
				return $result;
			}
			$result = exec('git checkout '. $branch);
			write_log('Changing git branch, command result is ' . $result,'D');
			$result = (preg_match('/up-to-date/',$result));
			if ($result) {
				$mySha = exec('git rev-parse HEAD');
				$config = new Config_Lite(CONFIG);
				if (!preg_match('/about a specific subcommand/',$mySha)) { // Something went wrong with the command to get our SHA, fall back to using the passed value.
					$config->set('settings','sha',$mySha);
					$config->set("settings","branch_changed",false);
					saveConfig($config);
				} else {
					$config->set('settings','sha',$sha);
				}
				saveConfig($config);
			} else {
				$result = 'Branch change failed!  An unknown error occurred attempting to update.  Please manually check git status and fix.';
			}
		} else {
			$resultshort = exec('git status');
			$result = (preg_match('/clean/',$resultshort));
			if ($result !== true) {
				$result ='Install Failed!  Local instance has files that will interfer with git pull - please manually stash changes and try again. Result message: "' . $resultshort.'"';
				write_log($result ,'E');
				$result ='Install Failed!  Local instance has files that will interfer with git pull - please manually stash changes and try again. See log for details.';
				return $result;
			}
			$result = exec('git pull');
			write_log('Updating via git, command result is ' . $result,'D');
			$result = (preg_match('/Updating/',$result));
			if ($result) {
				$mySha = exec('git rev-parse HEAD');
				$config = new Config_Lite(CONFIG);
				if (!preg_match('/about a specific subcommand/',$mySha)) { // Something went wrong with the command to get our SHA, fall back to using the passed value.
					$config->set('settings','sha',$mySha);
				} else {
					$config->set('settings','sha',$sha);
				}
				saveConfig($config);
			} else {
				$result = 'Install Failed!  An unknown error occurred attempting to update.  Please manually check git status and fix.';
			}
		}
	} else {
		$result = false;
		$zipFile = "Muximux-".$sha. ".zip";
		$f = file_put_contents($zipFile, fopen("https://github.com/mescon/Muximux/archive/". $sha .".zip", 'r'), LOCK_EX);
		if(FALSE === $f) {
			$result = 'Install Failed!  An error occurred saving the update.  Please check directory permissions and try again.';
		} else {
			$zip = new ZipArchive;
			$res = $zip->open($zipFile);
			if ($res === TRUE) {
				$result = $zip->extractTo('./.stage');
				$zip->close();

				if ($result === TRUE) {
					cpy("./.stage/Muximux-".$sha, "./");
					deleteContent("./.stage");
					$gone = unlink($zipFile);
				} else {
					$result = 'Install Failed!  Unable to extract zip file.  Please check directory permissions and try again.';
				}
				$config = new Config_Lite(CONFIG);
				$config->set('settings','sha',$sha);
				saveConfig($config);
			} else {
				$result = 'Install Failed!  Unable to open zip file.  Check directory permissions and try again.';
			}
		}
	}
	if ($result === true) {
		deleteContent('./cache');
		write_log('Update Succeeded.','I');
	} else {
		write_log($result ,'E');
	}
    
    return $result;
}
