// Windows 7 Intergration
$(document).ready(function(){
	try{
		if(window.external.msIsSiteMode()){
			// Category "Quark Media Bibliotheek"
			window.external.msSiteModeClearJumplist();
			window.external.msSiteModeCreateJumplist('Quark Media Bibliotheek');
			window.external.msSiteModeAddJumpListItem('Muziek: Voeg Discografie toe',		'http://quark/Applications/SickPotato/', '/assets/images/icons/Music.ico');
			window.external.msSiteModeAddJumpListItem('Muziek: Voeg album toe',				'http://quark/Applications/SickPotato/', '/assets/images/icons/Music.ico');
			window.external.msSiteModeAddJumpListItem('Videos: Voeg film toe',				'http://quark/Applications/SickPotato/', '/assets/images/icons/Movies.ico');
			window.external.msSiteModeAddJumpListItem('Videos: Voeg serie toe',				'http://quark/Applications/SickPotato/', '/assets/images/icons/Movies.ico');
			//window.external.msSiteModeAddJumpListItem('Televisie: Neem een programma op',	'http://quark/Applications/SickPotato/', '/assets/images/icons/videos.ico');
			window.external.msSiteModeShowJumplist();
		}
	}catch(ex){}
});