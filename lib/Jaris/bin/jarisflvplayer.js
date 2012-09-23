/**    
 * @author Jefferson Gonzalez
 * @copyright 2010 Jefferson Gonzalez
 *
 * @license 
 * This file is part of Jaris FLV Player.
 *
 * Jaris FLV Player is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License or GNU LESSER GENERAL 
 * PUBLIC LICENSE as published by the Free Software Foundation, either version 
 * 3 of the License, or (at your option) any later version.
 *
 * Jaris FLV Player is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License and 
 * GNU LESSER GENERAL PUBLIC LICENSE along with Jaris FLV Player.  If not, 
 * see <http://www.gnu.org/licenses/>.
 */

/**
 *Interface for the JarisFLVPlayer JavaScript API implemented 
 *by Sascha from http://projekktor.com/
 *@param id The id of the flash object
*/
function JarisFLVPlayer(id){

	this.playerId = id; //Stores the id of the player
	this.player = document.getElementById(id); //Object that points to the player
}

//Event constants
JarisFLVPlayer.event = {
	MOUSE_HIDE: "onMouseHide", 
	MOUSE_SHOW: "onMouseShow",
	MEDIA_INITIALIZED: "onDataInitialized",
	BUFFERING: "onBuffering",
	NOT_BUFFERING: "onNotBuffering",
	RESIZE: "onResize",
	PLAY_PAUSE: "onPlayPause",
	PLAYBACK_FINISHED: "onPlaybackFinished",
	CONNECTION_FAILED: "onConnectionFailed",
	ASPECT_RATIO: "onAspectRatio",
	VOLUME_UP: "onVolumeUp",
	VOLUME_DOWN: "onVolumeDown",
	VOLUME_CHANGE: "onVolumeChange",
	MUTE: "onMute",
	TIME: "onTimeUpdate",
	PROGRESS: "onProgress",
	SEEK: "onSeek",
	ON_ALL: "on*"
};
	
JarisFLVPlayer.prototype.isBuffering = function(){
	return this.player.api_get("isBuffering");
}

JarisFLVPlayer.prototype.isPlaying = function(){
	return this.player.api_get("isPlaying");
}

JarisFLVPlayer.prototype.getCurrentTime = function(){
	return this.player.api_get("time");
}

JarisFLVPlayer.prototype.getBytesLoaded = function(){
	return this.player.api_get("loaded");
}

JarisFLVPlayer.prototype.getVolume = function(){
	return this.player.api_get("volume");
}

JarisFLVPlayer.prototype.addListener = function(event, listener){
	this.player.api_addlistener(event, listener);
}

JarisFLVPlayer.prototype.removeListener = function(event){
	this.player.api_removelistener(event);
}

JarisFLVPlayer.prototype.play = function(){
	this.player.api_play();
}

JarisFLVPlayer.prototype.pause = function(){
	this.player.api_pause();
}

JarisFLVPlayer.prototype.seek = function(seconds){
	this.player.api_seek(seconds);
}

JarisFLVPlayer.prototype.volume = function(value){
	this.player.api_volume(value);
}
