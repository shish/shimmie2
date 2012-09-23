/**    
 * @author Jefferson González
 * @copyright 2010 Jefferson González
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

package jaris.display;
import flash.display.MovieClip;
import flash.events.ContextMenuEvent;
import flash.Lib;
import flash.net.URLRequest;
import flash.ui.ContextMenu;
import flash.ui.ContextMenuItem;
import jaris.player.Player;
import jaris.player.AspectRatio;
import jaris.Version;

/**
 * Modify original context menu
 */
class Menu 
{
	private var _movieClip:MovieClip;
	public static var _player:Player;
	
	private var _contextMenu:ContextMenu;
	private var _jarisVersionMenuItem:ContextMenuItem;
	private var _playMenuItem:ContextMenuItem;
	private var _fullscreenMenuItem:ContextMenuItem;
	private var _aspectRatioMenuItem:ContextMenuItem;
	private var _muteMenuItem:ContextMenuItem;
	private var _volumeUpMenuItem:ContextMenuItem;
	private var _volumeDownMenuItem:ContextMenuItem;
	private var _qualityContextMenu:ContextMenuItem;
	
	public function new(player:Player) 
	{
		_movieClip = Lib.current;
		_player = player;
		
		//Initialize context menu replacement
		_contextMenu = new ContextMenu();
		_contextMenu.hideBuiltInItems();
		
		_contextMenu.addEventListener(ContextMenuEvent.MENU_SELECT, onMenuOpen);
		
		//Initialize each menu item
		_jarisVersionMenuItem = new ContextMenuItem("Jaris Player v" + Version.NUMBER + " " + Version.STATUS, true, true, true);
		_jarisVersionMenuItem.addEventListener(ContextMenuEvent.MENU_ITEM_SELECT, onJarisVersion);
		
		_playMenuItem = new ContextMenuItem("Play (SPACE)", true, true, true);
		_playMenuItem.addEventListener(ContextMenuEvent.MENU_ITEM_SELECT, onPlay);
		
		_fullscreenMenuItem = new ContextMenuItem("Fullscreen View (F)");
		_fullscreenMenuItem.addEventListener(ContextMenuEvent.MENU_ITEM_SELECT, onFullscreen);
		
		_aspectRatioMenuItem = new ContextMenuItem("Aspect Ratio (original) (TAB)");
		_aspectRatioMenuItem.addEventListener(ContextMenuEvent.MENU_ITEM_SELECT, onAspectRatio);
		
		_muteMenuItem = new ContextMenuItem("Mute (M)");
		_muteMenuItem.addEventListener(ContextMenuEvent.MENU_ITEM_SELECT, onMute);
		
		_volumeUpMenuItem = new ContextMenuItem("Volume + (arrow UP)");
		_volumeUpMenuItem.addEventListener(ContextMenuEvent.MENU_ITEM_SELECT, onVolumeUp);
		
		_volumeDownMenuItem = new ContextMenuItem("Volume - (arrow DOWN)");
		_volumeDownMenuItem.addEventListener(ContextMenuEvent.MENU_ITEM_SELECT, onVolumeDown);
		
		_qualityContextMenu = new ContextMenuItem("Lower Quality", true, true, true);
		_qualityContextMenu.addEventListener(ContextMenuEvent.MENU_ITEM_SELECT, onQuality);
		
		//add all context menu items to context menu object
		_contextMenu.customItems.push(_jarisVersionMenuItem);
		_contextMenu.customItems.push(_playMenuItem);
		_contextMenu.customItems.push(_fullscreenMenuItem);
		_contextMenu.customItems.push(_aspectRatioMenuItem);
		_contextMenu.customItems.push(_muteMenuItem);
		_contextMenu.customItems.push(_volumeUpMenuItem);
		_contextMenu.customItems.push(_volumeDownMenuItem);
		_contextMenu.customItems.push(_qualityContextMenu);
		
		//override default context menu
		_movieClip.contextMenu = _contextMenu;
	}
	
	/**
	 * Update context menu item captions depending on player status before showing them
	 * @param	event
	 */
	private function onMenuOpen(event:ContextMenuEvent):Void
	{
		if (_player.isPlaying())
		{
			_playMenuItem.caption = "Pause (SPACE)";
		}
		else
		{
			_playMenuItem.caption = "Play (SPACE)";
		}
		
		if (_player.isFullscreen())
		{
			_fullscreenMenuItem.caption = "Normal View";
		}
		else
		{
			_fullscreenMenuItem.caption = "Fullscreen View (F)";
		}
		
		if (_player.getMute())
		{
			_muteMenuItem.caption = _player.isFullscreen()?"Unmute":"Unmute (M)";
		}
		else
		{
			_muteMenuItem.caption = _player.isFullscreen()?"Mute":"Mute (M)";
		}
		
		switch(_player.getAspectRatioString())
		{
			case "original":
				_aspectRatioMenuItem.caption = "Aspect Ratio (1:1) (TAB)";
				
			case "1:1":
				_aspectRatioMenuItem.caption = "Aspect Ratio (3:2) (TAB)";
				
			case "3:2":
				_aspectRatioMenuItem.caption = "Aspect Ratio (4:3) (TAB)";
				
			case "4:3":
				_aspectRatioMenuItem.caption = "Aspect Ratio (5:4) (TAB)";
				
			case "5:4":
				_aspectRatioMenuItem.caption = "Aspect Ratio (14:9) (TAB)";
				
			case "14:9":
				_aspectRatioMenuItem.caption = "Aspect Ratio (14:10) (TAB)";
				
			case "14:10":
				_aspectRatioMenuItem.caption = "Aspect Ratio (16:9) (TAB)";
				
			case "16:9":
				_aspectRatioMenuItem.caption = "Aspect Ratio (16:10) (TAB)";
				
			case "16:10":
				_aspectRatioMenuItem.caption = "Aspect Ratio (original) (TAB)";
		}
		
		if (_player.getQuality())
		{
			_qualityContextMenu.caption = "Lower Quality";
		}
		else
		{
			_qualityContextMenu.caption = "Higher Quality";
		}
	}
	
	/**
	 * Open jaris player website
	 * @param	event
	 */
	private function onJarisVersion(event:ContextMenuEvent)
	{
		Lib.getURL(new URLRequest("http://jaris.sourceforge.net"), "_blank");
	}
	
	/**
	 * Toggles playback
	 * @param	event
	 */
	private function onPlay(event:ContextMenuEvent)
	{
		_player.togglePlay();
	}
	
	/**
	 * Toggles fullscreen
	 * @param	event
	 */
	private function onFullscreen(event:ContextMenuEvent)
	{
		_player.toggleFullscreen();
	}
	
	/**
	 * Toggles aspect ratio
	 * @param	event
	 */
	private function onAspectRatio(event:ContextMenuEvent)
	{
		_player.toggleAspectRatio();
	}
	
	/**
	 * Toggles mute
	 * @param	event
	 */
	private function onMute(event:ContextMenuEvent)
	{
		_player.toggleMute();
	}
	
	/**
	 * Raise volume
	 * @param	event
	 */
	private function onVolumeUp(event:ContextMenuEvent)
	{
		_player.volumeUp();
	}
	
	/**
	 * Lower volume
	 * @param	event
	 */
	private function onVolumeDown(event:ContextMenuEvent)
	{
		_player.volumeDown();
	}
	
	/**
	 * Toggle video quality
	 * @param	event
	 */
	private function onQuality(event:ContextMenuEvent)
	{
		_player.toggleQuality();
	}
}