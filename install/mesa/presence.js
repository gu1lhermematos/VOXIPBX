var showLines          = 2;
var notifyDuration     = 6;
var warnClose          = true;
var warnHangup         = true;
var dynamicLineDisplay = false;
var soundChat          = true;
var soundQueue         = true;
var soundRing          = true;
var displayQueue       = 'max'; // max or min
var pdateFormat        = 'ddd, HH:MM';
var disableVoicemail   = false; 
var language           = 'pt_BR';
var voicemailFormat    = 'wav';
var phonebookWidth     = 960;
var phonebookHeight    = 580;
var noExtenInLabel     = false;
var disableWebSocket   = false;
var enableDragTransfer = true; 
var startNotRegistered = false; 
var desktopNotify      = true;
var logoutUrl          = '';

var presence = new Object();
presence['']               = '';
presence['Do not Disturb'] = '#FF8A8A';
presence['Out to lunch']   = '#57BCD9';
presence['Break']          = '#6094DB';
presence['Meeting']        = '#CDD11B';

/* Uncomment the following to enable pause menu with reasons 
//
var pauseReasons = new Object();
pauseReasons['Break'] = 1;
pauseReasons['Lunch'] = 2;
*/

var availLang = new Object();
availLang['ca']='Català';
availLang['cr']='Hrvatski';
availLang['de']='Deutsch';
availLang['he']='עברית';
availLang['el']='Ελληνικά';
availLang['en']='English';
availLang['es']='Español';
availLang['fr_FR']='Francais';
availLang['hu']='Magyar';
availLang['it']='Italiano';
availLang['nl']='Dutch'
availLang['pl']='Polski';
availLang['pt_BR']='Português';
availLang['ru']='Русский';
availLang['se']='Svenska';
availLang['tr']='Türkçe';
availLang['zh']='简体中文'; 

var lang = new Object();
